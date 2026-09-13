# Testes E2E (Playwright)

A suíte em `testes/e2e` exercita o e-commerce **pelo navegador** contra Laravel e MySQL reais do ambiente **E2E**, isolado do app em `http://localhost:8002` e do banco `ecommerce`.

Os testes PHPUnit em `tests/` continuam no SQLite em memória. Não usam `ecommerce` nem `ecommerce_e2e`.

## Fora de escopo (não implementado no produto)

Checkout, pagamento, frete, impostos, descontos e persistência do carrinho por conta. `Usuario` **não** implementa `MustVerifyEmail`: o painel Breeze abre sem exigir verificação; a tela `/verify-email` e o reenvio existem e são o fluxo atual.

A loja pública tem Início, carrinho, Entrar/Criar conta (visitante) ou Conta (autenticado). Não há links de navegação só para `#`. Após o login, administradores vão para `/admin/produtos` e demais usuários para `/dashboard`. `/admin/painel` encaminha à listagem.

## Isolamento

| Recurso | Ambiente normal | E2E |
| --- | --- | --- |
| HTTP | porta 8002 | porta **8003** (`artisan serve --env=e2e`) |
| Banco | `ecommerce` (usuário `sail`) | `ecommerce_e2e` (usuário `ecommerce_e2e`, só esse banco) |
| Uploads | disco `public` (`storage/app/public` + `public/storage`) | `storage/e2e/app/publico` em `/armazenamento-e2e` |
| Sessão / cache / views | padrão Laravel | `storage/e2e/framework/*`, cookie `e2e_sessao` |
| Correio | log da aplicação | `storage/e2e/correio/mensagens.log` (`MAIL_MAILER=log`) |
| Rotas `/_e2e/*` | 404 | só com `APP_ENV=e2e` |

`e2e:reiniciar` chama `migrate:fresh` **somente** depois de `IsolamentoE2e::garantir()`. Não use esse comando com `--env=local`.

A prova HTTP não é só status 200: `GET /_e2e/diagnostico` (cabeçalho `X-Token-E2e`) devolve banco, disco, marcador em `storage/e2e/marcador.txt` e caminho do correio.

Projetos Playwright (`playwright.config.ts`): `chromium`, `firefox` e `webkit` (desktop 1280×800, `grepInvert: /@somente-mobile/`) e `mobile` (emulação **Pixel 5**, `hasTouch` e `isMobile`, `grep: /@principal|@somente-mobile/`). O projeto `mobile` **não** é aparelho físico. `retries: 0`, `workers: 1` global, `fullyParallel: false` — a suíte compartilha `ecommerce_e2e` e chama `e2e:reiniciar`; não rode processos concorrentes que reiniciem o mesmo banco.

## Comandos reais

Preparar (uma vez; não toca `ecommerce`):

```bash
chmod +x scripts/e2e/preparar-ambiente.sh
./scripts/e2e/preparar-ambiente.sh
./vendor/bin/sail npm install
npx playwright install chromium firefox webkit
```

Subir a instância HTTP E2E (se 8003 estiver livre):

```bash
./vendor/bin/sail artisan serve --host=0.0.0.0 --port=8003 --env=e2e
```

Executar **tudo** (no host; `webServer` reutiliza 8003 se já estiver no ar):

```bash
npm run teste:e2e
```

Um arquivo / um cenário / um projeto:

```bash
npx playwright test testes/e2e/carrinho.spec.ts --project=chromium
npx playwright test --grep "lembrar de mim" --project=firefox
npx playwright test --project=mobile
```

Navegador visível / UI do Playwright:

```bash
npx playwright test --headed --project=chromium
npx playwright test --ui
```

Relatório HTML da última execução:

```bash
npx playwright show-report storage/e2e/relatorio-playwright
```

Encerrar **somente** o `artisan serve` da porta 8003 (não derrube o Compose, a 8002, o banco `ecommerce` nem o app administrador/produtos locais). No terminal desse serve: `Ctrl+C`. Se o processo ficou no container Sail:

```bash
ss -tlnp | grep 8003
docker exec ecommerce-laravel.test-1 sh -c "ss -tlnp | grep 8003 || netstat -tlnp | grep 8003"
docker exec ecommerce-laravel.test-1 pkill -f 'artisan serve.*8003' || true
ss -tlnp | grep 8003 || echo '8003 livre'
```

Administrador E2E: `admin@e2e.test` / `ADMIN_PASSWORD` em `.env.e2e` (padrão do exemplo: `SenhaE2e!234`). Não usa DEMO-0001 / DEMO-GALLERY-01.

## Roteiro manual (navegador em http://127.0.0.1:8003)

