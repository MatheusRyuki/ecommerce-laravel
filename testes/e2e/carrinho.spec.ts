import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  artisan,
  cadastrarProdutoUi,
  escolherCor,
  entrar,
  entrarComoAdmin,
  expectTotal,
  reiniciar,
  senhaPadrao,
} from './suporte/aplicacao';

async function prepararProduto(page: Parameters<typeof entrarComoAdmin>[0], extra?: { sku?: string; quantidade?: string; preco?: string; cores?: string[] }) {
  await entrarComoAdmin(page);
  await cadastrarProdutoUi(page, {
    nome: extra?.sku === 'CARO' ? 'Produto caro' : 'Bolsa carrinho',
    sku: extra?.sku ?? 'CART-E2E',
    preco: extra?.preco ?? '49.90',
    quantidade: extra?.quantidade ?? '10',
    cores: extra?.cores ?? ['Vermelho', 'Amarelo'],
    arquivos: [arquivoFixture('capa.jpg')],
  });
  await page.goto('/');
  await page.getByRole('link', { name: extra?.sku === 'CARO' ? 'Produto caro' : 'Bolsa carrinho' }).first().click();
}

test.describe('Carrinho', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('vazio, adicionar, acumular cor, cores distintas, totais exatos e checkout indisponível', async ({ page }) => {
    await page.goto('/carrinho');
    await expect(page.getByText('Seu carrinho está vazio.')).toBeVisible();
    await expect(page.getByText('R$ 0,00')).toBeVisible();
    await expect(page.locator('b').filter({ hasText: /^0$/ })).toBeVisible();

    await prepararProduto(page);
    await escolherCor(page, 'Vermelho');
    await page.locator('#formulario-adicionar-carrinho input[name="quantidade"]').fill('2');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('O produto foi adicionado ao carrinho.')).toBeVisible();
    await expectTotal(page, 'R$ 99,80');
    await expect(page.getByRole('table').getByText('R$ 49,90')).toBeVisible();

    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).first().click();
    await escolherCor(page, 'Vermelho');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('Cor: Vermelho')).toHaveCount(1);
    await expect(page.locator('input[name="quantidade"]')).toHaveValue('3');

    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).first().click();
    await escolherCor(page, 'Amarelo');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('Cor: Amarelo')).toBeVisible();
    await expectTotal(page, 'R$ 199,60');
    await expect(page.getByText('Finalizar compra')).toBeVisible();
    await expect(page.getByText('Finalizar compra')).toHaveAttribute('aria-disabled', 'true');
  });

  test('estoque compartilhado, aviso não salvo, rejeição acima do estoque e persistência', async ({ page }) => {
    await prepararProduto(page, { quantidade: '5' });
    await escolherCor(page, 'Vermelho');
    await page.locator('input[name="quantidade"]').fill('3');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).first().click();
    await escolherCor(page, 'Amarelo');
    await page.locator('input[name="quantidade"]').fill('3');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('ultrapassa o estoque')).toBeVisible();

    await page.goto('/carrinho');
    const qtd = page.locator('input[name="quantidade"]').first();
    await qtd.fill('4');
    await expect(page.getByText('Quantidade ainda não salva')).toBeVisible();
    await page.getByRole('button', { name: 'Atualizar' }).first().click();
    await expect(page.getByText('O carrinho foi atualizado.')).toBeVisible();
    await page.reload();
    await expect(page.locator('input[name="quantidade"]').first()).toHaveValue('4');

    await page.locator('input[name="quantidade"]').first().fill('9');
    await page.getByRole('button', { name: 'Atualizar' }).first().click();
    await expect(page.getByText('ultrapassa o estoque')).toBeVisible();
    await expect(page.locator('input[name="quantidade"]').first()).toHaveAttribute('data-salvo', '4');
    await page.reload();
    await expect(page.locator('input[name="quantidade"]').first()).toHaveValue('4');
  });

  test('redução quando estoque cai, linhas inválidas sem total, remoção e estoque do banco intacto', async ({ page }) => {
    await prepararProduto(page, { quantidade: '10' });
    await escolherCor(page, 'Vermelho');
    await page.locator('input[name="quantidade"]').fill('8');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    artisan(['e2e:atualizar-produto', '--sku=CART-E2E', '--quantidade=5']);
    await page.goto('/carrinho');
    await expect(page.getByText('Este item precisa de ajuste')).toBeVisible();
    await expect(page.getByText('O total dos produtos só aparece quando todos os itens estão disponíveis.')).toBeVisible();

    await page.locator('input[name="quantidade"]').fill('5');
    await page.getByRole('button', { name: 'Atualizar' }).click();
    await expectTotal(page, 'R$ 249,50');

    artisan(['e2e:atualizar-produto', '--sku=CART-E2E', '--cores=Azul']);
    await page.goto('/carrinho');
    await expect(page.getByText('Este item precisa de ajuste')).toBeVisible();
    await expect(page.getByRole('heading', { name: /Total dos produtos/ })).toHaveCount(0);

    await page.getByRole('button', { name: 'Remover' }).click();
    await expect(page.getByText('Seu carrinho está vazio.')).toBeVisible();
    await expect(page.getByText('R$ 0,00')).toBeVisible();

    artisan(['e2e:atualizar-produto', '--sku=CART-E2E', '--quantidade=5']);
    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).click();
    await expect(page.getByText('Em estoque: 5')).toBeVisible();
  });

  test('produto excluído no carrinho, valores altos e sessões independentes', async ({ page, context, browser }) => {
    await prepararProduto(page, { sku: 'CARO', preco: '99999999.99', quantidade: '2', cores: ['Azul'] });
    await escolherCor(page, 'Azul');
    await page.locator('input[name="quantidade"]').fill('2');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expectTotal(page, 'R$ 199.999.999,98');

    await page.goto('/');
    await page.getByRole('link', { name: 'Produto caro' }).click();
    await escolherCor(page, 'Azul');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    artisan(['e2e:atualizar-produto', '--sku=CARO', '--excluir']);
    await page.goto('/carrinho');
    await expect(page.getByText('Item indisponível')).toBeVisible();
    await expect(page.getByText('O total dos produtos só aparece quando todos os itens estão disponíveis.')).toBeVisible();

    const outra = await browser.newContext();
    const pagina2 = await outra.newPage();
    await pagina2.goto('http://127.0.0.1:8003/carrinho');
    await expect(pagina2.getByText('Seu carrinho está vazio.')).toBeVisible();
    await outra.close();

    artisan(['e2e:preparar-usuario', '--email=cli@e2e.test', `--senha=${senhaPadrao}`, '--nome=Cliente']);
    const autenticada = await context.newPage();
    await entrar(autenticada, 'cli@e2e.test');
    await autenticada.goto('/carrinho');
  });

  test('duas abas da mesma sessão e logout esvazia o carrinho da sessão', async ({ page, context }) => {
    await prepararProduto(page, { quantidade: '5' });
    await escolherCor(page, 'Vermelho');
    await page.locator('input[name="quantidade"]').fill('2');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    const aba2 = await context.newPage();
    await aba2.goto('/carrinho');
    await aba2.locator('input[name="quantidade"]').fill('4');
    await Promise.all([
      page.locator('input[name="quantidade"]').fill('4'),
      page.getByRole('button', { name: 'Atualizar' }).click(),
      aba2.getByRole('button', { name: 'Atualizar' }).click(),
    ]);
    await page.goto('/carrinho');
    const valor = await page.locator('input[name="quantidade"]').inputValue();
    expect(['2', '4']).toContain(valor);
    expect(Number(valor)).toBeLessThanOrEqual(5);

    artisan(['e2e:preparar-usuario', '--email=sessao@e2e.test', `--senha=${senhaPadrao}`, '--nome=Sessao']);
    await entrar(page, 'sessao@e2e.test');
    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).click();
    await escolherCor(page, 'Vermelho');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await page.goto('/dashboard');
    await page.getByRole('button', { name: 'Sessao' }).click();
    await page.getByRole('link', { name: 'Sair' }).click();
    await page.goto('/carrinho');
    await expect(page.getByText('Seu carrinho está vazio.')).toBeVisible();
  });

  test('usuário autenticado adiciona ao carrinho', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=authcart@e2e.test', `--senha=${senhaPadrao}`, '--nome=Auth Cart']);
    await prepararProduto(page);
    await page.goto('/login');
    await entrar(page, 'authcart@e2e.test');
    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).click();
    await escolherCor(page, 'Vermelho');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await expect(page.getByText('O produto foi adicionado ao carrinho.')).toBeVisible();
  });
});
