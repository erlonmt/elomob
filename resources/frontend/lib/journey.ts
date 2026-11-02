import { recifeTime } from './schedule.ts';
import type { Step } from './types.ts';

export type JourneyStop = { name: string; time: string | null };

/**
 * One leg of a trip: a ride on one service, or a walk, from the stop where the passenger arrives to the stop where
 * they get off. Times are "HH:MM" in Recife, or null when the departure is unknown.
 */
export type JourneyLeg = {
  mode: string;
  modeName: string;
  color: string;
  lineCode: string | null;
  from: JourneyStop;
  boardTime: string | null;
  to: JourneyStop;
  transferMinutes: number;
  waitMinutes: number;
  travelMinutes: number;
  costCents: number;
  fareIntegrated: boolean;
  passes: string[];
  accessibleFor: string[];
  hasStairs: boolean;
};

const INK = '#11222c';
const WHITE = '#ffffff';

/** The WCAG relative luminance of a "#rrggbb" color. */
function luminance(hex: string): number {
  const channels = [1, 3, 5].map(start => Number.parseInt(hex.slice(start, start + 2), 16) / 255);
  const [red, green, blue] = channels.map(channel =>
    channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4,
  );
  return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

const contrast = (first: string, second: string) => {
  const [light, dark] = [luminance(first), luminance(second)].sort((a, b) => b - a);
  return (light + 0.05) / (dark + 0.05);
};

/** White or the ink color, whichever has the higher contrast on the given background. */
export function readableTextOn(background: string): string {
  return contrast(WHITE, background) >= contrast(INK, background) ? WHITE : INK;
}

/**
 * Group the steps of an itinerary into legs, joining stretches in a row on the same line, and work out the clock
 * time at every stop from the departure. A step's minutes include the transfer and the wait before boarding.
 */
export function journeyLegs(steps: Step[], departure: Date | null): JourneyLeg[] {
  const at = (minutes: number) =>
    departure ? recifeTime(new Date(departure.getTime() + minutes * 60_000)) : null;
  const legs: JourneyLeg[] = [];
  let elapsed = 0;
  for (const step of steps) {
    const transferMinutes = step.transfer_minutes ?? 0;
    const waitMinutes = step.wait_minutes ?? 0;
    const arrivedAt = elapsed;
    elapsed += step.minutes;
    const previous = legs.at(-1);
    const continues =
      previous !== undefined &&
      step.line_code !== null &&
      previous.lineCode === step.line_code &&
      previous.mode === step.mode &&
      previous.to.name === step.origin &&
      transferMinutes === 0 &&
      waitMinutes === 0;
    if (continues) {
      previous.passes.push(previous.to.name);
      previous.to = { name: step.destination, time: at(elapsed) };
      previous.travelMinutes += step.minutes;
      previous.costCents += step.cost_cents;
      previous.accessibleFor = previous.accessibleFor.filter(need =>
        (step.accessible_for ?? []).includes(need),
      );
      previous.hasStairs ||= Boolean(step.has_stairs);
      continue;
    }
    legs.push({
      mode: step.mode,
      modeName: step.mode_name,
      color: step.color ?? '#526b74',
      lineCode: step.line_code,
      from: { name: step.origin, time: at(arrivedAt) },
      boardTime: at(arrivedAt + transferMinutes + waitMinutes),
      to: { name: step.destination, time: at(elapsed) },
      transferMinutes,
      waitMinutes,
      travelMinutes: step.minutes - transferMinutes - waitMinutes,
      costCents: step.cost_cents,
      fareIntegrated: Boolean(step.fare_integrated),
      passes: [],
      accessibleFor: [...(step.accessible_for ?? [])],
      hasStairs: Boolean(step.has_stairs),
    });
  }
  return legs;
}
