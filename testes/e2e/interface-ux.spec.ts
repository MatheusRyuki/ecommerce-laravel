import { test, expect, type Page } from '@playwright/test';
import {
  artisan,
  arquivoFixture,
  cadastrarProdutoUi,
  abrirMenuDaConta,
  entrar,
  entrarComoAdmin,
  reiniciar,
  senhaPadrao,
} from './suporte/aplicacao';

async function geometriaNav(page: Page) {
  return page.evaluate(() => {
    const d = document.documentElement;
    const visiveis = [...document.querySelectorAll('nav a, nav button')].filter((el) => {
      const r = el.getBoundingClientRect();

      return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden';
    });
    let overlap = false;
    for (let i = 0; i < visiveis.length; i++) {
      for (let j = i + 1; j < visiveis.length; j++) {
        const a = visiveis[i].getBoundingClientRect();
        const b = visiveis[j].getBoundingClientRect();
        const cruza = a.right > b.left + 4 && a.left < b.right - 4 && a.bottom > b.top + 4 && a.top < b.bottom - 4;
        if (cruza) {
          overlap = true;
        }
      }
    }

    return {
      overflow: d.scrollWidth - d.clientWidth,
      overlap,
    };
  });
}

test.describe('Interface UX das correções', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('navegação autenticada, geometria e destinos @principal', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=ux.nav@e2e.test', `--senha=${senhaPadrao}`, '--nome=Nav Comum']);
    await page.setViewportSize({ width: 1920, height: 900 });
    await entrar(page, 'ux.nav@e2e.test');
    await expect(page).toHaveURL(/\/dashboard/);
    const contaDesktop = page.locator('#nav-conta');
    if (await contaDesktop.isVisible()) {
      await contaDesktop.click();
    } else {
      await page.getByRole('button', { name: 'Menu' }).click();
      await page.locator('#nav-conta-movel').click();
    }
    await expect(page).toHaveURL(/\/dashboard/);
    await abrirMenuDaConta(page);
    await page.getByRole('navigation').getByRole('link', { name: 'Perfil' }).click();
    await expect(page).toHaveURL(/\/perfil/);
    await expect(page.locator('#nav-conta')).not.toHaveAttribute('aria-current', 'page');

    await entrarComoAdmin(page);
    await expect(page).toHaveURL(/admin\/produtos/);
    await cadastrarProdutoUi(page, {
      nome: 'Nav Produto',
      sku: 'NAV-UX',
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await expect(page.getByRole('link', { name: 'Freeit' }).first()).toBeVisible();

    for (const largura of [320, 390, 640, 768, 992, 1024, 1280, 1920]) {
      await page.setViewportSize({ width: largura, height: 800 });
      await page.goto('/admin/produtos');
      const geo = await geometriaNav(page);
      expect(geo.overflow, `overflow em ${largura}px`).toBeLessThanOrEqual(0);
      expect(geo.overlap, `sobreposição em ${largura}px`).toBe(false);

      const menu = page.getByRole('button', { name: 'Menu' });
      if (await menu.isVisible()) {
        await expect(menu).toHaveAttribute('aria-expanded', 'false');
        await menu.click();
        await expect(menu).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: 'Meus pedidos' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Pedidos da loja' })).toBeVisible();
        await menu.click();
      } else {
        await expect(page.getByRole('link', { name: 'Meus pedidos' })).toBeVisible();
        await page.getByRole('button', { name: 'Administração' }).click();
        await expect(page.getByRole('button', { name: 'Administração' })).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: 'Pedidos da loja' })).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('button', { name: 'Administração' })).toHaveAttribute('aria-expanded', 'false');
      }
    }

    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/admin/produtos');
    const linha = page.locator('table tbody tr').first();
    await expect(linha.getByRole('link', { name: 'Editar' })).toBeVisible();
    await expect(linha.getByRole('button', { name: 'Excluir' })).toBeVisible();
    await linha.getByRole('button', { name: 'Mais' }).click();
    await expect(linha.getByRole('link', { name: 'Duplicar' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(linha.getByRole('link', { name: 'Duplicar' })).toBeHidden();
    await expect(linha.getByRole('button', { name: 'Mais' })).toBeFocused();
  });

  test('confirma exclusão de endereço, carrinho vazio e checkout preenchido @principal', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 900 });
    await entrarComoAdmin(page);
    await page.goto('/admin/frete/criar');
    await page.getByLabel('CEP início').fill('00000000');
    await page.getByLabel('CEP fim').fill('99999999');
    await page.getByLabel('Valor (BRL)').fill('8.00');
    await page.getByRole('button', { name: 'Salvar' }).click();
    await cadastrarProdutoUi(page, {
      nome: 'UX Checkout',
      sku: 'UX-CK',
      preco: '20.00',
      quantidade: '4',
      cores: ['Verde'],
      arquivos: [arquivoFixture('capa.jpg')],
    });
    await page.goto('/admin/cupons/criar');
    await page.getByLabel('Código').fill('UXCUP');
    await page.getByLabel('Tipo').selectOption('fixo');
    await page.getByLabel('Valor').fill('2.00');
    await page.getByRole('checkbox', { name: 'Ativo' }).check();
    await page.getByRole('button', { name: 'Salvar' }).click();
    await expect(page.getByText('Valor fixo (BRL)')).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Ativo' })).toBeVisible();

    artisan(['e2e:preparar-usuario', '--email=ux.conta@e2e.test', `--senha=${senhaPadrao}`, '--nome=Conta UX']);
    await entrar(page, 'ux.conta@e2e.test');

    await page.goto('/carrinho');
    await expect(page.getByRole('heading', { name: 'Carrinho' })).toBeVisible();
    await expect(page.getByText('Seu carrinho está vazio.')).toBeVisible();
    await expect(page.getByText('Resumo do pedido')).toHaveCount(0);
    await expect(page.getByLabel('Cupom')).toHaveCount(0);
    await page.goto('/checkout');
    await expect(page).toHaveURL(/\/carrinho/);
    await expect(page.getByText('O carrinho está vazio.')).toBeVisible();

    await page.goto('/');
    await page.getByRole('link', { name: /UX Checkout/ }).click();
    await page.getByRole('button', { name: 'Salvar nos favoritos' }).click();
    await page.getByRole('button', { name: 'Adicionar ao carrinho' }).click();

    await page.goto('/enderecos/criar');
    await page.getByLabel('Destinatário').fill('Conta UX');
    await page.getByLabel('CEP').fill('01310-100');
    await page.getByLabel('Logradouro').fill('Av Paulista');
    await page.getByLabel('Número').fill('100');
    await page.getByLabel('Bairro').fill('Bela Vista');
    await page.getByLabel('Cidade').fill('São Paulo');
    await page.getByLabel('UF').selectOption('SP');
    await page.getByRole('button', { name: 'Salvar' }).click();

    await page.goto('/enderecos');
    const excluir = page.getByRole('button', { name: 'Excluir' });
    await excluir.click();
    await expect(page.getByRole('heading', { name: 'Excluir endereço' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('heading', { name: 'Excluir endereço' })).toBeHidden();
    await expect(excluir).toBeFocused();
    await expect(page.locator('li').filter({ hasText: 'Av Paulista' })).toBeVisible();

    await page.goto('/carrinho');
    await page.getByRole('button', { name: 'Usar este endereço' }).click();
    await page.getByRole('link', { name: 'Revisar pedido' }).click();
    await expect(page.getByRole('heading', { name: 'Revisar pedido' })).toBeVisible();
    await expect(page.getByText('Total dos produtos')).toBeVisible();
    await expect(page.locator('.resumo-pedido-carrinho').getByText('Frete')).toBeVisible();
  });
});
