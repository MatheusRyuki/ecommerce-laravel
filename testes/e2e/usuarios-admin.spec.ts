import { test, expect } from '@playwright/test';
import {
  artisan,
  entrarComoAdmin,
  reiniciar,
  senhaPadrao,
} from './suporte/aplicacao';

test.describe('Usuários administrativos', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('busca, promove, rebaixa e protege o último administrador pela interface @principal', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=temp.admin@e2e.test', `--senha=${senhaPadrao}`, '--nome=Temporario E2E']);
    await entrarComoAdmin(page);

    await page.goto('/perfil');
    await page.getByRole('button', { name: 'Excluir conta' }).click();
    await page.getByPlaceholder('Senha').fill(senhaPadrao);
    await page.getByRole('button', { name: 'Excluir conta' }).nth(1).click();
    await expect(page.getByText('Não é possível excluir o último administrador.')).toBeVisible();

    await page.goto('/admin/usuarios');
    await expect(page.getByRole('cell', { name: 'admin@e2e.test', exact: true })).toBeVisible();
    await page.getByPlaceholder('Nome ou e-mail').fill('temp.admin@e2e.test');
    await page.getByRole('button', { name: 'Buscar' }).click();
    await expect(page.getByRole('cell', { name: 'Temporario E2E' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'admin@e2e.test', exact: true })).toHaveCount(0);

    const linhaTemp = page.locator('tr', { hasText: 'temp.admin@e2e.test' });
    await expect(linhaTemp.getByText('Cliente')).toBeVisible();
    await linhaTemp.getByRole('button', { name: 'Promover' }).click();
    await expect(page.getByText('Usuário promovido a administrador.')).toBeVisible();
    await expect(linhaTemp.getByText('Administrador')).toBeVisible();

    await linhaTemp.getByRole('button', { name: 'Remover admin' }).click();
    await expect(page.getByText('Privilégio de administrador removido.')).toBeVisible();
    await expect(linhaTemp.getByText('Cliente')).toBeVisible();

    await page.getByRole('link', { name: 'Limpar' }).click();
    const linhaPropria = page.locator('tr', { hasText: 'admin@e2e.test' }).filter({ has: page.getByRole('cell', { name: 'admin@e2e.test', exact: true }) });
    await expect(linhaPropria.getByText('Você')).toBeVisible();
    await expect(linhaPropria.getByRole('button', { name: 'Remover admin' })).toHaveCount(0);
    await expect(linhaPropria.getByRole('button', { name: 'Promover' })).toHaveCount(0);
  });
});
