import type { NextRequest } from 'next/server';

export const dynamic = 'force-dynamic';

async function proxy(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> },
): Promise<Response> {
  const { path } = await context.params;
  if (path.some(segment => segment === '.' || segment === '..')) {
    return Response.json({ message: 'Endereço inválido.' }, { status: 400 });
  }
  const backend = process.env.BACKEND_URL ?? 'http://127.0.0.1:8002';
  const url = new URL(
    `/api/v1/${path.map(encodeURIComponent).join('/')}${request.nextUrl.search}`,
    backend,
  );
  const headers = new Headers({ Accept: 'application/json' });

  for (const name of ['cookie', 'content-type', 'x-csrf-token', 'x-xsrf-token', 'user-agent']) {
    const value = request.headers.get(name);
    if (value) {
      headers.set(name, value);
    }
  }

  headers.set('X-Forwarded-Host', request.headers.get('host') ?? request.nextUrl.host);
  headers.set('X-Forwarded-Proto', request.nextUrl.protocol.replace(':', ''));

  try {
    const upstream = await fetch(url, {
      method: request.method,
      headers,
      body: ['GET', 'HEAD'].includes(request.method) ? undefined : await request.arrayBuffer(),
      cache: 'no-store',
      redirect: 'manual',
      signal: AbortSignal.timeout(30000),
    });
    const responseHeaders = new Headers({
      'Content-Type': upstream.headers.get('content-type') ?? 'application/json',
      'Cache-Control': 'no-store',
    });

    for (const cookie of upstream.headers.getSetCookie()) {
      responseHeaders.append('Set-Cookie', cookie);
    }
    for (const name of ['retry-after', 'x-ratelimit-limit', 'x-ratelimit-remaining']) {
      const value = upstream.headers.get(name);
      if (value) {
        responseHeaders.set(name, value);
      }
    }

    return new Response(upstream.body, { status: upstream.status, headers: responseHeaders });
  } catch {
    return Response.json(
      { message: 'Não foi possível conectar ao serviço. Tente novamente em instantes.' },
      { status: 503 },
    );
  }
}

export { proxy as GET, proxy as POST, proxy as PUT, proxy as PATCH, proxy as DELETE };
