import { test, expect } from '@playwright/test';
import {
  artisan,
  entrar,
  reiniciar,
  sairDaConta,
  senhaPadrao,
  ultimoLinkRedefinicao,
} from './suporte/aplicacao';

test.describe('Recuperação de senha', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('solicita pela UI, abre o link capturado e redefine; rejeita token já usado', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=reset@e2e.test', `--senha=${senhaPadrao}`, '--nome=Reset']);

    await page.goto('/forgot-password');
    await page.getByLabel('E-mail').fill('reset@e2e.test');
    await page.getByRole('button', { name: 'Enviar link de redefinição' }).click();
    await expect(page.getByText('Enviamos o link de redefinição de senha por e-mail.')).toBeVisible();

    const link = ultimoLinkRedefinicao();
    await page.goto(link);
    await page.getByLabel('Senha', { exact: true }).fill('Resetada!234');
    await page.getByLabel('Confirmar senha').fill('Resetada!234');
    await page.getByRole('button', { name: 'Redefinir senha' }).click();

    await entrar(page, 'reset@e2e.test', 'Resetada!234');
    await expect(page).toHaveURL(/dashboard/);

    await sairDaConta(page, 'Reset');

    await page.goto(link);
    await page.getByLabel('Senha', { exact: true }).fill('OutraSenha!234');
    await page.getByLabel('Confirmar senha').fill('OutraSenha!234');
    await page.getByRole('button', { name: 'Redefinir senha' }).click();
    await expect(page.getByText(/inválid|expir|token/i)).toBeVisible();
  });

  test('link inválido não redefine', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=reset2@e2e.test', `--senha=${senhaPadrao}`, '--nome=Reset Dois']);
    await page.goto('/reset-password/token-invalido');
    await page.getByLabel('E-mail').fill('reset2@e2e.test');
    await page.getByLabel('Senha', { exact: true }).fill('Resetada!234');
    await page.getByLabel('Confirmar senha').fill('Resetada!234');
    await page.getByRole('button', { name: 'Redefinir senha' }).click();
    await expect(page.getByText(/inválid|expir|token/i)).toBeVisible();
  });
});
