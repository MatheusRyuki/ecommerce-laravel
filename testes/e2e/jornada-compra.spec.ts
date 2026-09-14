import { test, expect } from '@playwright/test';
import {
  artisan,
  cadastrarProdutoUi,
  escolherCor,
  entrar,
  entrarComoAdmin,
  arquivoFixture,
  concluirVerificacao,
  reiniciar,
  sairDaConta,
  senhaPadrao,
} from './suporte/aplicacao';

test.describe('Jornada integrada de compra', () => {
  test('cadastro, verificação, favorito, mescla, endereço, cupom, frete e pedido @principal', async ({ page, context }) => {
    reiniciar();
    await entrarComoAdmin(page);
    await page.goto('/admin/categorias/criar');
    await page.getByLabel('Nome').fill('Bolsas');
    await page.getByRole('button', { name: 'Salvar' }).click();

    await cadastrarProdutoUi(page, {
      nome: 'Jornada Bolsa',
      sku: 'JOR-0001',
      preco: '20.00',
      quantidade: '5',
      cores: ['Verde'],
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.goto('/admin/cupons/criar');
    await page.getByLabel('Código').fill('CINCO');
    await page.getByLabel('Tipo').selectOption('fixo');
    await page.getByLabel('Valor').fill('5.00');
    await page.getByRole('checkbox', { name: 'Ativo' }).check();
    await page.getByRole('button', { name: 'Salvar' }).click();

    await page.goto('/admin/frete/criar');
    await page.getByLabel('CEP início').fill('00000000');
    await page.getByLabel('CEP fim').fill('99999999');
    await page.getByLabel('Valor (BRL)').fill('8.00');
    await page.getByRole('button', { name: 'Salvar' }).click();

    await sairDaConta(page, 'Administrador E2E');

    const visitante = await context.browser()?.newContext();
    if (!visitante) {
      throw new Error('contexto');
    }
    const loja = await visitante.newPage();
    await loja.goto('http://127.0.0.1:8003/');
    await loja.getByRole('link', { name: 'Jornada Bolsa' }).click();
    await escolherCor(loja, 'Verde');
    await loja.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    await loja.goto('/register');
    await loja.getByLabel('Nome').fill('Cliente Jornada');
    await loja.getByLabel('E-mail').fill('jornada@e2e.test');
    await loja.getByLabel('Senha', { exact: true }).fill(senhaPadrao);
    await loja.getByLabel('Confirmar senha').fill(senhaPadrao);
    await loja.getByRole('button', { name: 'Cadastrar' }).click();
    await expect(loja).toHaveURL(/verify-email/);
    await concluirVerificacao(loja);

    await loja.goto('/');
    await loja.getByRole('link', { name: 'Jornada Bolsa' }).click();
    await loja.getByRole('button', { name: 'Salvar nos favoritos' }).click();
    await loja.goto('/favoritos');
    await expect(loja.getByRole('link', { name: 'Jornada Bolsa' })).toBeVisible();

    await loja.goto('/enderecos/criar');
    await loja.getByLabel('Destinatário').fill('Cliente Jornada');
    await loja.getByLabel('CEP').fill('01310-100');
    await loja.getByLabel('Logradouro').fill('Av Paulista');
    await loja.getByLabel('Número').fill('1000');
    await loja.getByLabel('Bairro').fill('Bela Vista');
    await loja.getByLabel('Cidade').fill('São Paulo');
    await loja.getByLabel('UF').selectOption('SP');
    await loja.getByRole('button', { name: 'Salvar' }).click();

    await loja.goto('/carrinho');
    await loja.getByLabel('Cupom').fill('CINCO');
    await loja.getByRole('button', { name: 'Aplicar cupom' }).click();
    await loja.getByRole('button', { name: 'Usar este endereço' }).click();
    await loja.getByRole('link', { name: 'Revisar pedido' }).click();
    await expect(loja.getByRole('heading', { name: 'Revisar pedido' })).toBeVisible();
    await expect(loja.getByText('Total dos produtos')).toBeVisible();
    await expect(loja.getByText('Desconto')).toBeVisible();
    await expect(loja.getByText('Frete')).toBeVisible();
    await expect(loja.locator('.resumo-pedido-carrinho__linha').last()).toContainText('Total');
    await loja.getByRole('button', { name: 'Confirmar pedido' }).click();
    await expect(loja.getByRole('heading', { name: /Pedido PED-/ })).toBeVisible();
    await expect(loja.getByText('Nenhum pagamento foi processado. O pedido permanece aguardando pagamento.')).toBeVisible();

    await visitante.close();
  });
});
