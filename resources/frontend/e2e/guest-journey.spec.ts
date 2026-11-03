import { expect, planTrip, test } from './fixtures';

test('a guest plans a bus and metro trip and pays a single fare at the integrated terminal', async ({
  page,
}) => {
  await planTrip(page, {
    origin: 'Boa Viagem',
    destination: 'Recife Antigo',
    modes: ['Ônibus', 'Metrô'],
  });

  await expect(page.getByText('Saída amanhã às 14:30.')).toBeVisible();
  const fastest = page.getByRole('link').filter({ hasText: 'Mais rápida' });
  await expect(fastest).toContainText('47 min');
  await expect(fastest).toContainText('Chegada às 15:17');
  await expect(fastest).toContainText(/R\$\s3,80/);
  await expect(fastest).toContainText('Ônibus 042 → Metrô CENTRO → Caminhada');

  await fastest.click();
  await expect(page).toHaveURL(/\/trajetos\/\d+/);
  const stops = page.locator('.journey-leg');
  await expect(stops).toHaveCount(4);
  await expect(stops.nth(0)).toContainText('14:30Boa Viagem');
  await expect(stops.nth(0)).toContainText('042 Ônibus até Estação Joana Bezerra');
  await expect(stops.nth(0)).toContainText('Embarque às 14:36, depois de espera de 6 min.');
  await expect(stops.nth(1)).toContainText('14:54Estação Joana Bezerra');
  await expect(stops.nth(1)).toContainText('Integração tarifária, sem nova tarifa.');
  await expect(stops.nth(2)).toContainText('15:10Estação Recife');
  await expect(stops.nth(2)).toContainText('A pé até Recife Antigo');
  await expect(stops.nth(3)).toHaveText('15:17Recife Antigo');
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

test('the schedules show each way of a line as a timetable of its next vehicles', async ({
  page,
}) => {
  await page.goto('/horarios');
  await page.getByText('081 Cordeiro → Recife Antigo').click();

  const toRecifeAntigo = page.getByRole('table', { name: 'Sentido Recife Antigo' });
  await expect(toRecifeAntigo.getByRole('rowheader')).toHaveText([
    'Várzea',
    'Terminal Integrado CDU',
    'Cordeiro',
    'Derby',
    'Boa Vista',
    'Recife Antigo',
  ]);
  await expect(toRecifeAntigo.getByRole('row').nth(1).getByRole('cell')).toHaveText([
    /^(\d{2}:\d{2}|–já passou)$/,
    /^(\d{2}:\d{2}|–já passou)$/,
    /^(\d{2}:\d{2}|–já passou)$/,
  ]);
  await expect(page.getByRole('table', { name: 'Sentido Várzea' })).toBeVisible();
  await expect(page.getByText('BRT Caxangá (TI CDU → Boa Vista)')).toBeVisible();
  await expect(page.getByText('BRT BRT')).toHaveCount(0);
});
