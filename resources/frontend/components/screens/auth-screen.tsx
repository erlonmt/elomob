'use client';

import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useState, type FormEvent } from 'react';
import { useApplication } from '@/components/providers';
import { Empty, Icon, Loading, Notice, PageHeader } from '@/components/ui';
import { api, errorMessage } from '@/lib/api';

export function AuthScreen() {
  const { user, refreshSession, sessionLoading } = useApplication();
  const params = useSearchParams();
  const router = useRouter();
  const [registering, setRegistering] = useState(false);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    const form = new FormData(event.currentTarget);
    try {
      await api(registering ? '/cadastrar' : '/entrar', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(form.entries())),
      });
      await refreshSession();
      const next = params.get('next');
      router.replace(next?.startsWith('/') && !next.startsWith('//') ? next : '/perfil');
    } catch (error) {
      setError(errorMessage(error));
      setSubmitting(false);
    }
  };

  return (
    <>
      <PageHeader title="Sua conta ELOMOB" />
      <div className="content auth-layout">
        <section className="auth-intro">
          <span className="about-mark">
            <Icon name="route-active" />
          </span>
          <h2>
            Seu caminho.
            <br />
            Suas preferências.
          </h2>
          <p>
            Salve seus locais, encontre seus trajetos recentes e escolha como você quer se mover.
          </p>
          <div className="auth-benefits">
            <p>
              <Icon name="heart" />
              Seus lugares sempre por perto
            </p>
            <p>
              <Icon name="accessible" />
              Acessibilidade no planejamento
            </p>
            <p>
              <Icon name="integration" />
              Todos os modos em um lugar
            </p>
          </div>
        </section>
        <section className="auth-card card">
          {sessionLoading ? (
            <Loading />
          ) : user ? (
            <Empty
              title={`Olá, ${user.name.split(' ')[0]}!`}
              action={
                <Link href="/perfil" className="button">
                  Ir para meu perfil
                </Link>
              }
            >
              Você já está conectado.
            </Empty>
          ) : (
            <>
              <h2>{registering ? 'Crie sua conta' : 'Bem-vindo de volta'}</h2>
              <p className="section-description">
                {registering
                  ? 'Comece a planejar sua mobilidade do seu jeito.'
                  : 'Entre para salvar seus trajetos.'}
              </p>
              <form className="stack auth-form" onSubmit={submit} key={String(registering)}>
                {registering && (
                  <label className="input-label">
                    Nome
                    <input name="name" required maxLength={100} autoComplete="name" />
                  </label>
                )}
                <label className="input-label">
                  E-mail
                  <input type="email" name="email" required autoComplete="email" />
                </label>
                <label className="input-label">
                  Senha
                  <input
                    type="password"
                    name="password"
                    required
                    minLength={registering ? 8 : undefined}
                    autoComplete={registering ? 'new-password' : 'current-password'}
                  />
                </label>
                {registering && (
                  <label className="input-label">
                    Confirme a senha
                    <input
                      type="password"
                      name="password_confirmation"
                      required
                      minLength={8}
                      autoComplete="new-password"
                    />
                  </label>
                )}
                <Notice>{error}</Notice>
                <button className={`button ${registering ? 'coral' : ''}`} disabled={submitting}>
                  {submitting ? 'Aguarde...' : registering ? 'Criar conta' : 'Entrar'}
                </button>
              </form>
              <p className="auth-toggle">
                {registering ? 'Já tem uma conta?' : 'Ainda não tem uma conta?'}{' '}
                <button
                  type="button"
                  className="text-button"
                  onClick={() => {
                    setRegistering(value => !value);
                    setError('');
                  }}
                >
                  {registering ? 'Entrar' : 'Criar uma conta'}
                </button>
              </p>
            </>
          )}
        </section>
      </div>
    </>
  );
}
