import { expect, test as base, type Page } from '@playwright/test';

/** A 1×1 PNG served in place of the OpenStreetMap tiles. */
const TILE = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=',
  'base64',
);

export const TILE_SERVER = 'https://tile.openstreetmap.org';

/**
 * Every test answers the map tiles itself, so it neither depends on the internet nor loads the OpenStreetMap
 * servers. `tileRequests` lists the tile addresses the page asked for, in order.
 */
export const test = base.extend<{ tileRequests: string[] }>({
  tileRequests: [
    async ({ page }, use) => {
      const requests: string[] = [];
      await page.route(`${TILE_SERVER}/**`, route => {
        requests.push(route.request().url());
        return route.fulfill({ contentType: 'image/png', body: TILE });
      });
      await use(requests);
    },
    { auto: true },
  ],
});

export { expect };

/** Tomorrow's date in Recife with the given time, as a datetime-local field takes it. */
export function tomorrowAt(time: string): string {
  const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
  const date = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Recife' }).format(tomorrow);
  return `${date}T${time}`;
}

const MODES = ['Ônibus', 'Metrô', 'Bicicleta', 'Táxi', 'Aplicativo'];

/**
 * Plan a trip on the search screen, leaving tomorrow at 14:30 with only the given modes, and wait for the
 * results.
 */
export async function planTrip(
  page: Page,
  { origin, destination, modes }: { origin: string; destination: string; modes: string[] },
): Promise<void> {
  await page.goto('/buscar');
  await page.getByLabel('Escolha a origem').selectOption({ label: origin });
  await page.getByLabel('Escolha o destino').selectOption({ label: destination });
  await page.getByRole('button', { name: 'Escolher horário' }).click();
  await page.getByLabel('Data e horário no Recife').fill(tomorrowAt('14:30'));
  for (const mode of MODES) {
    await page.getByRole('checkbox', { name: mode, exact: true }).setChecked(modes.includes(mode));
  }
  await page.getByRole('button', { name: 'Buscar Trajeto' }).click();
  await expect(page).toHaveURL(/\/resultados\/\d+/);
}

/** A fresh e-mail address for a test account, unique across parallel tests and browsers. */
export function testEmail(): string {
  return `e2e.${Date.now()}.${Math.random().toString(36).slice(2, 8)}@elomob.test`;
}
