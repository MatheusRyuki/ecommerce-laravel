import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  cadastrarProdutoUi,
  entrarComoAdmin,
  expectTotal,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Navegação e apresentação', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('teclado no formulário do produto e nas ações do carrinho', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Teclado',
      sku: 'TEC-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await page.goto('/');
    await page.getByRole('link', { name: 'Teclado' }).first().click();
    const quantidadeProduto = page.locator('#formulario-adicionar-carrinho input[name="quantidade"]');
    await quantidadeProduto.focus();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(quantidadeProduto).toHaveValue('2');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(page.getByText('O produto foi adicionado ao carrinho.')).toBeVisible();
    await expect(page.locator('.formulario-qtd-carrinho input[name="quantidade"]')).toHaveValue('2');

    await page.locator('.formulario-qtd-carrinho input[name="quantidade"]').focus();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(page.locator('.formulario-qtd-carrinho input[name="quantidade"]')).toHaveValue('3');
    await expect(page.getByText('Quantidade ainda não salva')).toBeVisible();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(page.getByText('O carrinho foi atualizado.')).toBeVisible();
    await expect(page.locator('.formulario-qtd-carrinho input[name="quantidade"]')).toHaveValue('3');
  });

  test('menus da loja, destinos reais e teclado no login de administrador', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Início' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Carrinho, 0 itens' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Entrar' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Criar conta' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Loja' })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Sobre' })).toHaveCount(0);
    await expect(page.locator('a[href="#"]')).toHaveCount(0);

    await page.goto('/login');
    await expect(page.getByRole('heading', { name: 'Entrar' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Criar conta' })).toBeVisible();
    await page.getByLabel('E-mail').focus();
    await page.keyboard.type('admin@e2e.test');
    await page.keyboard.press('Tab');
    await page.keyboard.type('SenhaE2e!234');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/admin\/produtos/);
  });

  test('celular: menu da loja, admin e carrinho utilizáveis @somente-mobile', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Mobile',
      sku: 'MOB-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Carrinho, 0 itens' })).toBeVisible();
    await page.getByRole('button', { name: 'Abrir menu' }).click();
    await page.getByRole('link', { name: 'Início' }).click();
    await page.getByRole('link', { name: 'Mobile' }).click();
    await expect(page.getByRole('heading', { name: 'Mobile' })).toBeVisible();
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('O produto foi adicionado ao carrinho.')).toBeVisible();
    await expectTotal(page, 'R$ 49,90');

    await page.goto('/admin/produtos');
    await page.locator('div.sm\\:hidden button').click();
    await expect(page.getByRole('link', { name: 'Produtos' }).first()).toBeVisible();
  });

  test('foco volta ao fechar o modal de exclusão com Escape e Cancelar @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Foco',
      sku: 'FOCO-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await page.goto('/admin/produtos');
    const botao = page.getByRole('button', { name: 'Excluir' });
    await botao.click();
    await expect(page.getByRole('heading', { name: 'Excluir produto' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('heading', { name: 'Excluir produto' })).toBeHidden();
    await expect(botao).toBeFocused();

    await botao.click();
    await expect(page.getByRole('heading', { name: 'Excluir produto' })).toBeVisible();
    await page.getByRole('button', { name: 'Cancelar' }).click();
    await expect(page.getByRole('heading', { name: 'Excluir produto' })).toBeHidden();
    await expect(botao).toBeFocused();
  });

  test('cartão da vitrine abre o detalhe pelo teclado sem hover', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Acesso teclado',
      sku: 'TEC-CARD',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await page.goto('/');
    const cartao = page.locator('.wsus__product_item__link').filter({ hasText: 'Acesso teclado' });
    await cartao.focus();
    await expect(page.getByText('Ver produto').first()).toBeVisible();
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/produtos\/\d+/);
    await expect(page.getByRole('heading', { name: 'Acesso teclado' })).toBeVisible();
  });
});
