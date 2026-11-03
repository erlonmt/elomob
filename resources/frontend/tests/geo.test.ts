import { strict as assert } from 'node:assert';
import test from 'node:test';
import { geographicLayout, mapTiles, placeLabels, routeFeatures, searchQuery } from '../lib/geo.ts';
import type { Step, Stop } from '../lib/types.ts';

test('map projection preserves geographic orientation and fits route endpoints', () => {
  const stops = [
    { longitude: -34.9, latitude: -8.1 },
    { longitude: -34.88, latitude: -8.06 },
  ] as Stop[];
  const layout = geographicLayout(stops, []);
  const southwest = layout.position([-34.9, -8.1]);
  const northeast = layout.position([-34.88, -8.06]);
  assert.ok(southwest[0] < northeast[0]);
  assert.ok(southwest[1] > northeast[1]);
  for (const [x, y] of [southwest, northeast]) {
    assert.ok(x >= 0 && x <= 900 && y >= 0 && y <= 560);
  }
});

test('missing route coordinates never become a fictitious point at zero', () => {
  assert.deepEqual(
    routeFeatures([{ origin_latitude: null, origin_longitude: null } as unknown as Step]),
    [],
  );
});

test('editing a search retains the exact transport selection and departure', () => {
  const query = new URLSearchParams(
    searchQuery({
      origin_id: 1,
      destination_id: 2,
      filters: { modes: ['bike'] },
      departure_at: '2026-10-31T08:00:00-03:00',
    }),
  );
  assert.deepEqual(query.getAll('modes'), ['bike']);
  assert.equal(query.get('departure_at'), '2026-10-31T08:00:00-03:00');
  assert.equal(
    new URLSearchParams(
      searchQuery({
        origin_id: 1,
        destination_id: 2,
        filters: { modes: ['bus'] },
        departure_at: null,
      }),
    ).has('departure_at'),
    false,
  );
});

/** Stops spread over Recife, from Boa Viagem to Casa Amarela, as the network map shows them. */
const recife = [
  { longitude: -34.899, latitude: -8.119 },
  { longitude: -34.871, latitude: -8.063 },
  { longitude: -34.918, latitude: -8.024 },
  { longitude: -34.95, latitude: -8.049 },
] as Stop[];

/** The OpenStreetMap tile holding a point, by the slippy map formula of the OpenStreetMap wiki. */
const osmTile = (longitude: number, latitude: number, level: number) => {
  const latitudeRadians = (latitude * Math.PI) / 180;
  return [
    Math.floor(((longitude + 180) / 360) * 2 ** level),
    Math.floor(((1 - Math.asinh(Math.tan(latitudeRadians)) / Math.PI) / 2) * 2 ** level),
  ];
};

type Tile = ReturnType<typeof mapTiles>[number];

const covers = (tiles: Tile[], [x, y]: number[]) =>
  tiles.some(
    tile => tile.x <= x && x < tile.x + tile.size && tile.y <= y && y < tile.y + tile.size,
  );

/** Whether every tile shows up in the drawing area [left, top, right, bottom], so none is fetched in vain. */
const allVisible = (tiles: Tile[], [left, top, right, bottom]: number[]) =>
  tiles.every(
    tile =>
      tile.x < right && tile.x + tile.size > left && tile.y < bottom && tile.y + tile.size > top,
  );

const level = (tile: Tile) => Number(tile.url.split('/')[3]);

test('map tiles cover the whole visible map, close to their own size, and nothing else', () => {
  const layout = geographicLayout(recife, [], 900, 520);
  const tiles = mapTiles(layout, { zoom: 1, offset: [0, 0] });

  for (const tile of tiles) {
    assert.ok(tile.size >= 128 && tile.size <= 512, `tile drawn at ${tile.size}px`);
    assert.match(tile.url, /^https:\/\/tile\.openstreetmap\.org\/\d+\/\d+\/\d+\.png$/);
  }
  for (const corner of [
    [0, 0],
    [899, 0],
    [0, 519],
    [899, 519],
    [450, 260],
  ]) {
    assert.ok(covers(tiles, corner), `nothing covers ${corner}`);
  }
  assert.ok(allVisible(tiles, [0, 0, 900, 520]));
});

test('the tile drawn under a stop is the OpenStreetMap tile of its coordinates', () => {
  const layout = geographicLayout(recife, [], 900, 520);
  const [x, y] = layout.position([-34.871, -8.063]);
  const tile = mapTiles(layout, { zoom: 1, offset: [0, 0] }).find(item => covers([item], [x, y]));

  assert.ok(tile);
  assert.equal(
    tile.url.split('/').slice(4).join('/'),
    `${osmTile(-34.871, -8.063, level(tile)).join('/')}.png`,
  );
});

test('zooming in loads more detailed tiles', () => {
  const layout = geographicLayout(recife, [], 900, 520);

  assert.equal(
    level(mapTiles(layout, { zoom: 4, offset: [0, 0] })[0]),
    level(mapTiles(layout, { zoom: 1, offset: [0, 0] })[0]) + 2,
  );
});

test('panning the map loads the tiles of the area moved into view, and only those', () => {
  const layout = geographicLayout(recife, [], 900, 520);
  const tiles = mapTiles(layout, { zoom: 1, offset: [-600, -400] });

  for (const corner of [
    [600, 400],
    [1499, 400],
    [600, 919],
    [1499, 919],
  ]) {
    assert.ok(covers(tiles, corner), `the panned view lacks ${corner}`);
  }
  assert.ok(allVisible(tiles, [600, 400, 1500, 920]));
});

test('a map label goes on the side facing the centre, else the other side, else it is hidden until zoomed in', () => {
  const point = (x: number, y: number, text: string) => ({ x, y, text, fontSize: 12 });
  const crowded = [point(100, 100, 'Estação Recife'), point(110, 104, 'Recife Antigo')];
  const visible = { left: 0, right: 900 };

  assert.deepEqual(placeLabels([point(100, 100, 'Pina')], 1, visible), ['right']);
  assert.deepEqual(placeLabels([point(800, 100, 'Pina')], 1, visible), ['left']);
  assert.deepEqual(placeLabels(crowded, 1, visible), ['right', null]);
  assert.deepEqual(placeLabels(crowded, 4, visible), ['right', 'right']);
  assert.deepEqual(
    placeLabels([point(500, 100, 'Afogados'), point(430, 100, 'Boa Vista')], 1, visible),
    ['right', 'left'],
  );
});

test('a map label stays inside the visible part of the map, or waits for the zoom', () => {
  const point = (x: number, text: string) => ({ x, y: 100, text, fontSize: 12 });
  const nearTheEdge = [point(40, 'Terminal Integrado CDU'), point(90, 'Pina')];

  assert.deepEqual(placeLabels(nearTheEdge, 1, { left: 0, right: 390 }), [null, 'right']);
  assert.deepEqual(placeLabels(nearTheEdge, 4, { left: 0, right: 390 }), ['right', 'right']);
});
