import type { Envelope, Session } from './types';

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

/** The API answers these statuses without a message meant for people (an empty one, or Laravel's in English). */
const statusMessages: Partial<Record<number, string>> = {
  401: 'Entre na sua conta para continuar.',
  404: 'Não encontramos o que você procurava. Confira o endereço ou faça uma nova busca.',
  419: 'Sua sessão expirou. Atualize a página e tente novamente.',
  429: 'Muitas tentativas. Aguarde um instante e tente novamente.',
  500: 'Ocorreu um erro no serviço. Tente novamente em instantes.',
};

let csrfToken = '';
let sessionRequest: Promise<Session> | null = null;

export async function loadSession(): Promise<Session> {
  if (!sessionRequest) {
    sessionRequest = api<Session>('/sessao')
      .then(result => {
        csrfToken = result.data.csrf_token;
        return result.data;
      })
      .finally(() => {
        sessionRequest = null;
      });
  }
  return sessionRequest;
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<Envelope<T>> {
  const method = options.method ?? 'GET';
  if (!['GET', 'HEAD'].includes(method) && !csrfToken) {
    await loadSession();
  }
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  if (options.body) {
    headers.set('Content-Type', 'application/json');
  }
  if (csrfToken && !['GET', 'HEAD'].includes(method)) {
    headers.set('X-CSRF-TOKEN', csrfToken);
  }

  const response = await fetch(`/api/v1${path}`, {
    ...options,
    headers,
    credentials: 'same-origin',
    cache: 'no-store',
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new ApiError(
      statusMessages[response.status] ??
        (result.message || 'Não foi possível concluir. Tente novamente.'),
      response.status,
      result.errors,
    );
  }
  return result;
}

export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    const messages = Object.values(error.errors).flat();
    return messages.length ? messages.join(' ') : error.message;
  }
  return error instanceof Error ? error.message : 'Ocorreu um erro inesperado. Tente novamente.';
}
