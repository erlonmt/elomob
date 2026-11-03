'use client';

import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { GpsButton } from '@/components/gps-button';
import { useApplication } from '@/components/providers';
import { StopSelect } from '@/components/stop-select';
import { DemoNote, Icon, Loading, Notice, PageHeader } from '@/components/ui';
import { needTitles } from '@/lib/accessibility';
import { api, errorMessage } from '@/lib/api';
import { departureWindow, recifeTime, serviceHoursNotice } from '@/lib/schedule';
import type { Search } from '@/lib/types';

export function SearchScreen() {
  const { catalog, user, loading, sessionLoading, catalogError } = useApplication();
  const params = useSearchParams();
  const router = useRouter();
  const initialized = useRef(false);
  const [origin, setOrigin] = useState(params.get('origin_id') ?? '');
  const [destination, setDestination] = useState(params.get('destination_id') ?? '');
  const [modes, setModes] = useState<string[]>([]);
  const [departure, setDeparture] = useState(params.get('departure_at')?.slice(0, 16) ?? '');
  const [scheduled, setScheduled] = useState(Boolean(params.get('departure_at')));
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const hoursNotice = serviceHoursNotice(
    catalog.lines,
    modes,
    scheduled && departure ? departure.slice(11, 16) : recifeTime(new Date()),
  );

  useEffect(() => {
    if (!loading && !sessionLoading && !initialized.current) {
      setModes(
        params.has('modes')
          ? params.getAll('modes')
          : (user?.transport_modes ?? catalog.modes.map(mode => mode.code)),
      );
      initialized.current = true;
    }
  }, [catalog.modes, loading, sessionLoading, params, user]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    if (origin === destination) {
      setError('Escolha uma origem e um destino diferentes.');
      return;
    }
    if (!modes.length) {
      setError('Selecione ao menos um modo de transporte.');
      return;
    }
    setSubmitting(true);
    try {
      const result = await api<Search>('/buscas', {
        method: 'POST',
        body: JSON.stringify({
          origin_id: Number(origin),
          destination_id: Number(destination),
          modes,
          ...(scheduled && departure ? { departure_at: `${departure}:00-03:00` } : {}),
        }),
      });
      router.push(`/resultados/${result.data.id}`);
    } catch (error) {
      setError(errorMessage(error));
      setSubmitting(false);
    }
  };

  return (
    <>
      <PageHeader title="Buscar Rota" subtitle="Combine transportes e encontre seu caminho." />
      <form className="search-layout content" onSubmit={submit}>
        <section className="search-panel card">
          <h2 className="desktop-only">De onde para onde?</h2>
          <label className="field">
            <Icon name="pin" />
            <StopSelect
              value={origin}
              onChange={setOrigin}
              label="Escolha a origem"
              placeholder="Escolha a origem"
            />
            <GpsButton
              disabled={loading}
              onLocate={stop => setOrigin(String(stop.id))}
              onError={setError}
            />
          </label>
          <button
            className="swap-button"
            type="button"
            aria-label="Trocar origem e destino"
            onClick={() => {
              setOrigin(destination);
              setDestination(origin);
            }}
          >
            ⇅
          </button>
          <label className="field focused">
            <Icon name="destination" />
            <StopSelect
              value={destination}
              onChange={setDestination}
              label="Escolha o destino"
              placeholder="Escolha o destino"
            />
          </label>
          <div className="departure-row">
            <span className="inline muted">
              <Icon name="time" />
              {scheduled ? 'Horário de saída' : 'Sair agora'}
            </span>
            <button
              type="button"
              className="text-button"
              onClick={() => setScheduled(value => !value)}
            >
              {scheduled ? 'Sair agora' : 'Escolher horário'}
            </button>
          </div>
          {scheduled && (
            <label className="input-label">
              Data e horário no Recife
              <input
                type="datetime-local"
                value={departure}
                onChange={event => setDeparture(event.target.value)}
                {...departureWindow(new Date())}
                required
              />
            </label>
          )}
          <fieldset className="mode-fieldset">
            <legend>Modais ativos</legend>
            <div className="chips">
              {catalog.modes.map(mode => (
                <label className="chip" key={mode.id}>
                  <input
                    type="checkbox"
                    value={mode.code}
                    checked={modes.includes(mode.code)}
                    onChange={event =>
                      setModes(previous =>
                        event.target.checked
                          ? [...previous, mode.code]
                          : previous.filter(code => code !== mode.code),
                      )
                    }
                  />
                  <span>{mode.name}</span>
                </label>
              ))}
            </div>
          </fieldset>
          {hoursNotice && (
            <p className="hours-note" role="status">
              {hoursNotice}
            </p>
          )}
          {user && (
            <p className="preference-note">
              {user.accessibility_needs.length
                ? `Acessibilidade aplicada: ${needTitles(user.accessibility_needs).join(', ')}.`
                : 'Nenhuma necessidade de acessibilidade marcada no perfil.'}{' '}
              <Link href="/perfil">Editar</Link>
            </p>
          )}
          <Notice>{catalogError || error}</Notice>
          <button
            className="button coral"
            disabled={submitting || loading || sessionLoading || Boolean(catalogError)}
          >
            {submitting ? 'Calculando trajetos...' : 'Buscar Rota'}
          </button>
          <DemoNote />
        </section>
        <section className="available-places">
          <h2>Locais disponíveis</h2>
          <p className="section-description">Escolha um destino da rede demonstrativa.</p>
          {loading ? (
            <Loading />
          ) : (
            <div className="stack">
              {catalog.stops.map(stop => (
                <button
                  type="button"
                  className={`suggestion ${destination === String(stop.id) ? 'selected' : ''}`}
                  onClick={() => setDestination(String(stop.id))}
                  key={stop.id}
                >
                  <span className="icon-tile">
                    <Icon name="recent" />
                  </span>
                  <span className="card-info">
                    <strong>{stop.name}</strong>
                    <small>{stop.address}</small>
                  </span>
                  <Icon name="arrow" />
                </button>
              ))}
            </div>
          )}
        </section>
      </form>
    </>
  );
}
