import { test, expect } from '@playwright/test';
import { artisan, entrar, reiniciar, senhaPadrao } from './suporte/aplicacao';

test.describe('Perfil, verificação e confirmação de senha', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('atualiza nome e e-mail e rejeita e-mail ocupado', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=perfil@e2e.test', `--senha=${senhaPadrao}`, '--nome=Perfil']);
    artisan(['e2e:preparar-usuario', '--email=ocupado@e2e.test', `--senha=${senhaPadrao}`, '--nome=Ocupado']);
    await entrar(page, 'perfil@e2e.test');
    await page.goto('/perfil');

    await page.getByLabel('Nome').fill('Perfil Novo');
    await page.getByLabel('E-mail').fill('ocupado@e2e.test');
    await page.getByRole('button', { name: 'Salvar' }).first().click();
    await expect(page.getByText(/já está|já foi/i)).toBeVisible();

    await page.getByLabel('E-mail').fill('perfil.novo@e2e.test');
    await page.getByRole('button', { name: 'Salvar' }).first().click();
    await expect(page.getByText('Salvo.')).toBeVisible();
    await page.reload();
    await expect(page.getByLabel('Nome')).toHaveValue('Perfil Novo');
    await expect(page.getByLabel('E-mail')).toHaveValue('perfil.novo@e2e.test');
  });

  test('altera senha e rejeita senha atual incorreta', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=conta.senha@e2e.test', `--senha=${senhaPadrao}`, '--nome=Conta Senha']);
    await entrar(page, 'conta.senha@e2e.test');
    await page.goto('/perfil');

    await page.getByLabel('Senha atual').fill('nao-e-essa-senha');
    await page.getByLabel('Nova senha').fill('NovaSenha!234');
    await page.getByLabel('Confirmar senha').fill('NovaSenha!234');
    await page.getByRole('button', { name: 'Salvar' }).nth(1).click();
    await expect(page.getByText(/não confere|incorreta|não coincide|inválid/i)).toBeVisible();

    await page.getByLabel('Senha atual').fill(senhaPadrao);
    await page.getByLabel('Nova senha').fill('NovaSenha!234');
    await page.getByLabel('Confirmar senha').fill('NovaSenha!234');
    await page.getByRole('button', { name: 'Salvar' }).nth(1).click();

    await page.getByRole('button', { name: 'Conta Senha' }).click();
    await page.getByRole('link', { name: 'Sair' }).click();
    await entrar(page, 'conta.senha@e2e.test', 'NovaSenha!234');
    await expect(page).toHaveURL(/dashboard/);
  });

  test('exclusão: modal, cancelar, senha errada e confirmação da conta de teste', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=apagar@e2e.test', `--senha=${senhaPadrao}`, '--nome=Apagar']);
    await entrar(page, 'apagar@e2e.test');
    await page.goto('/perfil');

    await page.getByRole('button', { name: 'Excluir conta' }).click();
    await expect(page.getByRole('heading', { name: 'Tem certeza de que deseja excluir a conta?' })).toBeVisible();
    await page.getByRole('button', { name: 'Cancelar' }).click();
    await expect(page.getByRole('heading', { name: 'Tem certeza de que deseja excluir a conta?' })).toBeHidden();

    await page.getByRole('button', { name: 'Excluir conta' }).click();
    await page.getByPlaceholder('Senha').fill('errada');
    await page.getByRole('button', { name: 'Excluir conta' }).nth(1).click();
    await expect(page.getByText(/não confere|incorreta|obrigatório/i)).toBeVisible();

    await page.getByPlaceholder('Senha').fill(senhaPadrao);
    await page.getByRole('button', { name: 'Excluir conta' }).nth(1).click();
    await expect(page).toHaveURL('/');
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('apagar@e2e.test');
    await page.getByLabel('Senha', { exact: true }).fill(senhaPadrao);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page.getByText(/credenciais/i)).toBeVisible();
  });

  test('verificação de e-mail pelos fluxos existentes e confirmação de senha', async ({ page }) => {
    artisan(['e2e:preparar-usuario', '--email=verif@e2e.test', `--senha=${senhaPadrao}`, '--nome=Verif', '--nao-verificado']);
    await entrar(page, 'verif@e2e.test');
    await page.goto('/verify-email');
    await expect(page.getByText('Obrigado por se cadastrar')).toBeVisible();
    await page.getByRole('button', { name: 'Reenviar e-mail de verificação' }).click();

    await page.goto('/dashboard');
    await expect(page.getByText('Painel').first()).toBeVisible();

    await page.goto('/confirm-password');
    await expect(page.getByText('Área protegida')).toBeVisible();
    await page.getByLabel('Senha').fill('errada-errada');
    await page.getByRole('button', { name: 'Confirmar' }).click();
    await expect(page.locator('#password')).toBeVisible();

    await page.getByLabel('Senha').fill(senhaPadrao);
    await page.getByRole('button', { name: 'Confirmar' }).click();
    await expect(page).not.toHaveURL(/confirm-password/);
  });
});
