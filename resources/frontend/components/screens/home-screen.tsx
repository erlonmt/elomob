'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useState } from 'react';
import { GpsButton } from '@/components/gps-button';
import { useApplication } from '@/components/providers';
import { StopSelect } from '@/components/stop-select';
import { Brand, DemoNote, Icon, Loading, Notice } from '@/components/ui';
import { shortDistance } from '@/lib/format';
import type { Stop } from '@/lib/types';

/** How many of the stops near the visitor the home screen lists after the GPS finds them. */
const NEARBY_SHOWN = 3;

export function HomeScreen() {
  const { catalog, user, loading, catalogError, refreshCatalog } = useApplication();
  const router = useRouter();
  const [origin, setOrigin] = useState('');
  const [destination, setDestination] = useState('');
  const [error, setError] = useState('');
  const [nearby, setNearby] = useState<Stop[] | null>(null);
  const bus = catalog.lines.find(line => line.mode === 'bus');
  const metro = catalog.stops.find(
    stop =>
      stop.mode === 'metro' ||
      stop.transport_mode_id === catalog.modes.find(mode => mode.code === 'metro')?.id,
  );
  const bike = catalog.stops.find(stop => stop.bikes_available !== null);

  return (
    <>
      <section className="home-hero">
        <div className="hero-inner">
          <div className="hero-top">
            <Brand />
            <Link className="avatar-button" href="/perfil" aria-label="Abrir meu perfil">
              <Icon name="avatar" />
            </Link>
          </div>
          <p>{user ? `Olá, ${user.name.split(' ')[0]}!` : 'Olá, bem-vindo ao Recife!'}</p>
          <h1>Para onde você vai hoje?</h1>
          <p className="hero-description">
            Uma cidade de possibilidades.
            <br /> Encontre o melhor caminho para você.
          </p>
        </div>
      </section>
      <div className="home-content">
        <section className="home-planner-area">
          <form
            className="planner"
            onSubmit={event => {
              event.preventDefault();
              router.push(
                `/buscar?${new URLSearchParams({ origin_id: origin, destination_id: destination })}`,
              );
            }}
          >
            <h2 className="desktop-only">Planeje seu próximo trajeto</h2>
            <label className="field">
              <Icon name="pin" />
              <StopSelect
                value={origin}
                onChange={setOrigin}
                label="Origem"
                placeholder="Escolha a origem"
              />
              <GpsButton
                disabled={loading}
                onLocate={(stop, stops) => {
                  setOrigin(String(stop.id));
                  setNearby(stops.slice(0, NEARBY_SHOWN));
                }}
                onError={setError}
              />
            </label>
            <label className="field">
              <Icon name="destination" />
              <StopSelect
                value={destination}
                onChange={setDestination}
                label="Destino"
                placeholder="Escolha o destino"
              />
            </label>
            <Notice>{error}</Notice>
            <button className="button coral" disabled={loading || Boolean(catalogError)}>
              Buscar Rota
            </button>
          </form>
          <section className="transport-section">
            <h2>Modos de Transporte</h2>
            <div className="transport-shortcuts">
              {catalog.modes.map(mode => (
                <Link href={`/mapa?mode=${mode.code}`} key={mode.id}>
                  <span className="round-icon">
                    <Icon name={mode.code} />
                  </span>
                  <span>{mode.name}</span>
                </Link>
              ))}
            </div>
            {loading && <Loading label="Carregando transportes..." />}
          </section>
        </section>
        <section className="home-network">
          <div className="section-heading">
            <h2>Transportes próximos</h2>
            <Link href="/mapa">Ver mapa</Link>
          </div>
          <p className="section-description" aria-live="polite">
            {nearby
              ? 'As paradas mais perto de você.'
              : 'Explore a rede cadastrada ou use o GPS para encontrar uma parada.'}
          </p>
          {nearby ? (
            <div className="stack">
              {nearby.map(stop => (
                <Link
                  href={`/mapa?q=${encodeURIComponent(stop.name)}`}
                  className="nearby-card"
                  key={stop.id}
                >
                  <span className="icon-tile">
                    <Icon name={`${stop.mode}-card`} />
                  </span>
                  <span className="card-info">
                    <strong>{stop.name}</strong>
                    <small>
                      {stop.mode_name} · {shortDistance(stop.distance_meters ?? 0)}
                    </small>
                  </span>
                  <span className="badge">Demo</span>
                </Link>
              ))}
            </div>
          ) : (
            <div className="stack">
              {bus && (
                <Link href={`/horarios?q=${encodeURIComponent(bus.code)}`} className="nearby-card">
                  <span className="icon-tile">
                    <Icon name="bus-card" />
                  </span>
                  <span className="card-info">
                    <strong>Ônibus {bus.code}</strong>
                    <small>{bus.name}</small>
                  </span>
                  <span className="badge">Demo</span>
                </Link>
              )}
              {metro && (
                <Link href={`/mapa?q=${encodeURIComponent(metro.name)}`} className="nearby-card">
                  <span className="icon-tile">
                    <Icon name="metro-card" />
                  </span>
                  <span className="card-info">
                    <strong>{metro.name}</strong>
                    <small>Metrô · Rede cadastrada</small>
                  </span>
                  <span className="badge">Demo</span>
                </Link>
              )}
              {bike && (
                <Link href="/mapa?mode=bike" className="nearby-card">
                  <span className="icon-tile">
                    <Icon name="bike-card" />
                  </span>
                  <span className="card-info">
                    <strong>{bike.name}</strong>
                    <small>
                      {bike.bikes_available} disponíveis · {bike.docks_available} vagas
                    </small>
                  </span>
                  <span className="badge">Demo</span>
                </Link>
              )}
            </div>
          )}
          {catalogError && (
            <>
              <Notice>{catalogError}</Notice>
              <button className="text-button" onClick={() => void refreshCatalog()}>
                Tentar novamente
              </button>
            </>
          )}
          <div className="city-note">
            <span className="city-note-icon">
              <Icon name="integration" />
            </span>
            <div>
              <h3>Conecte seu caminho</h3>
              <p>Combine transportes e descubra os pontos de integração da cidade.</p>
              <Link href="/integracoes">Conhecer as integrações →</Link>
            </div>
          </div>
        </section>
      </div>
      <div className="home-demo">
        <DemoNote />
      </div>
    </>
  );
}
