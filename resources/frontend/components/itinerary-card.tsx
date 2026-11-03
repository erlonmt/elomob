'use client';

import Link from 'next/link';
import { distance, money } from '@/lib/format';
import { arrivalLabel } from '@/lib/schedule';
import type { Itinerary } from '@/lib/types';
import { Icon } from './ui';

const criterionLabels: Record<string, { label: string; icon: string }> = {
  fastest: { label: 'Mais rápida', icon: 'fastest' },
  cheapest: { label: 'Mais econômica', icon: 'cheapest' },
  transfers: { label: 'Menos transferências', icon: 'integration' },
  sustainable: { label: 'Mais sustentável', icon: 'sustainable' },
};

export function ItineraryCard({
  itinerary,
  criteria = [itinerary.criterion],
  departure = null,
}: {
  itinerary: Itinerary;
  criteria?: string[];
  /** When the trip leaves, to tell when it arrives. */
  departure?: Date | null;
}) {
  const uniqueSteps = (itinerary.steps ?? []).filter(
    (step, index, steps) =>
      steps.findIndex(
        item => `${item.mode}${item.line_code}` === `${step.mode}${step.line_code}`,
      ) === index,
  );
  return (
    <Link href={`/trajetos/${itinerary.id}`} className="result-card">
      <div className="split">
        <span className="badges">
          {criteria.map(key => {
            const criterion = criterionLabels[key] ?? criterionLabels.fastest;
            return (
              <span className="badge teal" key={key}>
                <Icon name={criterion.icon} />
                {criterion.label}
              </span>
            );
          })}
        </span>
        <small>Estimativa</small>
      </div>
      <div className="split metrics">
        <strong>
          {itinerary.total_minutes}
          <span> min</span>
        </strong>
        <b>{money(itinerary.total_cost_cents)}</b>
      </div>
      {departure && <p className="arrival">{arrivalLabel(departure, itinerary.total_minutes)}</p>}
      <strong className="route-description">
        {uniqueSteps
          .map(step => `${step.mode_name}${step.line_code ? ` ${step.line_code}` : ''}`)
          .join(' → ')}
      </strong>
      <p>
        {distance(itinerary.total_distance_meters)} · {itinerary.transfers}{' '}
        {itinerary.transfers === 1 ? 'integração' : 'integrações'}
      </p>
      <div className="result-bottom">
        <span className="inline">
          {uniqueSteps.map((step, index) => (
            <span className="mini-icon" key={index}>
              <Icon name={`${step.mode}-small`} />
            </span>
          ))}
        </span>
        <span>
          Ver detalhes <Icon name="arrow" />
        </span>
      </div>
    </Link>
  );
}
