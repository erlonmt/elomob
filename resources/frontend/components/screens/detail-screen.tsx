'use client';

import Link from 'next/link';
import { useApiData } from '@/components/providers';
import { RouteMap } from '@/components/route-map';
import { JourneyTimeline } from '@/components/journey-timeline';
import { DemoNote, Loading, Notice, PageHeader } from '@/components/ui';
import { distance, money } from '@/lib/format';
import { journeyLegs } from '@/lib/journey';
import type { Itinerary } from '@/lib/types';

export function DetailScreen({ id }: { id: string }) {
  const { data: itinerary, loading, error, reload } = useApiData<Itinerary>(`/trajetos/${id}`);
  const search = itinerary?.search;
  const departure = search?.departure_at ?? search?.created_at;
  return (
    <>
      <PageHeader
        title="Detalhes do Trajeto"
        subtitle={
          search ? `${search.origin} → ${search.destination}` : 'Seu caminho, passo a passo.'
        }
        back={search ? `/resultados/${search.id}` : '/buscar'}
      />
      <div className="content">
        <Notice>{error}</Notice>
        {error && (
          <button className="text-button" onClick={reload}>
            Tentar novamente
          </button>
        )}
        {loading ? (
          <Loading />
        ) : (
          itinerary && (
            <>
              <div className="detail-layout">
                <section className="detail-map-panel">
                  <div className="section-heading">
                    <h2>Visualização da Rota</h2>
                    <Link href={`/mapa?trajeto=${itinerary.id}`}>Abrir mapa</Link>
                  </div>
                  <RouteMap steps={itinerary.steps ?? []} geojson={itinerary.geojson} compact />
                  <div className="journey-summary card">
                    <div>
                      <small>Duração total</small>
                      <strong>{itinerary.total_minutes} min</strong>
                    </div>
                    <div>
                      <small>Custo estimado</small>
                      <strong className="teal-text">{money(itinerary.total_cost_cents)}</strong>
                    </div>
                    <div>
                      <small>Distância</small>
                      <strong>{distance(itinerary.total_distance_meters)}</strong>
                    </div>
                  </div>
                  <DemoNote />
                </section>
                <section className="steps card">
                  <h2>Instruções passo a passo</h2>
                  <JourneyTimeline
                    legs={journeyLegs(
                      itinerary.steps ?? [],
                      departure ? new Date(departure) : null,
                    )}
                  />
                  <Link className="button" href={`/mapa?trajeto=${itinerary.id}`}>
                    Ver trajeto no mapa
                  </Link>
                </section>
              </div>
            </>
          )
        )}
      </div>
    </>
  );
}