Pré-condições: `./scripts/e2e/preparar-ambiente.sh` já rodou; `artisan serve --env=e2e` na 8003; **não** use a 8002 para este roteiro.

| # | Dados | Passos | Resultado esperado |
| --- | --- | --- | --- |
| 1 | — | Abrir `/`. | “Ainda não há produtos na loja.” |
| 2 | `admin@e2e.test` + senha do `.env.e2e` | `/login` → Entrar → Produtos → Cadastrar. JPEG/PNG, nome, preço `15,00`, cor Verde, SKU `INT-MANUAL`, estoque 6, descrição no Quill. | Lista com o SKU; “Produto cadastrado.” |
| 3 | outra janela anônima | Abrir `/`, clicar no cartão, adicionar, no carrinho mudar quantidade para 2 e Atualizar. | Total **R$ 30,00**. “Finalizar compra” desabilitado. |
| 4 | admin | Editar o produto: preço `18,50`, salvar. Recarregar a vitrine e o carrinho do visitante. | Nome/preço novos; subtotal **R$ 37,00**. |
| 5 | visitante | `/register` senha curta e confirmação divergente; depois cadastro válido. | Erros de senha; depois `/dashboard`. |
| 6 | conta de teste | `/forgot-password` → ver `storage/e2e/correio/mensagens.log` → abrir o link → redefinir. Abrir o mesmo link de novo. | Senha nova funciona; segundo uso rejeita o token. |
| 7 | `/login` com “Lembrar de mim” | Nas ferramentas de desenvolvedor, apagar só o cookie `e2e_sessao`, manter `remember_web_*`, abrir `/perfil`. | Continua autenticado. |
| 8 | `/perfil` | Excluir conta: Cancelar; senha errada; senha certa (só conta de teste). | Modal fecha; erro de senha; depois `/` e login falha. |
| 9 | usuário comum criado no cadastro | URL `/admin/produtos`. | 403. Menu Administração/Produtos ausente. |
| 10 | duas abas da **mesma** sessão no carrinho | Alterar quantidade nas duas e Atualizar. | Quantidade final ≤ estoque; sem total enganoso se a linha ficar inválida. |

## Matriz de cobertura (automação)

Há **33 cenários únicos** (`test()` em `testes/e2e/*.spec.ts`). Sem retry. Tags: `@principal` (fluxos de autenticação, CRUD admin, galeria, carrinho principal, integrado e foco do modal) e `@somente-mobile` (menu da loja em emulação Pixel 5).

| Arquivo | Cenários únicos | Desktop (3 navegadores) | Emulação mobile |
| --- | --- | --- | --- |
| `isolamento.spec.ts` | 1 (diagnóstico HTTP vs 8002) | 3 | — |
| `autenticacao.spec.ts` | 3 (cadastro, login/logout, lembrar) | 9 | 2 (`@principal`) |
| `perfil.spec.ts` | 4 (perfil, senha, exclusão, verify-email + confirm-password) | 12 | — |
| `recuperacao-senha.spec.ts` | 2 (link local + token inválido/reusado) | 6 | — |
| `permissoes.spec.ts` | 3 (visitante, comum 403, admin) | 9 | — |
| `admin-produtos.spec.ts` | 4 (CRUD, validações/Quill/imagens, paginação 15) | 12 | 2 (`@principal`) |
| `vitrine.spec.ts` | 3 (vazio/ordem, galeria/sanitização, paginação 12) | 9 | 1 (`@principal`) |
| `carrinho.spec.ts` | 8 (cores/totais, estoque, redução, exclusão, abas, autenticado, +/−, qty 0 e 1,5 nativa) | 24 | 2 (`@principal`) |
| `fluxo-integrado.spec.ts` | 1 (admin UI → visitante → carrinho → alteração admin) | 3 | 1 (`@principal`) |
| `navegacao.spec.ts` | 4 (teclado produto/carrinho, links `#` + teclado login, celular, modal Escape/Cancelar + foco) | 9 (sem `@somente-mobile`) | 2 (`@principal` + `@somente-mobile`) |

Execuções por `npm run teste:e2e`: **32 × Chromium + 32 × Firefox + 32 × WebKit + 10 × mobile = 106**. Quantidade 0/fracionária no servidor continua coberta pelo PHPUnit (`LojaCarrinhoMutacaoTest`).

Preparação: `e2e:reiniciar` no `beforeEach` (ou no próprio teste quando usa `--com-catalogo`). Não é um teste extra.

## Rollback

Não rode `migrate:rollback` / `fresh` no banco `ecommerce`. O E2E usa `migrate:fresh` só em `ecommerce_e2e`.
