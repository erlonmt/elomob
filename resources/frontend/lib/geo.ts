import type { GeoJSON, Position, Step, Stop } from './types.ts';

export function routeFeatures(steps: Step[], geojson?: GeoJSON): GeoJSON['features'] {
  if (geojson?.features?.length) {
    return geojson.features.filter(
      feature => feature.geometry?.type === 'LineString' && feature.geometry.coordinates.length > 1,
    );
  }

  return steps.flatMap(step => {
    const positions = [
      step.origin_longitude,
      step.origin_latitude,
      step.destination_longitude,
      step.destination_latitude,
    ];
    if (
      !positions.every(
        value => value !== null && value !== undefined && Number.isFinite(Number(value)),
      )
    ) {
      return [];
    }
    return [
      {
        type: 'Feature' as const,
        geometry: {
          type: 'LineString' as const,
          coordinates: [
            [Number(step.origin_longitude), Number(step.origin_latitude)],
            [Number(step.destination_longitude), Number(step.destination_latitude)],
          ] as Position[],
        },
        properties: { mode: step.mode, origin: step.origin, destination: step.destination },
      },
    ];
  });
}

export function geographicLayout(
  stops: Stop[],
  features: GeoJSON['features'],
  width = 900,
  height = 560,
) {
  const project = ([longitude, latitude]: Position): Position => [
    (longitude * Math.PI) / 180,
    Math.log(Math.tan(Math.PI / 4 + (Math.max(-85, Math.min(85, latitude)) * Math.PI) / 360)),
  ];
  const coordinates: Position[] = [
    ...stops.map(stop => [Number(stop.longitude), Number(stop.latitude)] as Position),
    ...features.flatMap(feature => feature.geometry.coordinates),
  ];
  const validCoordinates = coordinates.filter(position => position.every(Number.isFinite));
  if (!validCoordinates.length) {
    validCoordinates.push([-34.88, -8.06], [-34.9, -8.08]);
  }
  const projected = validCoordinates.map(project);
  const minimumX = Math.min(...projected.map(([x]) => x));
  const maximumX = Math.max(...projected.map(([x]) => x));
  const minimumY = Math.min(...projected.map(([, y]) => y));
  const maximumY = Math.max(...projected.map(([, y]) => y));
  const rangeX = Math.max(maximumX - minimumX, 0.00003);
  const rangeY = Math.max(maximumY - minimumY, 0.00003);
  const scale = Math.min((width - 160) / rangeX, (height - 140) / rangeY);
  const centerX = (minimumX + maximumX) / 2;
  const centerY = (minimumY + maximumY) / 2;

  return {
    position: (coordinates: Position): Position => {
      const [x, y] = project(coordinates);
      return [(x - centerX) * scale + width / 2, height / 2 - (y - centerY) * scale];
    },
    width,
    height,
    scale,
    center: [centerX, centerY] as Position,
    bounds: {
      west: Math.min(...validCoordinates.map(([x]) => x)),
      east: Math.max(...validCoordinates.map(([x]) => x)),
      south: Math.min(...validCoordinates.map(([, y]) => y)),
      north: Math.max(...validCoordinates.map(([, y]) => y)),
    },
  };
}

const TILE_SIZE = 256;
const TILE_SERVER = 'https://tile.openstreetmap.org';
const MAXIMUM_TILE_LEVEL = 19;

export type MapTile = { key: string; url: string; x: number; y: number; size: number };

/**
 * The OpenStreetMap tiles covering the visible part of the map, placed in the drawing's coordinates so
 * they pan and zoom with it. The layout already uses the Web Mercator projection of the tiles, so a tile
 * is a square in it. The tile level follows the drawing scale times the map zoom: zooming in loads more
 * detailed tiles.
 */
export function mapTiles(
  layout: Pick<ReturnType<typeof geographicLayout>, 'width' | 'height' | 'scale' | 'center'>,
  { zoom, offset }: { zoom: number; offset: Position },
): MapTile[] {
  const { width, height, scale, center } = layout;
  const pixelsPerRadian = scale * zoom;
  const level = Math.min(
    MAXIMUM_TILE_LEVEL,
    Math.max(0, Math.round(Math.log2((pixelsPerRadian * 2 * Math.PI) / TILE_SIZE))),
  );
  const tileCount = 2 ** level;
  const span = (2 * Math.PI) / tileCount;
  const drawing = (screen: number, size: number, shift: number) =>
    size / 2 + (screen - shift - size / 2) / zoom;
  const west = center[0] + (drawing(0, width, offset[0]) - width / 2) / scale;
  const east = center[0] + (drawing(width, width, offset[0]) - width / 2) / scale;
  const north = center[1] - (drawing(0, height, offset[1]) - height / 2) / scale;
  const south = center[1] - (drawing(height, height, offset[1]) - height / 2) / scale;
  const column = (x: number) =>
    Math.min(tileCount - 1, Math.max(0, Math.floor((x + Math.PI) / span)));
  const row = (y: number) => Math.min(tileCount - 1, Math.max(0, Math.floor((Math.PI - y) / span)));

  const tiles: MapTile[] = [];
  for (let tileY = row(north); tileY <= row(south); tileY++) {
    for (let tileX = column(west); tileX <= column(east); tileX++) {
      tiles.push({
        key: `${level}/${tileX}/${tileY}`,
        url: `${TILE_SERVER}/${level}/${tileX}/${tileY}.png`,
        x: (-Math.PI + tileX * span - center[0]) * scale + width / 2,
        y: height / 2 - (Math.PI - tileY * span - center[1]) * scale,
        size: span * scale,
      });
    }
  }
  return tiles;
}

export function searchQuery(search: {
  origin_id: number;
  destination_id: number;
  filters?: { modes?: string[] };
  departure_at?: string | null;
}): string {
  const query = new URLSearchParams({
    origin_id: String(search.origin_id),
    destination_id: String(search.destination_id),
  });
  for (const mode of search.filters?.modes ?? []) {
    query.append('modes', mode);
  }
  if (search.departure_at) {
    query.set('departure_at', search.departure_at);
  }
  return query.toString();
}
