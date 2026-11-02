'use client';

import Link from 'next/link';
import { useApiData } from '@/components/providers';
import { RouteMap } from '@/components/route-map';
import { DemoNote, Icon, Loading, Notice, PageHeader } from '@/components/ui';
import { accessibilityNeeds } from '@/lib/accessibility';
import { distance, money, stepFare } from '@/lib/format';
import type { Itinerary } from '@/lib/types';

/** Accessibility the step offers, one badge per need it meets, and a warning when it has stairs. */
function StepAccessibility({ meets, hasStairs }: { meets: string[]; hasStairs: boolean }) {
  const needs = accessibilityNeeds.filter(need => meets.includes(need.code));
  if (!needs.length && !hasStairs) {
    return null;
  }
  return (
    <div className="badges step-access" role="list" aria-label="Acessibilidade do trecho">
      {needs.map(need => (
        <span className="badge teal" role="listitem" key={need.code}>
          <Icon name={need.icon} />
          {need.stepLabel}
        </span>
      ))}
      {hasStairs && (
        <span className="badge warning" role="listitem">
          Tem escada
        </span>
      )}
    </div>
  );
}

export function DetailScreen({ id }: { id: string }) {
  const { data: itinerary, loading, error, reload } = useApiData<Itinerary>(`/trajetos/${id}`);
  const search = itinerary?.search;
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
                  <ol>
                    {(itinerary.steps ?? []).map((step, index) => (
                      <li key={step.id ?? index}>
                        <span className="step-icon">
                          <Icon name={`${step.mode}-small`} />
                        </span>
                        <div>
                          <div className="split">
                            <strong>
                              {step.mode_name} {step.line_code}
                            </strong>
                            <small>{step.minutes} min</small>
                          </div>
                          <p>
                            {step.origin} → {step.destination}
                          </p>
                          {Boolean(step.transfer_minutes) && (
                            <small>Inclui {step.transfer_minutes} min de integração</small>
                          )}
                          {Boolean(step.wait_minutes) && (
                            <small>Inclui {step.wait_minutes} min de espera estimada</small>
                          )}
                          {stepFare(step) !== null && <small>{stepFare(step)}</small>}
                          <StepAccessibility
                            meets={step.accessible_for ?? []}
                            hasStairs={Boolean(step.has_stairs)}
                          />
                        </div>
                      </li>
                    ))}
                  </ol>
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
