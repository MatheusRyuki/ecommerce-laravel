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
  sairDaConta,
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
  await sairDaConta(page, 'Administrador E2E');
  await page.goto('/');
  await page.getByRole('link', { name: extra?.sku === 'CARO' ? 'Produto caro' : 'Bolsa carrinho' }).first().click();
}

test.describe('Carrinho', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('vazio, adicionar, acumular cor, cores distintas, totais exatos e checkout indisponível @principal', async ({ page }) => {
    await page.goto('/carrinho');
    await expect(page.getByText('Seu carrinho está vazio.')).toBeVisible();
    await expect(page.getByText('Resumo do pedido')).toHaveCount(0);
    const contador = page.getByRole('link', { name: 'Carrinho, 0 itens' });
    await expect(contador).toBeVisible();

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
    await expect(page.getByText(/Conclua login|Nenhum pagamento/)).toBeVisible();
    await expect(page.locator('.checkout-indisponivel')).toHaveAttribute('aria-disabled', 'true');
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
    await expect(page.getByText('Resumo do pedido')).toHaveCount(0);

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
    if (await page.getByRole('button', { name: 'Sessao' }).isVisible()) {
      await page.getByRole('button', { name: 'Sessao' }).click();
    } else {
      await page.getByRole('button', { name: 'Menu' }).click();
    }
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

  test('botões mais e menos alteram quantidade, respeitam mínimo e persistem após atualizar @principal', async ({ page }) => {
    await prepararProduto(page);
    await escolherCor(page, 'Vermelho');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await page.goto('/carrinho');

    const quantidade = page.locator('.formulario-qtd-carrinho input[name="quantidade"]');
    const aviso = page.getByText('Quantidade ainda não salva');
    await expect(quantidade).toHaveValue('1');
    await expect(aviso).toBeHidden();

    await page.getByRole('button', { name: 'Aumentar quantidade' }).click();
    await expect(quantidade).toHaveValue('2');
    await expect(aviso).toBeVisible();

    await page.getByRole('button', { name: 'Atualizar' }).click();
    await expect(page.getByText('O carrinho foi atualizado.')).toBeVisible();
    await expect(quantidade).toHaveValue('2');
    await expectTotal(page, 'R$ 99,80');

    await page.getByRole('button', { name: 'Diminuir quantidade' }).click();
    await expect(quantidade).toHaveValue('1');
    await expect(aviso).toBeVisible();
    await page.getByRole('button', { name: 'Atualizar' }).click();
    await expect(quantidade).toHaveValue('1');
    await expectTotal(page, 'R$ 49,90');

    await page.getByRole('button', { name: 'Diminuir quantidade' }).click();
    await expect(quantidade).toHaveValue('1');
    await expect(aviso).toBeHidden();
    await page.getByRole('button', { name: 'Atualizar' }).click();
    await expect(quantidade).toHaveValue('1');
    await expectTotal(page, 'R$ 49,90');
  });

  test('quantidade zero e fracionária são bloqueadas pelo formulário nativo sem alterar o carrinho', async ({ page }) => {
    await prepararProduto(page);
    await escolherCor(page, 'Vermelho');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await page.goto('/carrinho');

    const quantidade = page.locator('.formulario-qtd-carrinho input[name="quantidade"]');
    await quantidade.fill('0');
    await page.getByRole('button', { name: 'Atualizar' }).click();
    expect(await quantidade.evaluate((el: HTMLInputElement) => el.validity.valid)).toBe(false);
    await expect(page).toHaveURL(/\/carrinho$/);
    await expect(quantidade).toHaveAttribute('data-salvo', '1');

    await quantidade.fill('1.5');
    await page.getByRole('button', { name: 'Atualizar' }).click();
    expect(await quantidade.evaluate((el: HTMLInputElement) => el.validity.valid)).toBe(false);
    await expect(page).toHaveURL(/\/carrinho$/);
    await expect(quantidade).toHaveAttribute('data-salvo', '1');
    await expectTotal(page, 'R$ 49,90');
  });

  test('resumo, badge e colunas permanecem alinhados com quantidade pendente @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Bolsa carrinho',
      sku: 'ALINHA-01',
      cores: ['Verde'],
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await cadastrarProdutoUi(page, {
      nome: 'Produto com nome bastante longo para quebrar o alinhamento da linha',
      sku: 'ALINHA-02',
      preco: '12.00',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await sairDaConta(page, 'Administrador E2E');
    await page.goto('/');
    await page.getByRole('link', { name: 'Bolsa carrinho' }).first().click();
    await escolherCor(page, 'Verde');
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await page.goto('/');
    await page.getByRole('link', { name: 'Produto com nome bastante longo para quebrar o alinhamento da linha' }).first().click();
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    const qtdBolsa = page.locator('tr', { hasText: 'Bolsa carrinho' }).locator('.formulario-qtd-carrinho input[name="quantidade"]');
    await qtdBolsa.fill('3');
    await qtdBolsa.dispatchEvent('input');
    await expect(page.locator('.qtd-carrinho-pendente').filter({ visible: true })).toHaveCount(1);
    await expectTotal(page, 'R$ 61,90');
    await expect(page.getByText('R$ 49,90').first()).toBeVisible();

    const icone = await page.locator('.cabecalho-loja__carrinho-icone').boundingBox();
    const badge = await page.locator('.cabecalho-loja__carrinho-contador').boundingBox();
    expect(icone).toBeTruthy();
    expect(badge).toBeTruthy();
    expect(badge!.y + badge!.height).toBeLessThan(icone!.y + icone!.height);
    expect(badge!.x).toBeGreaterThan(icone!.x);

    const excesso = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(excesso).toBeLessThanOrEqual(1);
  });
});
