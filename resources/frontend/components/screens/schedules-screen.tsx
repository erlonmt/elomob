'use client';

import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useEffect, useState } from 'react';
import { useApplication } from '@/components/providers';
import { DemoNote, Empty, Icon, Loading, Notice, PageHeader } from '@/components/ui';
import { money } from '@/lib/format';
import { clockTime, minuteOfDay, recifeTime, upcomingTrips } from '@/lib/schedule';
import type { Line } from '@/lib/types';

/** The line's code and name, without repeating the code when the name starts with it ("BRT Caxangá"). */
const lineTitle = (line: Line) =>
  line.name.startsWith(line.code) ? line.name : `${line.code} ${line.name}`;

/** The current instant, renewed every minute. */
function useNow(): Date {
  const [now, setNow] = useState(() => new Date());
  useEffect(() => {
    const timer = setInterval(() => setNow(new Date()), 60_000);
    return () => clearInterval(timer);
  }, []);
  return now;
}

/**
 * Each way of a line as a timetable: its stops, and when each of the next vehicles still on the way passes them, one
 * column per vehicle; a dash where the vehicle has already passed.
 */
function LineTimetables({ line, now }: { line: Line; now: Date }) {
  const nowMinute = minuteOfDay(recifeTime(now));
  const vehicle = line.mode === 'metro' ? 'trem' : 'ônibus';
  return (
    <>
      <p>
        Próximos {line.mode === 'metro' ? 'trens' : 'ônibus'} em cada sentido. Um traço indica que o{' '}
        {vehicle} já passou pela parada.
      </p>
      {line.directions.map(direction => {
        const trips = upcomingTrips(line, direction.stops.at(-1)?.minutes ?? 0, now);
        return (
          <table className="line-timetable" key={direction.to}>
            <caption>Sentido {direction.to}</caption>
            <thead>
              <tr>
                <th scope="col">Parada</th>
                {trips.map(trip => (
                  <th scope="col" key={trip.minute}>
                    {trip.tomorrow && 'Amanhã'}
                    <span className="visually-hidden">
                      {' '}
                      {vehicle} que sai de {direction.stops[0].name} às {clockTime(trip.minute)}
                    </span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {direction.stops.map(stop => (
                <tr key={stop.id}>
                  <th scope="row">{stop.name}</th>
                  {trips.map(trip => {
                    const minute = trip.minute + stop.minutes;
                    return minute < nowMinute ? (
                      <td key={trip.minute} className="passed">
                        <span aria-hidden="true">–</span>
                        <span className="visually-hidden">já passou</span>
                      </td>
                    ) : (
                      <td key={trip.minute}>
                        <time>{clockTime(minute)}</time>
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        );
      })}
    </>
  );
}

export function SchedulesScreen() {
  const { catalog, loading, catalogError } = useApplication();
  const params = useSearchParams();
  const [mode, setMode] = useState(params.get('mode') ?? 'bus');
  const [query, setQuery] = useState(params.get('q') ?? '');
  const lines = catalog.lines.filter(
    line =>
      line.mode === mode &&
      `${line.code} ${line.name}`
        .toLocaleLowerCase('pt-BR')
        .includes(query.toLocaleLowerCase('pt-BR')),
  );
  const bikes = catalog.stops.filter(stop => stop.mode === 'bike');
  const now = useNow();

  return (
    <>
      <PageHeader title="Horários e Linhas" subtitle="Planeje suas conexões pela cidade." />
      <div className="content schedules-layout">
        <section>
          <div className="tabs" role="tablist" aria-label="Modo de transporte">
            <button
              role="tab"
              aria-selected={mode === 'bus'}
              className={mode === 'bus' ? 'selected' : ''}
              onClick={() => setMode('bus')}
            >
              Ônibus
            </button>
            <button
              role="tab"
              aria-selected={mode === 'metro'}
              className={mode === 'metro' ? 'selected' : ''}
              onClick={() => setMode('metro')}
            >
              Metrô
            </button>
          </div>
          <label className="field white schedule-search">
            <Icon name="search" />
            <input
              type="search"
              aria-label="Buscar linha ou estação"
              placeholder="Buscar linha ou estação..."
              value={query}
              onChange={event => setQuery(event.target.value)}
            />
          </label>
          <Notice>{catalogError}</Notice>
          {loading ? (
            <Loading />
          ) : (
            <div className="stack line-list">
              {lines.map(line => (
                <details key={line.id} className="line-card">
                  <summary>
                    <span className="icon-tile">
                      <Icon name={`${line.mode}-card`} />
                    </span>
                    <span className="card-info">
                      <strong>{lineTitle(line)}</strong>
                      <small>
                        {line.starts_at.slice(0, 5)}–{line.ends_at.slice(0, 5)} · a cada{' '}
                        {line.frequency_minutes} min
                      </small>
                    </span>
                    <Icon name="arrow" />
                  </summary>
                  <div className="line-details">
                    <p>
                      <strong>Tarifa estimada:</strong> {money(line.fare_cents)}
                    </p>
                    {line.directions.length ? (
                      <LineTimetables line={line} now={now} />
                    ) : (
                      <p>
                        A busca considera o horário de operação e a frequência cadastrada para
                        estimar a espera.
                      </p>
                    )}
                    <Link href="/buscar">Planejar um trajeto com esta rede →</Link>
                  </div>
                </details>
              ))}
              {!lines.length && (
                <Empty title="Nenhuma linha encontrada">Tente outro nome ou código.</Empty>
              )}
            </div>
          )}
        </section>
        <aside className="network-aside">
          <article className="preview-card">
            <div className="section-heading">
              <h2>Metrô de Recife</h2>
              <button
                className="text-button"
                onClick={() => {
                  setMode('metro');
                  setQuery('');
                }}
              >
                Ver tudo
              </button>
            </div>
            {catalog.lines
              .filter(line => line.mode === 'metro')
              .map(line => (
                <p className="metro-line" key={line.id}>
                  {line.name}
                  <small>
                    {line.starts_at.slice(0, 5)}–{line.ends_at.slice(0, 5)}
                  </small>
                </p>
              ))}
          </article>
          <article className="preview-card">
            <div className="section-heading">
              <h2>Bike Recife</h2>
              <Link href="/mapa?mode=bike">Mapa de vagas</Link>
            </div>
            {bikes.map(stop => (
              <div className="bike-availability" key={stop.id}>
                <p>{stop.name}</p>
                <span>
                  <strong>{stop.bikes_available}</strong> bicicletas{' '}
                  <small>· {stop.docks_available} vagas</small>
                </span>
              </div>
            ))}
          </article>
          <DemoNote />
        </aside>
      </div>
    </>
  );
}
