'use client';

import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, errorMessage, loadSession } from '@/lib/api';
import type { Catalog, User } from '@/lib/types';

type ApplicationContext = {
  catalog: Catalog;
  user: User | null;
  loading: boolean;
  sessionLoading: boolean;
  catalogError: string;
  refreshSession: () => Promise<void>;
  refreshCatalog: () => Promise<void>;
};
const emptyCatalog: Catalog = { stops: [], modes: [], lines: [], integrations: [] };
const Context = createContext<ApplicationContext | null>(null);

export function Providers({ children }: { children: ReactNode }) {
  const [catalog, setCatalog] = useState<Catalog>(emptyCatalog);
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [sessionLoading, setSessionLoading] = useState(true);
  const [catalogError, setCatalogError] = useState('');

  const refreshSession = useCallback(async () => {
    try {
      const session = await loadSession();
      setUser(session.user);
    } finally {
      setSessionLoading(false);
    }
  }, []);

  const refreshCatalog = useCallback(async () => {
    setLoading(true);
    try {
      const result = await api<Catalog>('/catalogo');
      if (
        !['stops', 'modes', 'lines', 'integrations'].every(key =>
          Array.isArray(result.data[key as keyof Catalog]),
        )
      ) {
        throw new Error(
          'Não foi possível carregar a rede de transportes. Tente novamente em instantes.',
        );
      }
      setCatalog(result.data);
      setCatalogError('');
    } catch (error) {
      setCatalogError(errorMessage(error));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refreshSession()
      .catch(() => {})
      .finally(() => refreshCatalog());
  }, [refreshCatalog, refreshSession]);

  return (
    <Context.Provider
      value={{
        catalog,
        user,
        loading,
        sessionLoading,
        catalogError,
        refreshSession,
        refreshCatalog,
      }}
    >
      {children}
    </Context.Provider>
  );
}

export function useApplication(): ApplicationContext {
  const context = useContext(Context);
  if (!context) {
    throw new Error('Application provider is missing.');
  }
  return context;
}

/**
 * Load an API path, again on reload(). It is loading until the answer to the current path and reload arrives; the
 * data stays the last one received meanwhile, and is null without a path.
 */
export function useApiData<T>(path: string | null) {
  const [revision, setRevision] = useState(0);
  const [answer, setAnswer] = useState<{ key: string | null; data: T | null; error: string }>({
    key: null,
    data: null,
    error: '',
  });
  const key = path ? `${path}#${revision}` : null;

  useEffect(() => {
    if (!path) {
      return;
    }
    const controller = new AbortController();
    api<T>(path, { signal: controller.signal })
      .then(result => setAnswer({ key, data: result.data, error: '' }))
      .catch(error => {
        if (!controller.signal.aborted) {
          setAnswer(previous => ({ key, data: previous.data, error: errorMessage(error) }));
        }
      });
    return () => controller.abort();
  }, [key, path]);

  return {
    data: path ? answer.data : null,
    error: answer.key === key ? answer.error : '',
    loading: key !== null && answer.key !== key,
    reload: () => setRevision(value => value + 1),
  };
}
