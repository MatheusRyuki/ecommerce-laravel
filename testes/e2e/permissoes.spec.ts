import { test, expect } from '@playwright/test';
import { artisan, entrar, entrarComoAdmin, reiniciar, senhaPadrao } from './suporte/aplicacao';

test.describe('Permissões', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('visitante vai ao login nas páginas autenticadas', async ({ page }) => {
    await page.goto('/admin/produtos');
    await expect(page).toHaveURL(/login/);
    await page.goto('/perfil');
    await expect(page).toHaveURL(/login/);
  });

  test('usuário comum não acessa administração nem vê o menu', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=comum@e2e.test', `--senha=${senhaPadrao}`, '--nome=Comum']);
    await entrar(page, 'comum@e2e.test');
    await expect(page.getByRole('link', { name: 'Produtos' })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Administração' })).toHaveCount(0);

    const resposta = await page.goto('/admin/produtos');
    expect(resposta?.status()).toBe(403);
    expect((await page.goto('/admin/produtos/criar'))?.status()).toBe(403);
  });

  test('administrador acessa o painel e os produtos', async ({ page }) => {
    await entrarComoAdmin(page);
    await page.goto('/admin/painel');
    await expect(page).toHaveURL(/admin\/produtos/);
    await expect(page.getByRole('link', { name: 'Cadastrar produto' }).first()).toBeVisible();
  });
});
