import type { Line } from './types.ts';

type LineHours = Pick<Line, 'mode' | 'starts_at' | 'ends_at'>;

/** What still works when no line runs, by the ride modes chosen ("taxi", "app", both or none). */
const RIDES_LEFT: Record<string, string> = {
  'taxi,app': 'Táxi e aplicativo continuam disponíveis.',
  taxi: 'O táxi continua disponível.',
  app: 'O aplicativo continua disponível.',
  '': 'Escolha outro horário ou inclua táxi ou aplicativo.',
};

/** Minutes since midnight of an "HH:MM" or "HH:MM:SS" time. */
const minuteOfDay = (time: string) => Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5));

/** Whether a line runs at the given minute of the day; a window ending before it starts crosses midnight. */
function runsAt(line: LineHours, minute: number): boolean {
  const start = minuteOfDay(line.starts_at);
  const end = minuteOfDay(line.ends_at);
  return start <= end ? minute >= start && minute <= end : minute >= start || minute <= end;
}

/**
 * A warning for a departure ("HH:MM" in Recife) when no line of the chosen modes runs then, saying when the lines
 * run and what still works; null while a line runs or when no mode with lines was chosen.
 */
export function serviceHoursNotice(
  lines: LineHours[],
  modes: string[],
  time: string,
): string | null {
  const chosen = lines.filter(line => modes.includes(line.mode));
  if (!chosen.length || chosen.some(line => runsAt(line, minuteOfDay(time)))) {
    return null;
  }
  const first = chosen.map(line => line.starts_at.slice(0, 5)).sort()[0];
  const last = chosen
    .map(line => line.ends_at.slice(0, 5))
    .sort()
    .at(-1);
  const rides = RIDES_LEFT[['taxi', 'app'].filter(mode => modes.includes(mode)).join(',')];
  return `Nenhuma linha de ônibus ou metrô funciona às ${time}: elas operam das ${first} às ${last}. ${rides}`;
}

/** The time of day in Recife, as "HH:MM". */
export function recifeTime(date: Date): string {
  return new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Recife',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(date);
}
