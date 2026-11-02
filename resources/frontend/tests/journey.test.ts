import { strict as assert } from 'node:assert';
import test from 'node:test';
import { journeyLegs, readableTextOn } from '../lib/journey.ts';
import type { Step } from '../lib/types.ts';

const step = (values: Partial<Step>): Step =>
  ({
    mode: 'bus',
    mode_name: 'Ônibus',
    color: '#028090',
    line_code: null,
    minutes: 10,
    wait_minutes: 0,
    transfer_minutes: 0,
    cost_cents: 0,
    fare_integrated: false,
    distance_meters: 1000,
    has_stairs: false,
    accessible_for: [],
    ...values,
  }) as Step;

/** Boa Viagem → Recife Antigo by line 042, the Linha Centro and on foot, as the API answers it. */
const demonstrationTrip = [
  step({
    origin: 'Boa Viagem',
    destination: 'Estação Joana Bezerra',
    line_code: '042',
    minutes: 24,
    wait_minutes: 6,
    cost_cents: 380,
  }),
  step({
    origin: 'Estação Joana Bezerra',
    destination: 'Estação Recife',
    mode: 'metro',
    mode_name: 'Metrô',
    color: '#3f51b5',
    line_code: 'CENTRO',
    minutes: 16,
    transfer_minutes: 3,
    wait_minutes: 3,
    fare_integrated: true,
  }),
  step({
    origin: 'Estação Recife',
    destination: 'Recife Antigo',
    mode: 'walk',
    mode_name: 'Caminhada',
    color: '#526b74',
    minutes: 7,
  }),
];

test('each leg says when the passenger reaches its stop, boards and arrives, in Recife time', () => {
  const legs = journeyLegs(demonstrationTrip, new Date('2026-11-03T14:30:00-03:00'));

  assert.deepEqual(
    legs.map(leg => [leg.from.name, leg.from.time, leg.boardTime, leg.to.name, leg.to.time]),
    [
      ['Boa Viagem', '14:30', '14:36', 'Estação Joana Bezerra', '14:54'],
      ['Estação Joana Bezerra', '14:54', '15:00', 'Estação Recife', '15:10'],
      ['Estação Recife', '15:10', '15:10', 'Recife Antigo', '15:17'],
    ],
  );
  assert.deepEqual(
    legs.map(leg => [
      leg.transferMinutes,
      leg.waitMinutes,
      leg.travelMinutes,
      leg.costCents,
      leg.fareIntegrated,
    ]),
    [
      [0, 6, 18, 380, false],
      [3, 3, 10, 0, true],
      [0, 0, 7, 0, false],
    ],
  );
});

test('stretches in a row on the same line become one leg passing the stops between', () => {
  const legs = journeyLegs(
    [
      step({
        origin: 'Cordeiro',
        destination: 'Derby',
        line_code: '081',
        minutes: 28,
        wait_minutes: 14,
        cost_cents: 380,
        accessible_for: ['visual', 'hearing'],
      }),
      step({
        origin: 'Derby',
        destination: 'Boa Vista',
        line_code: '081',
        minutes: 8,
        accessible_for: ['visual'],
      }),
      step({
        origin: 'Boa Vista',
        destination: 'Recife Antigo',
        line_code: '081',
        minutes: 10,
        accessible_for: ['visual'],
        has_stairs: true,
      }),
    ],
    new Date('2026-11-03T14:31:00-03:00'),
  );

  assert.equal(legs.length, 1);
  assert.deepEqual(legs[0].passes, ['Derby', 'Boa Vista']);
  assert.deepEqual(
    [legs[0].boardTime, legs[0].to.name, legs[0].to.time],
    ['14:45', 'Recife Antigo', '15:17'],
  );
  assert.deepEqual([legs[0].travelMinutes, legs[0].costCents], [32, 380]);
  assert.deepEqual(legs[0].accessibleFor, ['visual']);
  assert.equal(legs[0].hasStairs, true);
});

test('another line or a walk at the same stop starts a new leg', () => {
  const legs = journeyLegs(
    [
      step({ origin: 'A', destination: 'B', line_code: '081' }),
      step({ origin: 'B', destination: 'C', line_code: 'BRT' }),
      step({ origin: 'C', destination: 'D', mode: 'walk', mode_name: 'Caminhada' }),
      step({ origin: 'D', destination: 'E', mode: 'walk', mode_name: 'Caminhada' }),
    ],
    null,
  );

  assert.equal(legs.length, 4);
  assert.deepEqual(
    legs.map(leg => leg.from.time),
    [null, null, null, null],
  );
});

test('line plates use white or dark text, whichever reads better on the line color', () => {
  assert.equal(readableTextOn('#028090'), '#ffffff');
  assert.equal(readableTextOn('#3f51b5'), '#ffffff');
  assert.equal(readableTextOn('#2ec4b6'), '#11222c');
  assert.equal(readableTextOn('#ffb300'), '#11222c');
});
