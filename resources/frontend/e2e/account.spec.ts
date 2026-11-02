import { expect, planTrip, test, testEmail } from './fixtures';

/**
 * One account from sign-up to deletion, so the test leaves nothing behind: every step builds on the previous one.
 */
test('an account keeps preferences, places and history, changes its password and is deleted', async ({
  page,
}) => {
  const email = testEmail();

  await test.step('sign up', async () => {
    await page.goto('/entrar');
    await page.getByRole('button', { name: 'Criar uma conta' }).click();
    await page.getByLabel('Nome').fill('Conta de Teste E2E');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha', { exact: true }).fill('old-pass-123');
    await page.getByLabel('Confirme a senha').fill('old-pass-123');
    await page.getByRole('button', { name: 'Criar conta' }).click();
    await expect(page).toHaveURL(/\/perfil/);
    await expect(page.getByText(email)).toBeVisible();
  });

  await test.step('a wheelchair need changes the planned trip', async () => {
    await page.getByRole('checkbox', { name: 'Cadeira de rodas' }).check();
    await page.getByRole('button', { name: 'Salvar preferências' }).click();
    await expect(page.getByText('Perfil e preferências salvos.')).toBeVisible();

    await planTrip(page, {
      origin: 'Boa Viagem',
      destination: 'Recife Antigo',
      modes: ['Ônibus', 'Metrô'],
    });
    const fastest = page.getByRole('link').filter({ hasText: 'Mais rápida' });
    await expect(fastest).toContainText('Ônibus 061');
    await expect(fastest).not.toContainText('Metrô');
  });

  await test.step('save and remove a favorite place', async () => {
    await page.goto('/rotas');
    await page.getByText('+ Adicionar local favorito').click();
    await page.getByLabel('Nome do local').fill('Casa');
    await page.getByLabel('Destino').selectOption({ label: 'Graças' });
    await page.getByRole('button', { name: 'Salvar local' }).click();
    await expect(page.getByText('Local salvo nos seus favoritos.')).toBeVisible();
    await expect(page.locator('.saved-card')).toContainText('Casa');

    await page.getByRole('button', { name: 'Excluir Casa' }).click();
    await expect(page.getByText('Local removido.')).toBeVisible();
    await expect(page.locator('.saved-card')).toHaveCount(0);
  });

  await test.step('clear the history after confirming on the page', async () => {
    const history = page.locator('.history-section');
    await expect(history).toContainText('Boa Viagem → Recife Antigo');
    await page.getByRole('button', { name: 'Limpar histórico' }).click();
    await page.getByRole('button', { name: 'Apagar histórico' }).click();
    await expect(page.getByText('Histórico apagado.')).toBeVisible();
    await expect(history).not.toContainText('Boa Viagem → Recife Antigo');
  });

  await test.step('change the password and sign in with the new one', async () => {
    await page.goto('/perfil');
    await page.getByText('Trocar senha', { exact: true }).click();
    await page.getByLabel('Senha atual').fill('old-pass-123');
    await page.getByLabel('Nova senha', { exact: true }).fill('new-pass-456');
    await page.getByLabel('Confirme a nova senha').fill('new-pass-456');
    await page.getByRole('button', { name: 'Salvar nova senha' }).click();
    await expect(page.getByText(/Senha alterada\./)).toBeVisible();

    await page.getByRole('button', { name: 'Sair da conta' }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.goto('/entrar');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha', { exact: true }).fill('new-pass-456');
    await page.getByRole('button', { name: 'Entrar', exact: true }).click();
    await expect(page).toHaveURL(/\/perfil/);
  });

  await test.step('delete the account with its password', async () => {
    await page.getByText('Excluir conta', { exact: true }).click();
    await page.getByLabel('Senha', { exact: true }).fill('new-pass-456');
    await page.getByRole('button', { name: 'Excluir minha conta' }).click();
    await expect(page.getByText(/Sua conta foi excluída\./)).toBeVisible();

    await page.goto('/entrar');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha', { exact: true }).fill('new-pass-456');
    await page.getByRole('button', { name: 'Entrar', exact: true }).click();
    await expect(page.getByText('E-mail ou senha incorretos.')).toBeVisible();
  });
});
