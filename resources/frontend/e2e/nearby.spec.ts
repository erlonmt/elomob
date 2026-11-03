import { expect, test } from './fixtures';

test('the GPS on the home screen picks the closest stop as origin and lists the stops near the visitor', async ({
  page,
}) => {
  await page.context().grantPermissions(['geolocation']);
  // About 30 m from the Boa Viagem stop; the Bike Recife station is a few hundred meters away.
  await page.context().setGeolocation({ latitude: -8.1192, longitude: -34.8992 });
  await page.goto('/');
  const nearby = page.locator('.home-network .nearby-card');
  await expect(nearby.first()).toContainText('Ônibus 042');

  await page.getByRole('button', { name: 'GPS' }).click();

  await expect(page.getByLabel('Origem')).toHaveValue(/\d+/);
  await expect(page.getByLabel('Origem').locator('option:checked')).toHaveText('Boa Viagem');
  await expect(page.getByText('As paradas mais perto de você.')).toBeVisible();
  await expect(nearby).toHaveCount(3);
  await expect(nearby.nth(0)).toContainText('Boa Viagem');
  await expect(nearby.nth(0)).toContainText(/Ônibus · \d+ m/);
  await expect(nearby.nth(1)).toContainText('Bike Recife — Est. 12');
  await expect(nearby.nth(1)).toHaveAttribute('href', /\/mapa\?q=Bike/);
});
