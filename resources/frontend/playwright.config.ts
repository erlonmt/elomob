import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests drive the whole application started by Docker Compose (`docker compose up`), through
 * the Next.js proxy, the way a person uses it. E2E_BASE_URL points them at another address.
 */
export default defineConfig({
  testDir: './e2e',
  timeout: 60_000,
  expect: { timeout: 15_000 },
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  workers: process.env.CI ? 2 : undefined,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8000',
    locale: 'pt-BR',
    timezoneId: 'America/Recife',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'celular', use: { ...devices['Pixel 7'] } },
  ],
});
