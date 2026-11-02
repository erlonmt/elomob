'use client';

import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useState } from 'react';
import { useApiData, useApplication } from '@/components/providers';
import { RouteMap } from '@/components/route-map';
import { DemoNote, Empty, Icon, Loading, Notice, PageHeader } from '@/components/ui';
import { errorMessage } from '@/lib/api';
import { nearestStop } from '@/lib/location';
import type { Itinerary, Stop } from '@/lib/types';

export function MapScreen() {
  const { catalog, loading, catalogError } = useApplication();
  const params = useSearchParams();
  const [mode, setMode] = useState(params.get('mode') ?? '');
  const [query, setQuery] = useState(params.get('q') ?? '');
  const [selected, setSelected] = useState<Stop | null>(null);
  const [locationError, setLocationError] = useState('');
  const [locating, setLocating] = useState(false);
  const routeId = params.get('trajeto');
  const {
    data: itinerary,
    loading: routeLoading,
    error: routeError,
  } = useApiData<Itinerary>(routeId ? `/trajetos/${encodeURIComponent(routeId)}` : null);
  const points = catalog.stops.filter(
    stop =>
      (!mode || stop.mode === mode) &&
      `${stop.name} ${stop.address}`
        .toLocaleLowerCase('pt-BR')
        .includes(query.toLocaleLowerCase('pt-BR')),
  );

  return (
    <>
      <PageHeader
        title={routeId ? 'Seu trajeto no mapa' : 'Mapa de mobilidade'}
        subtitle="Explore estações, paradas e conexões."
        back={routeId ? `/trajetos/${routeId}` : '/'}
      />
      <div className="map-page content">
        <section className="map-main">
          <div className="map-toolbar">
            <label className="field white">
              <Icon name="search" />
              <input
                type="search"
                aria-label="Buscar estação ou parada"
                placeholder="Estações e paradas..."
                value={query}
                onChange={event => setQuery(event.target.value)}
              />
            </label>
            <button
              type="button"
              className="small-outline gps-map"
              disabled={locating}
              onClick={async () => {
                setLocating(true);
                setLocationError('');
                try {
                  const stop = await nearestStop();
                  setSelected(stop);
                  setMode('');
                  setQuery('');
                } catch (error) {
                  setLocationError(errorMessage(error));
                } finally {
                  setLocating(false);
                }
              }}
            >
              {locating ? 'Localizando...' : 'Usar minha localização'}
            </button>
          </div>
          <div className="chips map-filters">
            <button
              className={`pill ${!mode ? 'selected' : ''}`}
              onClick={() => {
                setMode('');
                setSelected(null);
              }}
            >
              Todos
            </button>
            {catalog.modes.map(item => (
              <button
                className={`pill ${mode === item.code ? 'selected' : ''}`}
                key={item.id}
                onClick={() => {
                  setMode(item.code);
                  setSelected(null);
                }}
              >
                {item.name}
              </button>
            ))}
          </div>
          <Notice>{catalogError || routeError || locationError}</Notice>
          {loading || routeLoading ? (
            <Loading label="Carregando mapa..." />
          ) : (
            <RouteMap
              stops={points}
              steps={itinerary?.steps}
              geojson={itinerary?.geojson}
              selectedId={selected?.id}
              onSelect={setSelected}
            />
          )}
          <div className="legend">
            <h2>Legenda de Transportes</h2>
            <div>
              {catalog.modes.map(item => (
                <span key={item.id}>
                  <i style={{ background: item.color }} />
                  {item.name}
                </span>
              ))}
            </div>
          </div>
          <p className="map-help">
            Arraste o mapa ou use as setas do teclado para mover. Use + e − para ajustar o zoom.
          </p>
          <DemoNote />
        </section>
        <aside className="map-results">
          <div className="section-heading">
            <h2>{selected ? 'Local selecionado' : 'Locais cadastrados'}</h2>
            <span className="badge teal">{points.length} locais</span>
          </div>
          {selected && (
            <article className="selected-stop card">
              <button className="text-button" onClick={() => setSelected(null)}>
                Fechar seleção ×
              </button>
              <h2>{selected.name}</h2>
              <p>{selected.address}</p>
              {selected.bikes_available !== null && (
                <p>
                  {selected.bikes_available} bicicletas · {selected.docks_available} vagas
                </p>
              )}
              <Link href={`/buscar?destination_id=${selected.id}`} className="button">
                Ir para este local
              </Link>
            </article>
          )}
          <div className="stack">
            {points.map(stop => (
              <button
                key={stop.id}
                type="button"
                className={`nearby-card point-card ${selected?.id === stop.id ? 'selected' : ''}`}
                onClick={() => setSelected(stop)}
              >
                <span className="icon-tile">
                  <Icon name={stop.mode ?? 'pin'} />
                </span>
                <span className="card-info">
                  <strong>{stop.name}</strong>
                  <small>
                    {stop.mode_name} · {stop.address}
                  </small>
                  {stop.bikes_available !== null && (
                    <small>
                      {stop.bikes_available} bicicletas · {stop.docks_available} vagas
                    </small>
                  )}
                </span>
                <Icon name="arrow" />
              </button>
            ))}
          </div>
          {!loading && !points.length && (
            <Empty title="Nenhum local encontrado">Tente outro nome ou modo de transporte.</Empty>
          )}
        </aside>
      </div>
    </>
  );
}
