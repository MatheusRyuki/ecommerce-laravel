import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  artisan,
  cadastrarProdutoUi,
  escolherCor,
  entrarComoAdmin,
  expectTotal,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Fluxo integrado', () => {
  test('admin cadastra, visitante compra no carrinho e alteração administrativa aparece', async ({ page, context }) => {
    reiniciar();
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Integrado E2E',
      sku: 'INT-0001',
      preco: '15.00',
      quantidade: '6',
      cores: ['Verde'],
      arquivos: [arquivoFixture('capa.jpg')],
    });

    const visitante = await context.browser()?.newContext();
    if (!visitante) {
      throw new Error('Não foi possível abrir o contexto do visitante.');
    }
    const loja = await visitante.newPage();
    await loja.goto('http://127.0.0.1:8003/');
    await loja.getByRole('link', { name: 'Integrado E2E' }).click();
    await escolherCor(loja, 'Verde');
    await loja.getByRole('button', { name: 'Adicionar ao carrinho' }).click();
    await loja.locator('input[name="quantidade"]').fill('2');
    await loja.getByRole('button', { name: 'Atualizar' }).click();
    await expectTotal(loja, 'R$ 30,00');

    artisan(['e2e:atualizar-produto', '--sku=INT-0001', '--preco=18.50', '--nome=Integrado Atualizado']);

    await loja.goto('http://127.0.0.1:8003/');
    await expect(loja.getByText('Integrado Atualizado')).toBeVisible();
    await loja.goto('http://127.0.0.1:8003/carrinho');
    await expect(loja.getByText('Integrado Atualizado')).toBeVisible();
    await expect(loja.getByRole('table').getByText('R$ 18,50')).toBeVisible();
    await expectTotal(loja, 'R$ 37,00');

    await visitante.close();
  });
});
