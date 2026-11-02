'use client';

import Link from 'next/link';
import { useRef, useState, type FormEvent } from 'react';
import { useApiData, useApplication } from '@/components/providers';
import { Empty, Icon, Loading, LoginPrompt, Notice, PageHeader } from '@/components/ui';
import { api, errorMessage } from '@/lib/api';
import type { Favorite, Search } from '@/lib/types';

export function FavoritesScreen() {
  const { user, sessionLoading, catalog } = useApplication();
  const favorites = useApiData<Favorite[]>(user ? '/favoritos' : null);
  const history = useApiData<Search[]>(user ? '/historico' : null);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [removing, setRemoving] = useState<number | null>(null);
  const [success, setSuccess] = useState('');
  const [confirmingClear, setConfirmingClear] = useState(false);
  const [clearing, setClearing] = useState(false);
  const [historyError, setHistoryError] = useState('');
  const [historySuccess, setHistorySuccess] = useState('');
  const clearButton = useRef<HTMLButtonElement>(null);
  const historyHeading = useRef<HTMLHeadingElement>(null);

  const clearHistory = async () => {
    setClearing(true);
    setHistoryError('');
    setHistorySuccess('');
    try {
      await api('/historico', { method: 'DELETE' });
      setConfirmingClear(false);
      history.reload();
      setHistorySuccess('Histórico apagado.');
      historyHeading.current?.focus();
    } catch (error) {
      setHistoryError(errorMessage(error));
    } finally {
      setClearing(false);
    }
  };

  const saveFavorite = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const formElement = event.currentTarget;
    const form = new FormData(formElement);
    setSubmitting(true);
    setError('');
    try {
      await api('/favoritos', {
        method: 'POST',
        body: JSON.stringify({ label: form.get('label'), stop_id: Number(form.get('stop_id')) }),
      });
      formElement.reset();
      favorites.reload();
      setSuccess('Local salvo nos seus favoritos.');
    } catch (error) {
      setError(errorMessage(error));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <PageHeader title="Rotas" subtitle="Seus lugares e caminhos em um só lugar." />
      {sessionLoading ? (
        <Loading />
      ) : !user ? (
        <LoginPrompt />
      ) : (
        <div className="content favorites-layout">
          <section>
            <h2>Locais Salvos</h2>
            <Notice>{favorites.error || error}</Notice>
            <Notice success>{success}</Notice>
            {favorites.loading ? (
              <Loading />
            ) : (
              <div className="stack saved-list">
                {favorites.data?.map(favorite => (
                  <article className="saved-card" key={favorite.id}>
                    <Link href={`/buscar?destination_id=${favorite.stop_id}`}>
                      <span className="icon-tile round">
                        <Icon name="saved" />
                      </span>
                      <span className="card-info">
                        <strong>{favorite.label}</strong>
                        <small>
                          {favorite.name ?? favorite.stop?.name} ·{' '}
                          {favorite.address ?? favorite.stop?.address}
                        </small>
                      </span>
                    </Link>
                    <button
                      type="button"
                      className="text-button"
                      aria-label={`Excluir ${favorite.label}`}
                      disabled={removing === favorite.id}
                      onClick={async () => {
                        setRemoving(favorite.id);
                        setError('');
                        try {
                          await api(`/favoritos/${favorite.id}`, { method: 'DELETE' });
                          favorites.reload();
                          setSuccess('Local removido.');
                        } catch (error) {
                          setError(errorMessage(error));
                        } finally {
                          setRemoving(null);
                        }
                      }}
                    >
                      {removing === favorite.id ? '...' : 'Excluir'}
                    </button>
                  </article>
                ))}
                {!favorites.data?.length && (
                  <Empty title="Seus lugares favoritos">
                    Salve casa, trabalho ou outro destino para buscar trajetos mais rápido.
                  </Empty>
                )}
              </div>
            )}
            <details className="add-place">
              <summary>+ Adicionar local favorito</summary>
              <form className="stack" onSubmit={saveFavorite}>
                <label className="input-label">
                  Nome do local
                  <input name="label" required maxLength={60} placeholder="Ex.: Casa" />
                </label>
                <label className="input-label">
                  Destino
                  <select name="stop_id" required defaultValue="">
                    <option value="" disabled>
                      Escolha um destino
                    </option>
                    {catalog.stops.map(stop => (
                      <option value={stop.id} key={stop.id}>
                        {stop.name}
                      </option>
                    ))}
                  </select>
                </label>
                <button className="button" disabled={submitting}>
                  {submitting ? 'Salvando...' : 'Salvar local'}
                </button>
              </form>
            </details>
            <Link className="button outline section-space" href="/integracoes">
              Pontos de integração
            </Link>
          </section>
          <section className="history-section">
            <div className="section-heading">
              <h2 ref={historyHeading} tabIndex={-1}>
                Histórico de trajetos
              </h2>
              {Boolean(history.data?.length) && (
                <button
                  type="button"
                  className="text-button"
                  ref={clearButton}
                  aria-expanded={confirmingClear}
                  aria-controls={confirmingClear ? 'clear-history' : undefined}
                  onClick={() => setConfirmingClear(open => !open)}
                >
                  Limpar histórico
                </button>
              )}
            </div>
            {confirmingClear && (
              <div className="confirm-panel" id="clear-history">
                <p>Apagar todas as buscas do histórico? Os locais salvos continuam.</p>
                <div className="confirm-actions">
                  <button
                    type="button"
                    className="button danger"
                    disabled={clearing}
                    onClick={clearHistory}
                  >
                    {clearing ? 'Apagando...' : 'Apagar histórico'}
                  </button>
                  <button
                    type="button"
                    className="button outline"
                    onClick={() => {
                      setConfirmingClear(false);
                      clearButton.current?.focus();
                    }}
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            )}
            <Notice>{history.error || historyError}</Notice>
            <Notice success>{historySuccess}</Notice>
            {history.loading ? (
              <Loading />
            ) : (
              <div className="stack saved-list">
                {history.data?.map(trip => (
                  <Link
                    href={`/resultados/${trip.id}`}
                    className="preview-card history-card"
                    key={trip.id}
                  >
                    <span className="icon-tile">
                      <Icon name="clock" />
                    </span>
                    <span className="card-info">
                      <strong>
                        {trip.origin} → {trip.destination}
                      </strong>
                      <small>
                        {trip.created_at &&
                          new Intl.DateTimeFormat('pt-BR', {
                            dateStyle: 'short',
                            timeStyle: 'short',
                            timeZone: 'America/Recife',
                          }).format(new Date(trip.created_at))}
                      </small>
                    </span>
                    <Icon name="arrow" />
                  </Link>
                ))}
                {!history.data?.length && (
                  <Empty
                    title="Seu próximo caminho começa aqui"
                    action={
                      <Link className="button" href="/buscar">
                        Buscar um trajeto
                      </Link>
                    }
                  >
                    As buscas feitas após entrar aparecerão aqui.
                  </Empty>
                )}
              </div>
            )}
          </section>
        </div>
      )}
    </>
  );
}
