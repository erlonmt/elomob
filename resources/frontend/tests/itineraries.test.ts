import { strict as assert } from 'node:assert';
import test from 'node:test';
import { routeOptions } from '../lib/itineraries.ts';
import type { Itinerary, Step } from '../lib/types.ts';

const step = (connection_id: number) =>
  ({ connection_id, mode: 'bus', line_code: '042', origin: 'A', destination: 'B' }) as Step;
const itinerary = (
  id: number,
  criterion: string,
  connections: number[],
  total_minutes = 25,
  total_cost_cents = 380,
) =>
  ({ id, criterion, total_minutes, total_cost_cents, steps: connections.map(step) }) as Itinerary;

test('a route chosen by several criteria is shown once, with every criterion that chose it', () => {
  const options = routeOptions([
    itinerary(1, 'fastest', [7]),
    itinerary(2, 'cheapest', [11, 13], 45),
    itinerary(3, 'transfers', [7]),
    itinerary(4, 'sustainable', [11, 13], 45),
  ]);
  assert.deepEqual(
    options.map(option => [option.itinerary.id, option.criteria]),
    [
      [1, ['fastest', 'transfers']],
      [2, ['cheapest', 'sustainable']],
    ],
  );
});

test('routes that differ in their connections or totals stay separate', () => {
  const options = routeOptions([
    itinerary(1, 'fastest', [1, 2]),
    itinerary(2, 'cheapest', [1, 3]),
    itinerary(3, 'transfers', [1, 2], 40),
  ]);
  assert.equal(options.length, 3);
});
