import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, type Page } from '@playwright/test';

const raiz = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

export const senhaPadrao = 'SenhaE2e!234';
export const adminEmail = 'admin@e2e.test';

export function lerEnvE2e(chave: string): string {
  const conteudo = fs.readFileSync(path.join(raiz, '.env.e2e'), 'utf8');
  const linha = conteudo.split('\n').find((item) => item.startsWith(`${chave}=`));
  if (!linha) {
    throw new Error(`Chave ${chave} ausente em .env.e2e`);
  }

  return linha.slice(chave.length + 1).replace(/^"|"$/g, '').trim();
}

export function artisan(args: string[]): string {
  let ultimo: unknown;

  for (let tentativa = 1; tentativa <= 3; tentativa++) {
    try {
      return execFileSync('./vendor/bin/sail', ['artisan', ...args, '--env=e2e', '--no-interaction'], {
        cwd: raiz,
        encoding: 'utf8',
        timeout: 120_000,
      });
    } catch (erro) {
      ultimo = erro;
      if (tentativa < 3) {
        execFileSync('sleep', ['2'], { encoding: 'utf8' });
      }
    }
  }

  throw ultimo;
}

export function reiniciar(opcoes: string[] = []): void {
  artisan(['e2e:reiniciar', ...opcoes]);
}

export function tokenE2e(): string {
  return lerEnvE2e('E2E_TOKEN');
}

export function marcadorE2e(): string {
  return fs.readFileSync(path.join(raiz, 'storage/e2e/marcador.txt'), 'utf8').trim();
}

export async function confirmarIsolamentoHttp(page: Page): Promise<void> {
  const resposta = await page.request.get('/_e2e/diagnostico', {
    headers: { 'X-Token-E2e': tokenE2e() },
  });
  expect(resposta.ok(), await resposta.text()).toBeTruthy();
  const corpo = await resposta.json();
  expect(corpo.ambiente).toBe('e2e');
  expect(corpo.banco).toBe('ecommerce_e2e');
  expect(corpo.usuario_banco).toBe('ecommerce_e2e');
  expect(corpo.disco_produtos).toBe('publico_e2e');
  expect(String(corpo.raiz_disco_produtos)).toContain('storage/e2e');
  expect(corpo.marcador).toBe(marcadorE2e());
  expect(String(corpo.correio)).toContain('storage/e2e/correio');
}

export async function entrar(page: Page, email: string, senha = senhaPadrao, lembrar = false): Promise<void> {
  await page.goto('/login');

  if (await page.getByLabel('E-mail').count() === 0) {
    await abrirMenuDaConta(page);
    await page.getByRole('link', { name: 'Sair' }).click();
    await page.goto('/login');
  }

  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Senha', { exact: true }).fill(senha);
  if (lembrar) {
    await page.getByLabel('Lembrar de mim').check();
  }
  await page.locator('form').filter({ has: page.getByLabel('E-mail') }).getByRole('button', { name: 'Entrar' }).click();
  await expect(page).toHaveURL(/\/(dashboard|admin\/produtos|perfil|verify-email)/);
}

export async function entrarComoAdmin(page: Page): Promise<void> {
  await entrar(page, adminEmail);
}

export async function abrirMenuDaConta(page: Page): Promise<void> {
  const menu = page.getByRole('button', { name: 'Menu' });
  if (await menu.isVisible()) {
    await menu.click();
    return;
  }

  await page.locator('nav button').filter({ has: page.locator('svg.fill-current') }).click();
}

export async function sairDaConta(page: Page, nome: string): Promise<void> {
  const acionador = page.getByRole('button', { name: nome });
  if (await acionador.isVisible()) {
    await expect(acionador).toBeVisible();
    await acionador.click();
  } else {
    await page.getByRole('button', { name: 'Menu' }).click();
    await expect(page.locator('nav').getByText(nome, { exact: true }).last()).toBeVisible();
  }
  await page.getByRole('link', { name: 'Sair' }).click();
}

export async function abrirMaisDaLinha(linha: import('@playwright/test').Locator): Promise<void> {
  await linha.getByRole('button', { name: 'Mais' }).click();
}

export async function preencherDescricaoQuill(page: Page, texto: string): Promise<void> {
  const editor = page.locator('#editor-descricao .ql-editor');
  await editor.click();
  await editor.fill(texto);
}

export async function cadastrarProdutoUi(
  page: Page,
  dados: {
    nome: string;
    sku: string;
    preco?: string;
    quantidade?: string;
    cores?: string[];
    descricaoCurta?: string;
    descricao?: string;
    descricaoHtml?: string;
    arquivos: string[];
  },
): Promise<void> {
  await page.goto('/admin/produtos/criar');
  await page.getByLabel('Nome').fill(dados.nome);
  await page.getByLabel('Preço (BRL)').fill(dados.preco ?? '49.90');
  const cores = dados.cores ?? ['Vermelho'];
  for (const cor of cores) {
    await page.getByRole('checkbox', { name: cor, exact: true }).check();
  }
  await page.getByLabel('Descrição curta').fill(dados.descricaoCurta ?? 'Resumo do produto de teste.');
  await page.getByLabel('Estoque').fill(dados.quantidade ?? '10');
  await page.getByLabel('SKU').fill(dados.sku);
  if (dados.descricaoHtml) {
    await page.locator('#editor-descricao .ql-editor').evaluate((el, html) => {
      el.innerHTML = html;
    }, dados.descricaoHtml);
  } else {
    await preencherDescricaoQuill(page, dados.descricao ?? 'Descrição visível do produto.');
  }
  await page.locator('#imagens').setInputFiles(dados.arquivos);
  await page.getByRole('button', { name: 'Cadastrar produto' }).click();
  await expect(page.getByText('Produto cadastrado.')).toBeVisible();
}

export function arquivoFixture(nome: string): string {
  return path.join(raiz, 'testes/e2e/fixtures', nome);
}

export async function escolherCor(page: Page, cor: string): Promise<void> {
  await page.locator('select[name="cor"]').selectOption(cor);
}

export function ultimoLinkRedefinicao(): string {
  const log = artisan(['e2e:ultimo-correio']);
  const match = log.match(/https?:\/\/[^\s"]+\/reset-password\/[^\s"]+/);
  if (!match) {
    throw new Error(`Link de redefinição não encontrado no correio:\n${log}`);
  }

  return match[0].replace(/&amp;/g, '&');
}

export async function concluirVerificacao(page: Page): Promise<void> {
  const link = ultimoLinkVerificacao();
  if (!link) {
    throw new Error('Link de verificação não encontrado no correio.');
  }
  await page.goto(link);
}

export function ultimoLinkVerificacao(): string | null {
  const log = artisan(['e2e:ultimo-correio']);
  const match = log.match(/https?:\/\/[^\s"]+\/verify-email\/[^\s"]+/);

  return match ? match[0].replace(/&amp;/g, '&') : null;
}

export async function expectTotal(page: Page, valor: string): Promise<void> {
  await expect(page.locator('.total-produtos-carrinho', { hasText: valor })).toBeVisible();
}
