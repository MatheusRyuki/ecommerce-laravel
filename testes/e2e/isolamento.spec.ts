import { test, expect } from '@playwright/test';
import {
  confirmarIsolamentoHttp,
  entrar,
  reiniciar,
  senhaPadrao,
  tokenE2e,
} from './suporte/aplicacao';

test.describe('Isolamento do ambiente E2E', () => {
  test.beforeEach(() => {
    reiniciar();
  });

  test('HTTP prova banco, disco e marcador E2E; auxiliar some fora deste env', async ({ page, request }) => {
    await confirmarIsolamentoHttp(page);

    const semToken = await request.get('/_e2e/diagnostico');
    expect(semToken.status()).toBe(403);

    const appNormal = await request.get('http://127.0.0.1:8002/_e2e/diagnostico', {
      headers: { 'X-Token-E2e': tokenE2e() },
    });
    expect(appNormal.status()).toBe(404);
  });
});
