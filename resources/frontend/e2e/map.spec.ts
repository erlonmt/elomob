import { expect, test, TILE_SERVER } from './fixtures';

const tileLevel = (url: string) => Number(new URL(url).pathname.split('/')[1]);

test('the network map draws OpenStreetMap tiles under the stops, with attribution', async ({
  page,
  tileRequests,
}) => {
  await page.goto('/mapa');

  await expect(page.locator('[data-stop]')).toHaveCount(17);
  await expect.poll(() => tileRequests.length).toBeGreaterThan(0);
  for (const url of tileRequests) {
    expect(url).toMatch(new RegExp(`^${TILE_SERVER}/\\d+/\\d+/\\d+\\.png$`));
  }
  await expect(page.getByRole('link', { name: 'colaboradores do OpenStreetMap' })).toHaveAttribute(
    'href',
    'https://www.openstreetmap.org/copyright',
  );
});

test('zooming in asks for more detailed tiles', async ({ page, tileRequests }) => {
  await page.goto('/mapa');
  await expect.poll(() => tileRequests.length).toBeGreaterThan(0);
  const firstLevel = Math.max(...tileRequests.map(tileLevel));

  await page.getByRole('button', { name: 'Aproximar mapa' }).click();
  await page.getByRole('button', { name: 'Aproximar mapa' }).click();

  await expect.poll(() => Math.max(...tileRequests.map(tileLevel))).toBeGreaterThan(firstLevel);
});

test('without the tile server the map still shows the stops and lets people pick one', async ({
  page,
}) => {
  const errors: Error[] = [];
  page.on('pageerror', error => errors.push(error));
  await page.route(`${TILE_SERVER}/**`, route => route.abort('internetdisconnected'));
  await page.goto('/mapa');

  await page.locator('[data-stop][aria-label="Boa Viagem"] circle').first().click();
  await expect(page.locator('.selected-stop')).toContainText('Boa Viagem');
  expect(errors).toEqual([]);
});
