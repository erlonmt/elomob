import type { Step } from './types.ts';

/** Format an amount in cents as Brazilian reais, e.g. "R$ 3,80". */
export const money = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);

/** Format meters as kilometers with up to one decimal, e.g. "4,2 km". */
export const distance = (meters: number) =>
  `${new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 }).format(meters / 1000)} km`;

/** The initials of the first and last names, in capitals, e.g. "EM" for "Erlon Moura". */
export function initials(name: string) {
  const words = name
    .trim()
    .split(/\s+/)
    .filter(word => word !== '');
  const chosen = words.length > 1 ? [words[0], words[words.length - 1]] : words;
  return chosen.map(word => Array.from(word)[0].toLocaleUpperCase('pt-BR')).join('');
}

/**
 * Describe what a step charges: its fare, or for a fare-integrated change only the difference up to the
 * higher fare. Steps that charge nothing outside a fare integration (walking, staying on board) say nothing.
 */
export function stepFare({
  cost_cents,
  fare_integrated,
}: Pick<Step, 'cost_cents' | 'fare_integrated'>) {
  if (fare_integrated) {
    return cost_cents > 0
      ? `Integração tarifária, com ${money(cost_cents)} de diferença.`
      : 'Integração tarifária, sem nova tarifa.';
  }
  return cost_cents > 0 ? `Tarifa de ${money(cost_cents)}.` : null;
}
