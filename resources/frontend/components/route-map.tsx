'use client';

import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { geographicLayout, mapTiles, placeLabels, routeFeatures } from '@/lib/geo';
import type { GeoJSON, Step, Stop } from '@/lib/types';

const modeColors: Record<string, string> = {
  bus: '#028090',
  metro: '#3f51b5',
  bike: '#259b84',
  taxi: '#bf8319',
  app: '#f07167',
  walk: '#526b74',
};

/** No stops, as a constant so the default does not change on every render. */
const NO_STOPS: Stop[] = [];
const NO_STEPS: Step[] = [];

/** How much a trip point's label matters: the origin and final destination more than the changes between. */
const endpointRank = (index: number, count: number) => (index === 0 || index === count - 1 ? 1 : 0);

export function RouteMap({
  stops = NO_STOPS,
  steps = NO_STEPS,
  geojson,
  selectedId,
  onSelect,
  compact = false,
}: {
  stops?: Stop[];
  steps?: Step[];
  geojson?: GeoJSON;
  selectedId?: number;
  onSelect?: (stop: Stop) => void;
  compact?: boolean;
}) {
  const [zoom, setZoom] = useState(1);
  const [offset, setOffset] = useState<[number, number]>([0, 0]);
  const [viewport, setViewport] = useState({ width: 900, height: compact ? 300 : 520 });
  const container = useRef<HTMLDivElement>(null);
  const drag = useRef<{ x: number; y: number; offset: [number, number] } | null>(null);
  const id = useId().replaceAll(':', '');
  const features = useMemo(() => routeFeatures(steps, geojson), [steps, geojson]);
  const visibleStops = useMemo(() => (features.length ? NO_STOPS : stops), [features, stops]);
  const layout = useMemo(
    () => geographicLayout(visibleStops, features, viewport.width, viewport.height),
    [visibleStops, features, viewport],
  );
  const tiles = useMemo(() => mapTiles(layout, { zoom, offset }), [layout, zoom, offset]);
  useEffect(() => {
    if (!container.current) {
      return;
    }
    const observer = new ResizeObserver(([entry]) => {
      setViewport({ width: entry.contentRect.width, height: entry.contentRect.height });
    });
    observer.observe(container.current);
    return () => observer.disconnect();
  }, []);
  const endpoints = features.flatMap((feature, index) => {
    const coordinates = feature.geometry.coordinates;
    return [
      ...(index === 0
        ? [
            {
              name: feature.properties.origin ?? steps[0]?.origin ?? 'Origem',
              position: coordinates[0],
              origin: true,
            },
          ]
        : []),
      {
        name: feature.properties.destination ?? steps[index]?.destination ?? 'Destino',
        position: coordinates[coordinates.length - 1],
        origin: false,
      },
    ];
  });
  // The part of the map on screen, in map units: the map is scaled around its centre and then moved by the offset.
  const visibleRange = {
    left: (0 - viewport.width / 2 - offset[0]) / zoom + viewport.width / 2,
    right: (viewport.width / 2 - offset[0]) / zoom + viewport.width / 2,
  };
  const stopPoints = visibleStops
    .filter(
      stop => Number.isFinite(Number(stop.latitude)) && Number.isFinite(Number(stop.longitude)),
    )
    .map(stop => ({
      stop,
      position: layout.position([Number(stop.longitude), Number(stop.latitude)]),
    }))
    .sort(
      (first, second) =>
        Number(second.stop.id === selectedId) - Number(first.stop.id === selectedId),
    );
  const stopSides = placeLabels(
    stopPoints.map(({ stop, position: [x, y] }) => ({ x, y, text: stop.name, fontSize: 12 })),
    zoom,
    visibleRange,
  );
  const placedStops = stopPoints.map((point, index) => ({ ...point, side: stopSides[index] }));
  // The origin and the final destination are labelled first, then the stops where the trip changes.
  const endpointOrder = endpoints
    .map((_, index) => index)
    .sort(
      (first, second) =>
        endpointRank(second, endpoints.length) - endpointRank(first, endpoints.length),
    );
  const orderedSides = placeLabels(
    endpointOrder.map(index => {
      const [x, y] = layout.position(endpoints[index].position);
      return { x, y, text: endpoints[index].name, fontSize: 13 };
    }),
    zoom,
    visibleRange,
  );
  const endpointSides = endpoints.map((_, index) => orderedSides[endpointOrder.indexOf(index)]);
  const move = (x: number, y: number) => setOffset(previous => [previous[0] + x, previous[1] + y]);

  return (
    <div ref={container} className={`geographic-map ${compact ? 'compact-map' : ''}`}>
      <svg
        viewBox={`0 0 ${viewport.width} ${viewport.height}`}
        className="geographic-canvas"
        role={onSelect ? 'group' : 'img'}
        aria-label={
          features.length
            ? 'Trajeto calculado sobre as coordenadas geográficas cadastradas'
            : 'Mapa geográfico das paradas e estações cadastradas'
        }
        tabIndex={0}
        onKeyDown={event => {
          const movements: Record<string, [number, number]> = {
            ArrowLeft: [30, 0],
            ArrowRight: [-30, 0],
            ArrowUp: [0, 30],
            ArrowDown: [0, -30],
          };
          if (movements[event.key]) {
            event.preventDefault();
            move(...movements[event.key]);
          } else if (event.key === '+' || event.key === '=') {
            setZoom(value => Math.min(5, value + 0.5));
          } else if (event.key === '-') {
            setZoom(value => Math.max(1, value - 0.5));
          }
        }}
        onPointerDown={event => {
          if ((event.target as Element).closest('[data-stop]')) {
            return;
          }
          event.currentTarget.setPointerCapture(event.pointerId);
          drag.current = { x: event.clientX, y: event.clientY, offset };
        }}
        onPointerMove={event => {
          if (drag.current) {
            const scale = viewport.width / event.currentTarget.getBoundingClientRect().width;
            setOffset([
              drag.current.offset[0] + (event.clientX - drag.current.x) * scale,
              drag.current.offset[1] + (event.clientY - drag.current.y) * scale,
            ]);
          }
        }}
        onPointerUp={() => {
          drag.current = null;
        }}
        onPointerCancel={() => {
          drag.current = null;
        }}
      >
        <title>{features.length ? 'Trajeto calculado' : 'Rede de mobilidade do Recife'}</title>
        <defs>
          <pattern id={`grid-${id}`} width="45" height="45" patternUnits="userSpaceOnUse">
            <path d="M45 0H0V45" fill="none" stroke="#d8e8e3" strokeWidth="1" />
          </pattern>
          <pattern id={`dots-${id}`} width="9" height="9" patternUnits="userSpaceOnUse">
            <circle cx="1" cy="1" r=".7" fill="#ddeae5" />
          </pattern>
        </defs>
        <rect width={viewport.width} height={viewport.height} fill="#edf4ee" />
        <rect width={viewport.width} height={viewport.height} fill={`url(#dots-${id})`} />
        <rect width={viewport.width} height={viewport.height} fill={`url(#grid-${id})`} />
        <g
          transform={`translate(${offset[0]} ${offset[1]}) translate(${viewport.width / 2} ${viewport.height / 2}) scale(${zoom}) translate(${-viewport.width / 2} ${-viewport.height / 2})`}
        >
          <g className="map-tiles" aria-hidden="true">
            {tiles.map(tile => (
              <image
                key={tile.key}
                href={tile.url}
                x={tile.x}
                y={tile.y}
                width={tile.size + 0.5}
                height={tile.size + 0.5}
                preserveAspectRatio="none"
              />
            ))}
          </g>
          {features.map((feature, index) => {
            const points = feature.geometry.coordinates
              .map(position => layout.position(position).join(','))
              .join(' ');
            const color =
              feature.properties.color ??
              modeColors[feature.properties.mode ?? 'bus'] ??
              modeColors.bus;
            return (
              <g key={index}>
                <polyline
                  points={points}
                  fill="none"
                  stroke="white"
                  strokeWidth="12"
                  vectorEffect="non-scaling-stroke"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
                <polyline
                  points={points}
                  fill="none"
                  stroke={color}
                  strokeWidth="6"
                  vectorEffect="non-scaling-stroke"
                  strokeDasharray={feature.properties.mode === 'walk' ? '7 9' : undefined}
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </g>
            );
          })}
          {placedStops.map(({ stop, position: [x, y], side }) => {
            const selected = selectedId === stop.id;
            return (
              <g
                data-stop="true"
                key={stop.id}
                transform={`translate(${x} ${y}) scale(${1 / zoom})`}
                role={onSelect ? 'button' : undefined}
                aria-label={stop.name}
                tabIndex={onSelect ? 0 : undefined}
                onClick={() => onSelect?.(stop)}
                onKeyDown={event => {
                  if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    onSelect?.(stop);
                  }
                }}
                className="map-point"
              >
                <circle
                  r={selected ? 20 : 13}
                  fill={selected ? '#e0f2f1' : 'white'}
                  stroke="white"
                  strokeWidth="5"
                />
                <circle
                  r={selected ? 13 : 9}
                  fill={stop.color ?? modeColors[stop.mode ?? 'bus'] ?? modeColors.bus}
                />
                {side && (
                  <text
                    x={side === 'left' ? -19 : 19}
                    textAnchor={side === 'left' ? 'end' : 'start'}
                    y="-13"
                    fontSize="12"
                    fontWeight={selected ? '700' : '500'}
                    fill="#11222c"
                    stroke="#edf4ee"
                    strokeWidth="5"
                    paintOrder="stroke"
                    strokeLinejoin="round"
                  >
                    {stop.name}
                  </text>
                )}
              </g>
            );
          })}
          {endpoints.map((endpoint, index) => {
            const [x, y] = layout.position(endpoint.position);
            const side = endpointSides[index];
            return (
              <g key={index} transform={`translate(${x} ${y}) scale(${1 / zoom})`}>
                <circle
                  r={endpoint.origin ? 11 : 9}
                  fill={endpoint.origin ? '#028090' : '#f07167'}
                  stroke="white"
                  strokeWidth="4"
                />
                {side && (
                  <text
                    x={side === 'left' ? -19 : 19}
                    textAnchor={side === 'left' ? 'end' : 'start'}
                    y="-13"
                    fill="#11222c"
                    fontSize="13"
                    fontWeight="700"
                    stroke="#edf4ee"
                    strokeWidth="5"
                    paintOrder="stroke"
                  >
                    {endpoint.name}
                  </text>
                )}
              </g>
            );
          })}
        </g>
        <g transform={`translate(${viewport.width - 28} 47)`}>
          <path d="M0 -15L-6 4L0 0L6 4Z" fill="#526b74" />
          <text y="-23" textAnchor="middle" fill="#526b74" fontSize="12">
            N
          </text>
        </g>
      </svg>
      <span className="map-label">
        Recife, PE <span>· Rede demonstrativa</span>
      </span>
      <div className="map-controls" aria-label="Controles do mapa">
        <button
          type="button"
          aria-label="Aproximar mapa"
          onClick={() => setZoom(value => Math.min(5, value + 0.5))}
        >
          +
        </button>
        <button
          type="button"
          aria-label="Afastar mapa"
          disabled={zoom === 1}
          onClick={() => setZoom(value => Math.max(1, value - 0.5))}
        >
          −
        </button>
        <button
          type="button"
          aria-label="Centralizar mapa"
          onClick={() => {
            setZoom(1);
            setOffset([0, 0]);
          }}
        >
          ⌖
        </button>
      </div>
      <span className="map-caption">
        Mapa ©{' '}
        <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">
          colaboradores do OpenStreetMap
        </a>{' '}
        · conexões esquemáticas
      </span>
    </div>
  );
}
