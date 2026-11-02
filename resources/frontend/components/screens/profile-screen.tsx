'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useState, type FormEvent } from 'react';
import { AccountSecurity } from '@/components/account-security';
import { useApplication } from '@/components/providers';
import { DemoNote, Icon, Loading, LoginPrompt, Notice, PageHeader } from '@/components/ui';
import { accessibilityNeeds } from '@/lib/accessibility';
import { api, errorMessage } from '@/lib/api';
import type { User } from '@/lib/types';

export function ProfileScreen() {
  const { user, sessionLoading } = useApplication();
  const [farewell, setFarewell] = useState('');
  return (
    <>
      <PageHeader title="Meu Perfil" subtitle="Mobilidade que combina com você." />
      {sessionLoading ? (
        <Loading />
      ) : user ? (
        <ProfileForm
          user={user}
          key={user.id}
          onDeleted={() =>
            setFarewell(
              'Sua conta foi excluída. Seus locais salvos e seu histórico foram apagados.',
            )
          }
        />
      ) : (
        <>
          {farewell && (
            <div className="content narrow">
              <Notice success>{farewell}</Notice>
            </div>
          )}
          <LoginPrompt />
        </>
      )}
    </>
  );
}

function ProfileForm({ user, onDeleted }: { user: User; onDeleted: () => void }) {
  const { catalog, refreshSession } = useApplication();
  const router = useRouter();
  const [profile, setProfile] = useState(user);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [editing, setEditing] = useState(false);
  const updateProfile = async (event: FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    setSuccess('');
    try {
      const result = await api<User>('/perfil', { method: 'PUT', body: JSON.stringify(profile) });
      setProfile(result.data);
      await refreshSession();
      setSuccess('Perfil e preferências salvos.');
    } catch (error) {
      setError(errorMessage(error));
    } finally {
      setSubmitting(false);
    }
  };
  const toggleNeed = (code: string, checked: boolean) =>
    setProfile(previous => ({
      ...previous,
      accessibility_needs: accessibilityNeeds
        .map(need => need.code)
        .filter(item => (item === code ? checked : previous.accessibility_needs.includes(item))),
    }));

  return (
    <div className="content profile-layout">
      <form className="profile-form" onSubmit={updateProfile}>
        <div className="profile-card">
          <img
            src="/assets/figma/034fd.png"
            width="60"
            height="60"
            className="profile-avatar"
            alt="Avatar ilustrativo do protótipo"
          />
          <div className="card-info">
            <strong>{user.name}</strong>
            <small>{user.email}</small>
            <small className="inline teal-text">
              <Icon name="location" />
              Recife, PE
            </small>
          </div>
          <a href="#profile-edit" onClick={() => setEditing(true)} className="small-outline">
            Editar perfil
          </a>
        </div>
        <fieldset className="mode-fieldset">
          <legend>Preferências de transporte</legend>
          <div className="preference-modes">
            {catalog.modes.map(mode => (
              <label key={mode.id}>
                <input
                  type="checkbox"
                  checked={profile.transport_modes?.includes(mode.code) ?? false}
                  onChange={event =>
                    setProfile(previous => ({
                      ...previous,
                      transport_modes: event.target.checked
                        ? [...previous.transport_modes, mode.code]
                        : previous.transport_modes.filter(code => code !== mode.code),
                    }))
                  }
                />
                <span>
                  <Icon name={`${mode.code}-preference`} />
                  <b>{mode.name}</b>
                </span>
              </label>
            ))}
          </div>
        </fieldset>
        <fieldset className="mode-fieldset">
          <legend>Tipo de rota preferida</legend>
          <div className="route-preferences">
            {[
              { value: 'fastest', label: 'Mais rápida' },
              { value: 'cheapest', label: 'Mais econômica' },
              { value: 'sustainable', label: 'Mais sustentável' },
            ].map(option => (
              <label key={option.value}>
                <input
                  type="radio"
                  name="preferred_route"
                  value={option.value}
                  checked={profile.preferred_route === option.value}
                  onChange={() =>
                    setProfile(previous => ({ ...previous, preferred_route: option.value }))
                  }
                />
                <span>
                  <Icon name={option.value} />
                  <b>{option.label}</b>
                </span>
              </label>
            ))}
          </div>
        </fieldset>
        <h2 id="accessibility-heading">Acessibilidade</h2>
        <p className="section-description" id="accessibility-description">
          Marque o que se aplica a você. As buscas só mostram trajetos com esses recursos.
        </p>
        <div
          className="settings-card"
          role="group"
          aria-labelledby="accessibility-heading"
          aria-describedby="accessibility-description"
        >
          {accessibilityNeeds.map(need => (
            <label className="setting-row" key={need.code}>
              <span className="icon-tile">
                <Icon name={need.icon} />
              </span>
              <span className="setting-text">
                <strong id={`need-${need.code}-title`}>{need.title}</strong>
                <small id={`need-${need.code}`}>{need.description}</small>
              </span>
              <input
                className="switch"
                type="checkbox"
                aria-labelledby={`need-${need.code}-title`}
                aria-describedby={`need-${need.code}`}
                checked={profile.accessibility_needs.includes(need.code)}
                onChange={event => toggleNeed(need.code, event.target.checked)}
              />
            </label>
          ))}
        </div>
        <details
          className="profile-edit"
          id="profile-edit"
          open={editing}
          onToggle={event => setEditing(event.currentTarget.open)}
        >
          <summary>Editar dados do perfil</summary>
          <div className="stack">
            <label className="input-label">
              Nome
              <input
                required
                maxLength={100}
                value={profile.name}
                onChange={event =>
                  setProfile(previous => ({ ...previous, name: event.target.value }))
                }
                autoComplete="name"
              />
            </label>
            <label className="input-label">
              E-mail
              <input
                type="email"
                required
                value={profile.email}
                onChange={event =>
                  setProfile(previous => ({ ...previous, email: event.target.value }))
                }
                autoComplete="email"
              />
            </label>
          </div>
        </details>
        <Notice>{error}</Notice>
        <Notice success>{success}</Notice>
        <button className="button" disabled={submitting}>
          {submitting ? 'Salvando...' : 'Salvar preferências'}
        </button>
      </form>
      <aside className="profile-aside">
        <h2>Minha atividade</h2>
        <div className="settings-card">
          <Link href="/rotas" className="setting-row">
            <span className="icon-tile">
              <Icon name="heart" />
            </span>
            <strong>Rotas favoritas e histórico</strong>
            <Icon name="arrow" />
          </Link>
          <Link href="/integracoes" className="setting-row">
            <span className="icon-tile">
              <Icon name="integration" />
            </span>
            <strong>Pontos de integração</strong>
            <Icon name="arrow" />
          </Link>
        </div>
        <h2>Configurações</h2>
        <div className="settings-card">
          <Link href="/sobre" className="setting-row">
            <span className="icon-tile">
              <Icon name="help" />
            </span>
            <strong>Ajuda e suporte</strong>
            <Icon name="arrow" />
          </Link>
          <Link href="/sobre" className="setting-row">
            <span className="icon-tile">
              <Icon name="info" />
            </span>
            <strong>Sobre o ELOMOB</strong>
            <Icon name="arrow" />
          </Link>
          <button
            className="setting-row logout"
            type="button"
            disabled={loggingOut}
            onClick={async () => {
              setLoggingOut(true);
              setError('');
              try {
                await api('/sair', { method: 'POST' });
                await refreshSession();
                router.push('/');
              } catch (error) {
                setError(errorMessage(error));
                setLoggingOut(false);
              }
            }}
          >
            <span className="icon-tile">
              <Icon name="logout" />
            </span>
            <strong>{loggingOut ? 'Saindo...' : 'Sair da conta'}</strong>
          </button>
        </div>
        <AccountSecurity onDeleted={onDeleted} />
        <DemoNote />
      </aside>
    </div>
  );
}
