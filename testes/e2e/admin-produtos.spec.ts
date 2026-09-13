import { test, expect } from '@playwright/test';
import {
  arquivoFixture,
  cadastrarProdutoUi,
  entrarComoAdmin,
  preencherDescricaoQuill,
  reiniciar,
} from './suporte/aplicacao';

test.describe('Administração de produtos', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('listagem vazia, cadastro completo, persistência e SKU com zeros @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await page.goto('/admin/produtos');
    await expect(page.getByText('Nenhum produto cadastrado ainda.')).toBeVisible();

    await cadastrarProdutoUi(page, {
      nome: 'Bolsa E2E',
      sku: '00123',
      preco: '49.90',
      quantidade: '7',
      cores: ['Vermelho', 'Azul'],
      arquivos: [arquivoFixture('capa.jpg'), arquivoFixture('lado.png'), arquivoFixture('extra.webp')],
    });

    await expect(page.getByRole('cell', { name: '00123' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'R$ 49,90' })).toBeVisible();
    await page.reload();
    await expect(page.locator('td.text-gray-800', { hasText: 'Bolsa E2E' })).toBeVisible();
    await expect(page.locator('td.font-mono', { hasText: '00123' })).toBeVisible();

    await page.getByRole('link', { name: 'Editar' }).click();
    await expect(page.getByLabel('SKU')).toHaveValue('00123');
    await expect(page.getByLabel('Nome')).toHaveValue('Bolsa E2E');
    await expect(page.locator('[data-rotulo-capa]:not([hidden])')).toHaveCount(1);
  });

  test('valida obrigatoriedade, limites, SKU duplicado e arquivos', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Primeiro',
      sku: 'SKU-UNICO',
      arquivos: [arquivoFixture('capa.jpg')],
    });

    await page.goto('/admin/produtos/criar');
    await page.getByRole('button', { name: 'Cadastrar produto' }).click();
    await expect(page.getByText('Corrija os campos destacados.')).toBeVisible();

    await page.getByLabel('Nome').fill('x'.repeat(256));
    await page.getByLabel('Preço (BRL)').fill('-1');
    await page.getByLabel('Descrição curta').fill('y'.repeat(501));
    await page.getByLabel('Estoque').fill('-3');
    await page.getByLabel('SKU').fill('SKU-UNICO');
    await page.getByRole('checkbox', { name: 'Vermelho', exact: true }).check();
    await preencherDescricaoQuill(page, 'Texto');
    await page.locator('#imagens').setInputFiles(arquivoFixture('invalido.txt'));
    await page.getByRole('button', { name: 'Cadastrar produto' }).click();
    await expect(page.getByText('Corrija os campos destacados.')).toBeVisible();

    await page.getByLabel('Nome').fill('Segundo');
    await page.getByLabel('Preço (BRL)').fill('10.00');
    await page.getByLabel('Descrição curta').fill('Curta');
    await page.getByLabel('Estoque').fill('1');
    await page.getByLabel('SKU').fill('SKU-GRANDE');
    await page.getByRole('checkbox', { name: 'Vermelho', exact: true }).check();
    await preencherDescricaoQuill(page, 'Texto visível');
    await page.locator('#imagens').setInputFiles(arquivoFixture('grande.bin'));
    await page.getByRole('button', { name: 'Cadastrar produto' }).click();
    await expect(page.getByText('Corrija os campos destacados.')).toBeVisible();
  });

  test('edição, cancelar, rejeição sem sobrescrever, imagens e exclusão @principal', async ({ page }) => {
    await entrarComoAdmin(page);
    await cadastrarProdutoUi(page, {
      nome: 'Manter',
      sku: 'MANTER-01',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await cadastrarProdutoUi(page, {
      nome: 'Alvo',
      sku: 'ALVO-01',
      quantidade: '4',
      arquivos: [arquivoFixture('capa.jpg'), arquivoFixture('lado.png')],
    });

    await page.locator('tr', { hasText: 'MANTER-01' }).getByRole('link', { name: 'Editar' }).click();
    const nomeOriginal = await page.getByLabel('Nome').inputValue();
    await page.getByLabel('Nome').fill('');
    await page.getByRole('button', { name: 'Salvar alterações' }).click();
    await expect(page.getByText('Corrija os campos destacados.')).toBeVisible();
    await page.getByRole('link', { name: 'Voltar' }).click();
    await expect(page.locator('td.text-gray-800', { hasText: nomeOriginal })).toBeVisible();

    await page.locator('tr', { hasText: 'ALVO-01' }).getByRole('link', { name: 'Editar' }).click();
    await page.getByLabel('Nome').fill('Alvo Editado');
    await page.getByLabel('Preço (BRL)').fill('59.90');
    await preencherDescricaoQuill(page, '');
    await page.getByRole('button', { name: 'Salvar alterações' }).click();
    await expect(page.getByText(/texto visível|Corrija/i)).toBeVisible();
    await preencherDescricaoQuill(page, 'Descrição atualizada com <script>alert(1)</script> ok.');
    await page.getByRole('checkbox', { name: 'Remover ao salvar' }).nth(1).check();
    await page.locator('#imagens').setInputFiles(arquivoFixture('extra.webp'));
    await page.getByRole('button', { name: 'Salvar alterações' }).click();
    await expect(page.getByText('Produto atualizado.')).toBeVisible();

    await page.locator('tr', { hasText: 'ALVO-01' }).getByRole('button', { name: 'Excluir' }).click();
    await expect(page.getByRole('heading', { name: 'Excluir produto' })).toBeVisible();
    await page.getByRole('button', { name: 'Cancelar' }).click();
    await expect(page.locator('td.text-gray-800', { hasText: 'Alvo Editado' })).toBeVisible();

    await page.locator('tr', { hasText: 'ALVO-01' }).getByRole('button', { name: 'Excluir' }).click();
    await page.locator('div.fixed').filter({ hasText: 'ALVO-01' }).getByRole('button', { name: 'Excluir' }).click();
    await expect(page.getByText('Produto excluído.')).toBeVisible();
    await expect(page.locator('td.font-mono', { hasText: 'ALVO-01' })).toHaveCount(0);
    await expect(page.locator('td.font-mono', { hasText: 'MANTER-01' })).toBeVisible();

    expect((await page.goto('/admin/produtos/99999/editar'))?.status()).toBe(404);
  });

  test('paginação da listagem administrativa', async ({ page }) => {
    reiniciar(['--com-catalogo=16']);
    await entrarComoAdmin(page);
    await page.goto('/admin/produtos');
    await expect(page.getByRole('link', { name: '2' }).or(page.getByLabel('Next')).or(page.getByText('Próximo'))).toBeVisible();
    await page.getByRole('link', { name: '2' }).first().click();
    await expect(page).toHaveURL(/page=2/);
  });

  test('preview das imagens escolhidas e checkboxes de cores', async ({ page }) => {
    await entrarComoAdmin(page);
    await page.goto('/admin/produtos/criar');
    await page.getByRole('button', { name: 'Escolher imagens' }).focus();
    await expect(page.getByRole('button', { name: 'Escolher imagens' })).toBeFocused();
    await page.getByRole('checkbox', { name: 'Azul', exact: true }).check();
    await page.locator('#imagens').setInputFiles([arquivoFixture('capa.jpg'), arquivoFixture('lado.png')]);
    await expect(page.getByText('capa.jpg')).toBeVisible();
    await expect(page.getByText('lado.png')).toBeVisible();
    await expect(page.getByText('Nova — entra ao final').first()).toBeVisible();

    await cadastrarProdutoUi(page, {
      nome: 'Com preview',
      sku: 'PREV-01',
      arquivos: [arquivoFixture('capa.jpg'), arquivoFixture('lado.png')],
    });
    await page.getByRole('link', { name: 'Editar' }).click();
    await page.getByRole('checkbox', { name: 'Remover ao salvar' }).nth(1).check();
    await expect(page.locator('[data-rotulo-remocao]:not([hidden])')).toHaveCount(1);
    await page.locator('#imagens').setInputFiles(arquivoFixture('extra.webp'));
    await expect(page.getByText('extra.webp')).toBeVisible();
    await expect(page.getByText(/Total previsto: 2/)).toBeVisible();
    await expect(page.locator('[data-rotulo-capa]:not([hidden])')).toHaveCount(1);
  });
});
