import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  testDir: './testes/e2e',
  fullyParallel: false,
  workers: 1,
  timeout: 90_000,
  expect: { timeout: 10_000 },
  forbidOnly: !!process.env.CI,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'storage/e2e/relatorio-playwright' }]],
  use: {
    baseURL: 'http://127.0.0.1:8003',
    extraHTTPHeaders: {},
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    locale: 'pt-BR',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
  ],
  webServer: {
    command: './vendor/bin/sail artisan serve --host=0.0.0.0 --port=8003 --env=e2e',
    url: 'http://127.0.0.1:8003/up',
    reuseExistingServer: true,
    timeout: 120_000,
  },
});
