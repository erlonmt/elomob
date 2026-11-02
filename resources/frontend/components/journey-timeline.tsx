import type { CSSProperties } from 'react';
import { Icon } from '@/components/ui';
import { accessibilityNeeds } from '@/lib/accessibility';
import { stepFare } from '@/lib/format';
import { readableTextOn, type JourneyLeg } from '@/lib/journey';

const listOfStops = new Intl.ListFormat('pt-BR', { type: 'conjunction' });

/** Accessibility the leg offers, one badge per need it meets, and a warning when it has stairs. */
function LegAccessibility({ meets, hasStairs }: { meets: string[]; hasStairs: boolean }) {
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

/** When to board: after the transfer and the wait, when there are any. */
function boarding(leg: JourneyLeg): string | null {
  const before = [
    leg.transferMinutes ? `troca de ${leg.transferMinutes} min` : null,
    leg.waitMinutes ? `espera de ${leg.waitMinutes} min` : null,
  ].filter(part => part !== null);
  if (leg.boardTime === null) {
    const waiting = listOfStops.format(before);
    return before.length
      ? `${waiting[0].toUpperCase()}${waiting.slice(1)} antes de embarcar.`
      : null;
  }
  return `Embarque às ${leg.boardTime}${before.length ? `, depois de ${listOfStops.format(before)}` : ''}.`;
}

/**
 * The trip drawn as a transit line: the clock time at each stop, each leg in the color of its service with the
 * line number on a plate, interchanges marked as on a metro map and walks dotted.
 */
export function JourneyTimeline({ legs }: { legs: JourneyLeg[] }) {
  const last = legs.at(-1);
  return (
    <ol className={`journey${legs[0]?.from.time ? '' : ' untimed'}`}>
      {legs.map((leg, index) => {
        const walking = leg.mode === 'walk';
        const fare = stepFare({ cost_cents: leg.costCents, fare_integrated: leg.fareIntegrated });
        const plate = leg.lineCode ?? (walking ? null : leg.modeName);
        const board = walking ? null : boarding(leg);
        return (
          <li
            key={index}
            className={`journey-leg${walking ? ' on-foot' : ''}`}
            style={
              {
                '--line-color': leg.color,
                '--plate-ink': readableTextOn(leg.color),
              } as CSSProperties
            }
          >
            {leg.from.time && <time>{leg.from.time}</time>}
            <span className={`journey-node${index === 0 ? ' start' : ' interchange'}`} />
            <strong className="journey-stop">{leg.from.name}</strong>
            <div className="journey-ride">
              <p className="journey-instruction">
                {plate && <span className="line-plate">{plate}</span>}
                {plate && ' '}
                {walking
                  ? `A pé até ${leg.to.name}`
                  : `${leg.lineCode ? `${leg.modeName} ` : ''}até ${leg.to.name}`}
              </p>
              {board && <p>{board}</p>}
              <p>
                {walking ? `${leg.travelMinutes} min a pé.` : `${leg.travelMinutes} min de viagem.`}
                {leg.passes.length > 0 && ` Passa por ${listOfStops.format(leg.passes)}.`}
              </p>
              {fare && <p className="journey-fare">{fare}</p>}
              <LegAccessibility meets={leg.accessibleFor} hasStairs={leg.hasStairs} />
            </div>
          </li>
        );
      })}
      {last && (
        <li className="journey-leg journey-end">
          {last.to.time && <time>{last.to.time}</time>}
          <span className="journey-node end" />
          <strong className="journey-stop">{last.to.name}</strong>
        </li>
      )}
    </ol>
  );
}
