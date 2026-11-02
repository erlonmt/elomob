import { expect, planTrip, test } from './fixtures';

test('a guest plans a bus and metro trip and pays a single fare at the integrated terminal', async ({
  page,
}) => {
  await planTrip(page, {
    origin: 'Boa Viagem',
    destination: 'Recife Antigo',
    modes: ['Ônibus', 'Metrô'],
  });

  const fastest = page.getByRole('link').filter({ hasText: 'Mais rápida' });
  await expect(fastest).toContainText('47 min');
  await expect(fastest).toContainText(/R\$\s3,80/);
  await expect(fastest).toContainText('Ônibus 042 → Metrô CENTRO → Caminhada');

  await fastest.click();
  await expect(page).toHaveURL(/\/trajetos\/\d+/);
  const steps = page.locator('.steps');
  await expect(steps).toContainText('Boa Viagem → Estação Joana Bezerra');
  await expect(steps).toContainText('Integração tarifária: sem nova tarifa');
  await expect(steps).toContainText('Estação Recife → Recife Antigo');
  await expect(page.locator('.journey-summary')).toContainText(/R\$\s3,80/);
});

test('without the metro the trip changes and the results say which criteria chose it', async ({
  page,
}) => {
  await planTrip(page, { origin: 'Boa Viagem', destination: 'Recife Antigo', modes: ['Ônibus'] });

  const cards = page.getByRole('link').filter({ hasText: 'Ver detalhes' });
  await expect(cards.first()).toBeVisible();
  await expect(page.getByText('Metrô CENTRO')).toHaveCount(0);
  await expect(page.getByRole('link').filter({ hasText: 'Mais econômica' })).toContainText(
    /R\$\s3,80/,
  );
});

test('a guest is invited to sign in to keep places and history', async ({ page }) => {
  await page.goto('/rotas');

  await expect(page.getByRole('link', { name: 'Entrar ou criar conta' })).toBeVisible();
  await page.getByRole('link', { name: 'Entrar ou criar conta' }).click();
  await expect(page).toHaveURL(/\/entrar/);
});
