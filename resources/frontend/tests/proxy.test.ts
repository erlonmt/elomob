import { strict as assert } from 'node:assert';
import test from 'node:test';
import type { NextRequest } from 'next/server';
import { POST } from '../app/api/v1/[...path]/route.ts';

test('the proxy tells the API the address the request came from, so each client has its own limits', async () => {
  let forwarded = new Headers();
  globalThis.fetch = async (_url, init) => {
    forwarded = new Headers(init?.headers);
    return Response.json({ data: {} }, { status: 201 });
  };
  const request = Object.assign(
    new Request('http://localhost:8000/api/v1/buscas', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Forwarded-For': '198.51.100.7' },
      body: '{}',
    }),
    { nextUrl: new URL('http://localhost:8000/api/v1/buscas') },
  ) as unknown as NextRequest;

  await POST(request, { params: Promise.resolve({ path: ['buscas'] }) });

  assert.equal(forwarded.get('x-forwarded-for'), '198.51.100.7');
});
