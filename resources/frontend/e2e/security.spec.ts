import { expect, test } from './fixtures';

test('pages and the API send headers that keep the app from being framed or sniffed', async ({
  page,
}) => {
  const response = await page.goto('/');
  const headers = response?.headers() ?? {};

  expect(headers['content-security-policy']).toContain("frame-ancestors 'none'");
  expect(headers['content-security-policy']).toContain('img-src');
  expect(headers['content-security-policy']).toContain('https://tile.openstreetmap.org');
  expect(headers['x-content-type-options']).toBe('nosniff');
  expect(headers['referrer-policy']).toBe('strict-origin-when-cross-origin');
  const api = await page.request.get('/api/v1/sessao');
  expect(api.headers()['x-content-type-options']).toBe('nosniff');
});

test('the content security policy blocks nothing the screens need', async ({ page }) => {
  await page.addInitScript(() => {
    const violations: string[] = [];
    Object.assign(window, { cspViolations: violations });
    document.addEventListener('securitypolicyviolation', event =>
      violations.push(`${event.violatedDirective} ${event.blockedURI}`),
    );
  });

  for (const path of ['/', '/buscar', '/mapa', '/horarios', '/integracoes', '/sobre', '/entrar']) {
    await page.goto(path);
    await page.waitForLoadState('networkidle');
    expect(
      await page.evaluate(() => (window as unknown as { cspViolations: string[] }).cspViolations),
      path,
    ).toEqual([]);
  }
});
