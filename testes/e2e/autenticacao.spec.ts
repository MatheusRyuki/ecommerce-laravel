import { test, expect } from '@playwright/test';
import {
  artisan,
  entrar,
  adminEmail,
  reiniciar,
  senhaPadrao,
  sairDaConta,
} from './suporte/aplicacao';

test.describe('Autenticação', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('cadastro válido, validações e e-mail duplicado @principal', async ({ page }) => {
    await page.goto('/register');
    await page.getByRole('button', { name: 'Cadastrar' }).click();
    await expect(page.locator('#name')).toBeVisible();

    await page.getByLabel('Nome').fill('Maria E2E');
    await page.getByLabel('E-mail').fill('maria@e2e.test');
    await page.getByLabel('Senha', { exact: true }).fill('curta');
    await page.getByLabel('Confirmar senha').fill('outra');
    await page.getByRole('button', { name: 'Cadastrar' }).click();
    await expect(page.getByText('A confirmação de senha não confere.')).toBeVisible();

    await page.getByLabel('Senha', { exact: true }).fill(senhaPadrao);
    await page.getByLabel('Confirmar senha').fill(senhaPadrao);
    await page.getByRole('button', { name: 'Cadastrar' }).click();
    await expect(page).toHaveURL(/dashboard/);

    await sairDaConta(page, 'Maria E2E');
    await expect(page).toHaveURL('/');

    await page.goto('/register');
    await page.getByLabel('Nome').fill('Outra');
    await page.getByLabel('E-mail').fill('maria@e2e.test');
    await page.getByLabel('Senha', { exact: true }).fill(senhaPadrao);
    await page.getByLabel('Confirmar senha').fill(senhaPadrao);
    await page.getByRole('button', { name: 'Cadastrar' }).click();
    await expect(page.getByText(/já está|já foi/i)).toBeVisible();
  });

  test('login válido, inválido, logout e proteção @principal', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=comum@e2e.test', `--senha=${senhaPadrao}`, '--nome=Comum']);

    await page.goto('/login');
    await expect(page.getByRole('link', { name: 'Criar conta' })).toBeVisible();
    await page.getByLabel('E-mail').fill('comum@e2e.test');
    await page.getByLabel('Senha', { exact: true }).fill('errada-errada');
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page.getByText(/credenciais/i)).toBeVisible();

    await entrar(page, 'comum@e2e.test');
    await expect(page).toHaveURL(/dashboard/);

    await sairDaConta(page, 'Comum');

    await page.goto('/perfil');
    await expect(page).toHaveURL(/login/);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/login/);
  });

  test('lembrar de mim restaura acesso após perder a sessão comum', async ({ page, context }) => {
    artisan(['e2e:preparar-usuario', '--email=lembrar@e2e.test', `--senha=${senhaPadrao}`, '--nome=Lembrar']);
    await entrar(page, 'lembrar@e2e.test', senhaPadrao, true);
    await page.goto('/perfil');
    await expect(page.getByLabel('Nome')).toHaveValue('Lembrar');

    const cookies = await context.cookies();
    const sessao = cookies.find((c) => c.name === 'e2e_sessao');
    const lembrar = cookies.filter((c) => c.name.startsWith('remember_web_'));
    expect(lembrar.length).toBeGreaterThan(0);
    expect(sessao).toBeTruthy();

    await context.clearCookies();
    await context.addCookies(lembrar);
    await page.goto('/perfil');
    await expect(page.getByLabel('Nome')).toHaveValue('Lembrar');
  });

  test('destinos após login por perfil', async ({ page }) => {
    await entrar(page, adminEmail);
    await expect(page).toHaveURL(/admin\/produtos$/);
    await sairDaConta(page, 'Administrador E2E');

    artisan(['e2e:preparar-usuario', '--email=destino@e2e.test', `--senha=${senhaPadrao}`, '--nome=Destino']);
    await entrar(page, 'destino@e2e.test');
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.getByRole('link', { name: 'Loja' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Carrinho' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Perfil' }).first()).toBeVisible();
  });
});
