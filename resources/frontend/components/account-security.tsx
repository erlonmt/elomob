'use client';

import { useState, type FormEvent } from 'react';
import { useApplication } from '@/components/providers';
import { Notice } from '@/components/ui';
import { api, errorMessage } from '@/lib/api';

/** Changing the password and deleting the account, each in its own collapsible form. */
export function AccountSecurity({ onDeleted }: { onDeleted: () => void }) {
  return (
    <>
      <h2>Segurança e privacidade</h2>
      <PasswordChange />
      <AccountDeletion onDeleted={onDeleted} />
    </>
  );
}

function PasswordChange() {
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const changePassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = event.currentTarget;
    setSubmitting(true);
    setError('');
    setSuccess('');
    try {
      await api('/perfil/senha', {
        method: 'PUT',
        body: JSON.stringify(Object.fromEntries(new FormData(form))),
      });
      form.reset();
      setSuccess('Senha alterada. As outras sessões da sua conta foram encerradas.');
    } catch (error) {
      setError(errorMessage(error));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <details className="profile-edit">
      <summary>Trocar senha</summary>
      <form className="stack" onSubmit={changePassword}>
        <label className="input-label">
          Senha atual
          <input type="password" name="current_password" required autoComplete="current-password" />
        </label>
        <label className="input-label">
          Nova senha
          <input
            type="password"
            name="password"
            required
            minLength={8}
            autoComplete="new-password"
          />
        </label>
        <label className="input-label">
          Confirme a nova senha
          <input
            type="password"
            name="password_confirmation"
            required
            minLength={8}
            autoComplete="new-password"
          />
        </label>
        <Notice>{error}</Notice>
        <Notice success>{success}</Notice>
        <button className="button" disabled={submitting}>
          {submitting ? 'Salvando...' : 'Salvar nova senha'}
        </button>
      </form>
    </details>
  );
}

function AccountDeletion({ onDeleted }: { onDeleted: () => void }) {
  const { refreshSession } = useApplication();
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const deleteAccount = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await api('/perfil', {
        method: 'DELETE',
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget))),
      });
      onDeleted();
      await refreshSession();
    } catch (error) {
      setError(errorMessage(error));
      setSubmitting(false);
    }
  };

  return (
    <details className="profile-edit danger-zone">
      <summary>Excluir conta</summary>
      <form className="stack" onSubmit={deleteAccount}>
        <p id="account-deletion-warning">
          Sua conta, seus locais salvos e seu histórico de buscas serão apagados. Não é possível
          desfazer.
        </p>
        <label className="input-label">
          Senha
          <input
            type="password"
            name="password"
            required
            autoComplete="current-password"
            aria-describedby="account-deletion-warning"
          />
        </label>
        <Notice>{error}</Notice>
        <button className="button danger" disabled={submitting}>
          {submitting ? 'Excluindo...' : 'Excluir minha conta'}
        </button>
      </form>
    </details>
  );
}
