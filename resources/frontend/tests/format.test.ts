import { strict as assert } from 'node:assert';
import test from 'node:test';
import { distance, initials, money, shortDistance, stepFare } from '../lib/format.ts';

test('fares in cents are shown as Brazilian reais', () => {
  assert.equal(money(380), 'R$ 3,80');
  assert.equal(money(0), 'R$ 0,00');
  assert.equal(money(123456), 'R$ 1.234,56');
});

test('distances in meters are shown in kilometers with at most one decimal', () => {
  assert.equal(distance(4200), '4,2 km');
  assert.equal(distance(550), '0,6 km');
  assert.equal(distance(8000), '8 km');
});

test('short distances are shown in meters, rounded to tens, and longer ones in kilometers', () => {
  assert.equal(shortDistance(0), '0 m');
  assert.equal(shortDistance(284.6), '280 m');
  assert.equal(shortDistance(995), '1 km');
  assert.equal(shortDistance(1450), '1,5 km');
});

test('a step says what it charges, and a fare-integrated one only the difference', () => {
  assert.equal(stepFare({ cost_cents: 380, fare_integrated: false }), 'Tarifa de R$ 3,80.');
  assert.equal(
    stepFare({ cost_cents: 0, fare_integrated: true }),
    'Integração tarifária, sem nova tarifa.',
  );
  assert.equal(
    stepFare({ cost_cents: 220, fare_integrated: true }),
    'Integração tarifária, com R$ 2,20 de diferença.',
  );
  assert.equal(stepFare({ cost_cents: 0, fare_integrated: false }), null);
  assert.equal(stepFare({ cost_cents: 0 }), null);
});

test('an account is shown by the initials of its first and last names', () => {
  assert.equal(initials('Erlon Moura'), 'EM');
  assert.equal(initials('  ana   de souza lima '), 'AL');
  assert.equal(initials('Érica'), 'É');
  assert.equal(initials(''), '');
});
