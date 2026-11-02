import type { Itinerary } from './types.ts';

export type RouteOption = { itinerary: Itinerary; criteria: string[] };

/**
 * A search keeps one itinerary per criterion, and different criteria often choose the same route
 * (the taxi is both the fastest and the one with fewest transfers). Each route is shown once,
 * with every criterion that chose it, in the order the API returned them.
 */
export function routeOptions(itineraries: Itinerary[]): RouteOption[] {
  const options = new Map<string, RouteOption>();
  for (const itinerary of itineraries) {
    const steps = (itinerary.steps ?? []).map(
      step =>
        step.connection_id ?? `${step.mode}:${step.line_code}:${step.origin}:${step.destination}`,
    );
    const route = [itinerary.total_minutes, itinerary.total_cost_cents, ...steps].join('|');
    const option = options.get(route);
    if (option) {
      option.criteria.push(itinerary.criterion);
    } else {
      options.set(route, { itinerary, criteria: [itinerary.criterion] });
    }
  }
  return [...options.values()];
}
