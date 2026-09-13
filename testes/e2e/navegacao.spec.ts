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

  test('menus da loja, links de template sem funcionalidade e teclado no login', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Início' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Loja' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Sobre' })).toHaveAttribute('href', '#');
    await expect(page.getByRole('link', { name: 'Serviços' })).toHaveAttribute('href', '#');
    await expect(page.getByRole('link', { name: 'Portfólio' })).toHaveAttribute('href', '#');
    await expect(page.getByRole('link', { name: 'Blog' })).toHaveAttribute('href', '#');
    await expect(page.getByRole('link', { name: 'Fale conosco' })).toHaveAttribute('href', '#');

    await page.goto('/login');
    await page.getByLabel('E-mail').focus();
    await page.keyboard.type('admin@e2e.test');
    await page.keyboard.press('Tab');
    await page.keyboard.type('SenhaE2e!234');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/dashboard/);
  });

  test('celular: menu da loja, admin e carrinho utilizáveis @somente-mobile', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Mobile',
      sku: 'MOB-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.goto('/');
    await page.getByRole('button', { name: 'Abrir menu' }).click();
    await page.getByRole('link', { name: 'Loja' }).click();
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
});
