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

export function useApiData<T>(path: string | null) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(Boolean(path));
  const [revision, setRevision] = useState(0);

  useEffect(() => {
    if (!path) {
      setLoading(false);
      setData(null);
      return;
    }
    const controller = new AbortController();
    setLoading(true);
    setError('');
    api<T>(path, { signal: controller.signal })
      .then(result => {
        setData(result.data);
      })
      .catch(error => {
        if (!controller.signal.aborted) {
          setError(errorMessage(error));
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      });
    return () => controller.abort();
  }, [path, revision]);

  return { data, error, loading, reload: () => setRevision(value => value + 1) };
}
