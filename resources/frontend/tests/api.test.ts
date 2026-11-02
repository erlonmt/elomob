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
