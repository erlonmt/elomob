import { strict as assert } from 'node:assert';
import test from 'node:test';
import { api, errorMessage } from '../lib/api.ts';

function respondWith(status: number, body: unknown): void {
  globalThis.fetch = async () =>
    new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

async function failure(path: string): Promise<string> {
  try {
    await api(path);
  } catch (error) {
    return errorMessage(error);
  }
  throw new Error('The request was expected to fail.');
}

test('a record that does not exist, or belongs to another account, is explained instead of leaving the screen blank', async () => {
  respondWith(404, { message: '' });
  assert.equal(
    await failure('/trajetos/999999'),
    'Não encontramos o que você procurava. Confira o endereço ou faça uma nova busca.',
  );
});

test('a page that needs an account asks to sign in, not with the framework message', async () => {
  respondWith(401, { message: 'Unauthenticated.' });
  assert.equal(await failure('/perfil'), 'Entre na sua conta para continuar.');
});

test('a server error is shown in Portuguese, not with the framework message', async () => {
  respondWith(500, { message: 'Server Error' });
  assert.equal(
    await failure('/buscas/1'),
    'Ocorreu um erro no serviço. Tente novamente em instantes.',
  );
});

test('messages written by the API for people are kept', async () => {
  respondWith(503, {
    message: 'Não foi possível conectar ao serviço. Tente novamente em instantes.',
  });
  assert.equal(
    await failure('/catalogo'),
    'Não foi possível conectar ao serviço. Tente novamente em instantes.',
  );
  respondWith(422, {
    message: 'Selecione a origem.',
    errors: { origin_id: ['Selecione a origem.'], destination_id: ['Selecione o destino.'] },
  });
  assert.equal(await failure('/catalogo'), 'Selecione a origem. Selecione o destino.');
});

/** Answer each request in turn, recording the method, path and CSRF token it was sent with. */
function respondInTurn(
  answers: [number, unknown][],
): { method: string; path: string; token: string | null }[] {
  const sent: { method: string; path: string; token: string | null }[] = [];
  globalThis.fetch = async (input, init) => {
    sent.push({
      method: init?.method ?? 'GET',
      path: String(input),
      token: new Headers(init?.headers).get('X-CSRF-TOKEN'),
    });
    const [status, body] = answers.shift() ?? [500, {}];
    return new Response(JSON.stringify(body), {
      status,
      headers: { 'Content-Type': 'application/json' },
    });
  };
  return sent;
}

test('a change sent with an expired session token gets a new token and is sent once more', async () => {
  const sent = respondInTurn([
    [200, { data: { user: null, csrf_token: 'old-token' } }],
    [419, { message: 'CSRF token mismatch.' }],
    [200, { data: { user: null, csrf_token: 'new-token' } }],
    [201, { data: { id: 7 } }],
  ]);

  const result = await api<{ id: number }>('/buscas', { method: 'POST', body: '{}' });

  assert.equal(result.data.id, 7);
  assert.deepEqual(sent, [
    { method: 'GET', path: '/api/v1/sessao', token: null },
    { method: 'POST', path: '/api/v1/buscas', token: 'old-token' },
    { method: 'GET', path: '/api/v1/sessao', token: null },
    { method: 'POST', path: '/api/v1/buscas', token: 'new-token' },
  ]);
});

test('a change refused again with a new token is not sent a third time', async () => {
  const sent = respondInTurn([
    [419, { message: 'CSRF token mismatch.' }],
    [200, { data: { user: null, csrf_token: 'newer-token' } }],
    [419, { message: 'CSRF token mismatch.' }],
  ]);

  try {
    await api('/favoritos', { method: 'POST', body: '{}' });
    assert.fail('The request was expected to fail.');
  } catch (error) {
    assert.equal(errorMessage(error), 'Sua sessão expirou. Atualize a página e tente novamente.');
  }
  assert.equal(sent.length, 3);
});
