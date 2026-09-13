import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  cadastrarProdutoUi,
  entrarComoAdmin,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Duplicação de produto', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('duplica pela interface com SKU novo, cópia oculta e original intacto @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Bolsa original',
      sku: 'ORIG-E2E',
      preco: '49.90',
      quantidade: '8',
      cores: ['Verde'],
      arquivos: [arquivoFixture('capa.jpg'), arquivoFixture('lado.png')],
    });

    const linhaOriginal = page.locator('tr', { hasText: 'ORIG-E2E' });
    await expect(linhaOriginal.getByText('Publicado')).toBeVisible();
    await expect(linhaOriginal.locator('img')).toBeVisible();
    const capaOriginal = await linhaOriginal.locator('img').getAttribute('src');

    await linhaOriginal.getByRole('link', { name: 'Duplicar' }).click();
    await expect(page.getByRole('heading', { name: 'Duplicar produto' }).first()).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Publicado na loja' })).toBeDisabled();
    await expect(page.getByText('A cópia nasce oculta até você publicá-la na edição.')).toBeVisible();
    await expect(page.getByLabel('SKU')).toHaveValue('');
    await expect(page.getByLabel('Nome')).toHaveValue('Bolsa original');

    await page.getByLabel('SKU').fill('COPIA-E2E');
    await page.getByRole('button', { name: 'Salvar cópia' }).click();
    await expect(page.getByText('Produto cadastrado.')).toBeVisible();

    const origem = page.locator('tr', { hasText: 'ORIG-E2E' });
    const copia = page.locator('tr', { hasText: 'COPIA-E2E' });
    await expect(origem.getByText('Publicado')).toBeVisible();
    await expect(origem.getByText('8', { exact: true })).toBeVisible();
    await expect(origem.locator('img')).toHaveAttribute('src', capaOriginal ?? '');
    await expect(copia.getByText('Oculto')).toBeVisible();

    await origem.getByRole('link', { name: 'Editar' }).click();
    await expect(page.getByLabel('SKU')).toHaveValue('ORIG-E2E');
    await expect(page.locator('[data-imagens-atuais] img')).toHaveCount(2);

    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Bolsa original' })).toHaveCount(1);
    await page.goto('/admin/produtos');
    await page.locator('tr', { hasText: 'COPIA-E2E' }).getByRole('link', { name: 'Editar' }).click();
    await expect(page.getByLabel('SKU')).toHaveValue('COPIA-E2E');
    await expect(page.getByRole('checkbox', { name: 'Publicado na loja' })).not.toBeChecked();
  });
});
