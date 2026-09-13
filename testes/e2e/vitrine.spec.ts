import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  artisan,
  cadastrarProdutoUi,
  entrarComoAdmin,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Vitrine e detalhes', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('estado vazio, produto real, ordenação, paginação e clique no cartão', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByText('Ainda não há produtos na loja.')).toBeVisible();

    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Primeiro criado',
      sku: 'VIT-01',
      preco: '10.00',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await cadastrarProdutoUi(page, {
      nome: 'Mais recente',
      sku: 'VIT-02',
      preco: '20.50',
      arquivos: [arquivoFixture('lado.png')],
    });

    await page.goto('/');
    const titulos = page.locator('a.title');
    await expect(titulos.first()).toHaveText('Mais recente');
    await expect(page.getByText('R$ 20,50')).toBeVisible();
    await titulos.filter({ hasText: 'Primeiro criado' }).click();
    await expect(page.getByRole('heading', { name: 'Primeiro criado' })).toBeVisible();
    await expect(page.getByText('SKU')).toBeVisible();
    await expect(page.getByText('VIT-01')).toBeVisible();
    await expect(page.getByText('R$ 10,00')).toBeVisible();
    await expect(page.getByText('Em estoque: 10')).toBeVisible();
  });

  test('galeria, sem estoque, inexistente, sanitização e reflexão administrativa', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Galeria',
      sku: 'GAL-01',
      quantidade: '0',
      cores: ['Verde', 'Azul'],
      descricaoHtml: '<p>Visível <script>alert(1)</script> e <b>negrito</b></p>',
      descricaoCurta: 'Curta <b>nao html</b>',
      arquivos: [arquivoFixture('capa.jpg'), arquivoFixture('lado.png'), arquivoFixture('extra.webp')],
    });

    await page.goto('/');
    await expect(page.locator('.wsus__product_item .new', { hasText: 'Indisponível' })).toBeVisible();
    await page.getByRole('link', { name: 'Galeria' }).first().click();
    await expect(page.getByRole('img', { name: /Miniatura 2/ })).toBeVisible();
    await page.getByRole('img', { name: /Miniatura 2/ }).click();
    await expect(page.getByText('Indisponível', { exact: true })).toBeVisible();
    await expect(page.getByText('Comprar agora')).toBeVisible();
    await expect(page.locator('.wsus__product_details_menu_contant script')).toHaveCount(0);
    await expect(page.locator('.wsus__product_details_menu_contant').locator('b, strong')).toContainText('negrito');
    await expect(page.getByText('Curta <b>nao html</b>')).toBeVisible();

    expect((await page.goto('/produtos/999999'))?.status()).toBe(404);

    artisan(['e2e:atualizar-produto', '--sku=GAL-01', '--quantidade=8', '--preco=33.30', '--nome=Galeria Nova']);
    await page.goto('/');
    await expect(page.getByText('Galeria Nova')).toBeVisible();
    await expect(page.getByText('R$ 33,30')).toBeVisible();
  });

  test('paginação da vitrine (12 por página)', async ({ page }) => {
    reiniciar(['--com-catalogo=13']);
    await page.goto('/');
    await expect(page.getByRole('link', { name: '2', exact: true })).toBeVisible();
    await page.getByRole('link', { name: '2', exact: true }).click();
    await expect(page).toHaveURL(/page=2/);
    await expect(page.locator('a.title')).toHaveCount(1);
  });
});
