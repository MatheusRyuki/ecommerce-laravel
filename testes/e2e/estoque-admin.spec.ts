import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  cadastrarProdutoUi,
  entrarComoAdmin,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Estoque administrativo', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('entrada, saída, ajuste e recusa de saldo negativo pela interface @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Bolsa estoque',
      sku: 'EST-E2E',
      quantidade: '10',
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.locator('tr', { hasText: 'EST-E2E' }).getByRole('button', { name: 'Mais' }).click();
    await page.locator('tr', { hasText: 'EST-E2E' }).getByRole('link', { name: 'Estoque' }).click();
    await expect(page.locator('p', { hasText: 'Quantidade atual:' })).toContainText('10');

    await page.getByLabel('Tipo').selectOption('entrada');
    await page.getByLabel('Quantidade').fill('5');
    await page.getByLabel('Motivo').fill('Reposição');
    await page.getByRole('button', { name: 'Registrar' }).click();
    await expect(page.getByText('Estoque atualizado.')).toBeVisible();
    await expect(page.getByText('Quantidade atual:')).toBeVisible();
    await expect(page.locator('p', { hasText: 'Quantidade atual:' })).toContainText('15');
    await expect(page.getByRole('cell', { name: 'Entrada' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Reposição' })).toBeVisible();

    await page.getByLabel('Tipo').selectOption('saida');
    await page.getByLabel('Quantidade').fill('3');
    await page.getByLabel('Motivo').fill('Avaria');
    await page.getByRole('button', { name: 'Registrar' }).click();
    await expect(page.locator('p', { hasText: 'Quantidade atual:' })).toContainText('12');
    await expect(page.getByRole('cell', { name: 'Avaria' })).toBeVisible();

    await page.getByLabel('Tipo').selectOption('ajuste');
    await page.getByLabel('Quantidade').fill('20');
    await page.getByLabel('Motivo').fill('Inventário');
    await page.getByRole('button', { name: 'Registrar' }).click();
    await expect(page.locator('p', { hasText: 'Quantidade atual:' })).toContainText('20');
    await expect(page.getByRole('cell', { name: 'Inventário' })).toBeVisible();

    const historicoAntes = await page.locator('tbody tr').count();
    await page.getByLabel('Tipo').selectOption('saida');
    await page.getByLabel('Quantidade').fill('50');
    await page.getByLabel('Motivo').fill('Tentativa inválida');
    await page.getByRole('button', { name: 'Registrar' }).click();
    await expect(page.getByText('O estoque não pode ficar negativo.')).toBeVisible();
    await expect(page.locator('p', { hasText: 'Quantidade atual:' })).toContainText('20');
    await expect(page.getByRole('cell', { name: 'Tentativa inválida' })).toHaveCount(0);
    await expect(page.locator('tbody tr')).toHaveCount(historicoAntes);
  });
});
