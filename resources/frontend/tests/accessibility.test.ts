import { strict as assert } from 'node:assert';
import test from 'node:test';
import { accessibilityNeeds, needTitles } from '../lib/accessibility.ts';

test('accessibility needs follow the order of the API codes', () => {
  assert.deepEqual(
    accessibilityNeeds.map(need => need.code),
    ['wheelchair', 'reduced_mobility', 'visual', 'hearing'],
  );
});

test('need titles follow the catalog order and skip unknown codes', () => {
  assert.deepEqual(needTitles(['visual', 'wheelchair', 'x']), [
    'Cadeira de rodas',
    'Deficiência visual',
  ]);
  assert.deepEqual(needTitles([]), []);
});
