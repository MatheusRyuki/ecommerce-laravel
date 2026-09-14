import { test, expect, type Page } from '@playwright/test';
import {
  arquivoFixture,
  cadastrarProdutoUi,
  entrarComoAdmin,
  expectTotal,
  reiniciar,
} from './suporte/aplicacao';

async function expectBarraPrincipalEmUmaLinha(page: Page): Promise<void> {
  const logo = page.locator('.navbar-brand');
  const carrinho = page.locator('.cabecalho-loja__carrinho');
  const menu = page.getByRole('button', { name: 'Menu' });
  const caixaLogo = await logo.boundingBox();
  const caixaCarrinho = await carrinho.boundingBox();
  const caixaMenu = await menu.boundingBox();

  expect(caixaLogo, 'logo visível na barra').toBeTruthy();
  expect(caixaCarrinho, 'carrinho visível na barra').toBeTruthy();
  expect(caixaMenu, 'botão do menu visível na barra').toBeTruthy();

  const centro = (caixa: { y: number; height: number }) => caixa.y + caixa.height / 2;
  expect(Math.abs(centro(caixaLogo!) - centro(caixaCarrinho!))).toBeLessThan(10);
  expect(Math.abs(centro(caixaCarrinho!) - centro(caixaMenu!))).toBeLessThan(10);
  expect(caixaLogo!.x).toBeLessThan(caixaCarrinho!.x);
  expect(caixaCarrinho!.x).toBeLessThan(caixaMenu!.x);
  expect(caixaLogo!.y + caixaLogo!.height).toBeGreaterThan(caixaCarrinho!.y);
  expect(caixaCarrinho!.y + caixaCarrinho!.height).toBeGreaterThan(caixaLogo!.y);
}

async function expectDistribuicaoDesktop(page: Page): Promise<void> {
  const container = page.locator('nav.main_menu .container');
  const logo = page.locator('.navbar-brand');
  const inicio = page.locator('.navbar-nav .nav-link', { hasText: 'Início' });
  const acoes = page.locator('.cabecalho-loja__acoes');
  const carrinho = page.locator('.cabecalho-loja__carrinho');

  const caixaContainer = await container.boundingBox();
  const caixaLogo = await logo.boundingBox();
  const caixaInicio = await inicio.boundingBox();
  const caixaAcoes = await acoes.boundingBox();
  const caixaCarrinho = await carrinho.boundingBox();

  expect(caixaContainer).toBeTruthy();
  expect(caixaLogo).toBeTruthy();
  expect(caixaInicio).toBeTruthy();
  expect(caixaAcoes).toBeTruthy();
  expect(caixaCarrinho).toBeTruthy();

  expect(caixaLogo!.x).toBeLessThan(caixaInicio!.x);
  expect(caixaInicio!.x).toBeLessThan(caixaAcoes!.x);
  expect(caixaLogo!.x + caixaLogo!.width).toBeLessThanOrEqual(caixaInicio!.x + 2);
  expect(caixaInicio!.x + caixaInicio!.width).toBeLessThanOrEqual(caixaCarrinho!.x + 2);

  const meioContainer = caixaContainer!.x + caixaContainer!.width / 2;
  expect(caixaAcoes!.x).toBeGreaterThan(meioContainer);
  expect(caixaContainer!.x + caixaContainer!.width - (caixaAcoes!.x + caixaAcoes!.width)).toBeLessThan(24);

  const centro = (caixa: { y: number; height: number }) => caixa.y + caixa.height / 2;
  expect(Math.abs(centro(caixaLogo!) - centro(caixaInicio!))).toBeLessThan(12);
  expect(Math.abs(centro(caixaInicio!) - centro(caixaAcoes!))).toBeLessThan(12);
}

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
    await expect(page.getByRole('button', { name: 'Menu' })).toBeHidden();

    await page.setViewportSize({ width: 991, height: 800 });
    await expect(page.getByRole('button', { name: 'Menu' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Entrar' })).toBeHidden();
    await expectBarraPrincipalEmUmaLinha(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await expect(page.getByRole('link', { name: 'Entrar' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Menu' })).toBeHidden();
    await expectDistribuicaoDesktop(page);

    await page.setViewportSize({ width: 1920, height: 800 });
    await expectDistribuicaoDesktop(page);
    await expect(page.getByRole('button', { name: 'Menu' })).toBeHidden();

    await page.setViewportSize({ width: 992, height: 800 });
    await expect(page.getByRole('button', { name: 'Menu' })).toBeHidden();
    await expectDistribuicaoDesktop(page);

    await page.setViewportSize({ width: 1280, height: 800 });
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
    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Conta' })).toBeVisible();
    await expectDistribuicaoDesktop(page);
  });

  test('celular: menu da loja, admin e carrinho utilizáveis @somente-mobile', async ({ page }) => {
    await page.goto('/');
    for (const largura of [320, 390, 414]) {
      await page.setViewportSize({ width: largura, height: 844 });
      await expect(page.getByRole('link', { name: 'Carrinho, 0 itens' })).toBeVisible();
      await expect(page.getByRole('link', { name: 'Entrar' })).toBeHidden();
      await expect(page.getByRole('link', { name: 'Criar conta' })).toBeHidden();
      await expectBarraPrincipalEmUmaLinha(page);
    }

    await page.getByRole('link', { name: /Carrinho/ }).focus();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('button', { name: 'Menu' })).toBeFocused();

    await page.getByRole('button', { name: 'Menu' }).click();
    await expect(page.getByRole('link', { name: 'Início' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Entrar' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Criar conta' })).toBeVisible();
    await expectBarraPrincipalEmUmaLinha(page);
    await page.setViewportSize({ width: 320, height: 844 });
    await expectBarraPrincipalEmUmaLinha(page);
    await page.setViewportSize({ width: 414, height: 844 });
    await expectBarraPrincipalEmUmaLinha(page);
    await page.getByRole('button', { name: 'Menu' }).click();

    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Mobile',
      sku: 'MOB-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Conta' })).toBeHidden();
    await expectBarraPrincipalEmUmaLinha(page);
    await page.getByRole('button', { name: 'Menu' }).click();
    await expect(page.getByRole('link', { name: 'Conta' })).toBeVisible();
    await page.getByRole('link', { name: 'Início' }).click();
    await page.getByRole('link', { name: 'Mobile' }).click();
    await expect(page.getByRole('heading', { name: 'Mobile' })).toBeVisible();
    await page.locator('#formulario-adicionar-carrinho input[name="quantidade"]').fill('10');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('O produto foi adicionado ao carrinho.')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Carrinho, 10 itens' })).toBeVisible();
    await expectBarraPrincipalEmUmaLinha(page);
    await expectTotal(page, 'R$ 499,00');

    await page.goto('/admin/produtos');
    await page.getByRole('button', { name: 'Menu' }).click();
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
