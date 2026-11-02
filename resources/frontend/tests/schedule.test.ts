import { strict as assert } from 'node:assert';
import test from 'node:test';
import { recifeTime, serviceHoursNotice } from '../lib/schedule.ts';

const lines = [
  { mode: 'bus', starts_at: '05:00:00', ends_at: '23:00:00' },
  { mode: 'metro', starts_at: '05:30:00', ends_at: '22:30:00' },
];

test('no notice while a line of the chosen modes runs', () => {
  assert.equal(serviceHoursNotice(lines, ['bus', 'metro'], '14:30'), null);
  assert.equal(serviceHoursNotice(lines, ['metro'], '05:30'), null);
});

test('outside the service hours the notice gives them and suggests a ride', () => {
  assert.equal(
    serviceHoursNotice(lines, ['bus', 'metro'], '23:30'),
    'Nenhuma linha de ônibus ou metrô funciona às 23:30: elas operam das 05:00 às 23:00. Escolha outro horário ou inclua táxi ou aplicativo.',
  );
  assert.equal(
    serviceHoursNotice(lines, ['metro', 'taxi'], '04:10'),
    'Nenhuma linha de ônibus ou metrô funciona às 04:10: elas operam das 05:30 às 22:30. O táxi continua disponível.',
  );
  assert.match(
    serviceHoursNotice(lines, ['bus', 'taxi', 'app'], '23:30') ?? '',
    /Táxi e aplicativo continuam disponíveis\.$/,
  );
  assert.match(
    serviceHoursNotice(lines, ['bus', 'app'], '23:30') ?? '',
    /O aplicativo continua disponível\.$/,
  );
});

test('no notice without a scheduled mode, and lines crossing midnight still run', () => {
  assert.equal(serviceHoursNotice(lines, ['taxi', 'app'], '23:30'), null);
  assert.equal(
    serviceHoursNotice(
      [{ mode: 'bus', starts_at: '22:00:00', ends_at: '02:00:00' }],
      ['bus'],
      '01:00',
    ),
    null,
  );
});

test('the current time is read in Recife', () => {
  assert.equal(recifeTime(new Date('2026-11-03T02:30:00Z')), '23:30');
});
