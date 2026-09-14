# Testes E2E (Playwright)

A suíte em `testes/e2e` exercita o e-commerce **pelo navegador** contra Laravel e MySQL reais do ambiente **E2E**, isolado do app em `http://localhost:8002` e do banco `ecommerce`.

Os testes PHPUnit em `tests/Feature` e `tests/Unit` usam SQLite em memória. Não usam `ecommerce`. A suíte `phpunit.concorrencia.xml` usa o MySQL `ecommerce_e2e` (wipe só desse banco) com dois processos PHP por caso.


## Escopo do produto (E2E)

Checkout **sem pagamento**, frete por faixa de CEP, cupom, carrinho persistente após login, `MustVerifyEmail`. Cadastro cai em `/verify-email`. O admin recriado por `e2e:reiniciar` nasce verificado **somente** no banco E2E (não altera o admin de `ecommerce`).

A loja pública tem Início, categorias, Favoritos, Pedidos, carrinho, Entrar/Criar conta (visitante) ou Conta (autenticado). Após o login, administradores verificados vão para `/admin/produtos` e demais usuários para `/dashboard`. `/admin/painel` encaminha à listagem.

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

Projetos Playwright (`playwright.config.ts`): `chromium`, `firefox` e `webkit` (desktop 1280×800, `grepInvert: /@somente-mobile/`) e `mobile` (emulação **Pixel 5**, `hasTouch` e `isMobile`, `grep: /@principal|@somente-mobile/`). O projeto `mobile` **não** é aparelho físico. `retries: 0`, `workers: 1` global, `fullyParallel: false`. `npm run teste:e2e` e `./scripts/e2e/preparar-ambiente.sh` usam `flock` em `storage/e2e/execucao.lock`: uma segunda execução é recusada **antes** de `e2e:reiniciar` ou de sobrescrever o relatório HTML. A suíte de concorrência MySQL usa o mesmo cadeado. `workers: 1` não cobre dois `npm run teste:e2e` independentes. O `artisan serve` na 8003 **não** ocupa esse cadeado.

## Concorrência MySQL (`phpunit.concorrencia.xml`)

Comando (no host; o PHPUnit corre **dentro** do container Sail; cada caso dispara dois processos PHP):

```bash
./scripts/e2e/com-exclusividade.sh ./vendor/bin/sail exec laravel.test vendor/bin/phpunit -c phpunit.concorrencia.xml
```

Não use `artisan test --configuration=phpunit.concorrencia.xml` em paralelo com essa invocação: o `artisan test` já injeta uma configuração e o PHPUnit recusa `--configuration` duplicado.

Isolamento: `IsolamentoE2e::garantir()` no processo principal e em cada filho, com `DATABASE()`, `CURRENT_USER()` e `CONNECTION_ID()` distintos. Usuário `ecommerce_e2e` no banco `ecommerce_e2e`. `migrate:fresh` só nesse banco, **sem** transação do PHPUnit à volta dos dados (os filhos precisam enxergar o que o pai gravou). Os filhos não executam migration nem reset.

Sobreposição: o filho A, **só no teste**, após o `FOR UPDATE` da aplicação no recurso indicado em `segurar_apos`, grava `a-segurou.json` e espera `liberar.json` com a transação ainda aberta. No ponto da consulta crítica de B (`DB::beforeExecuting` do mesmo `FOR UPDATE`), o teste abre uma conexão/transação **descartável** (usuário `ecommerce_e2e`) e executa o mesmo SQL e parâmetros com `FOR UPDATE NOWAIT`. Só o erro MySQL **3572** (`ER_LOCK_NOWAIT`) é aceito como comprovação de conflito; o pai recusa sucesso ou qualquer outro código. Essa sondagem prova que o InnoDB já recusava o lock enquanto A retinha a transação; **não** é observação da fila de espera da consulta normal de B, que só segue depois. Marcadores como `b-atingiu-for-update.json`, sozinhos, não liberam A. Sem o 3572, `liberar.json` não é escrito. Há um controle negativo: a mesma sonda sobre uma linha livre não devolve 3572 e `exigirErro3572` recusa a prova. O usuário `ecommerce_e2e` não tem `PROCESS` nem `performance_schema.data_lock_waits`.

A concorrência é entre esses processos, não via `artisan serve` (um processo só). HTTP não é usado nesses casos.

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
| 3 | outra janela anônima | Abrir `/`, clicar no cartão, adicionar, no carrinho mudar quantidade para 2 e Atualizar. | Total **R$ 30,00**. “Revisar pedido” só após login verificado, endereço e cobertura de frete. |
| 4 | admin | Editar o produto: preço `18,50`, salvar. Recarregar a vitrine e o carrinho do visitante. | Nome/preço novos; subtotal **R$ 37,00**. |
| 5 | visitante | `/register` senha curta e confirmação divergente; depois cadastro válido. | Erros de senha; depois `/verify-email`. |
| 6 | conta de teste | `/forgot-password` → ver `storage/e2e/correio/mensagens.log` → abrir o link → redefinir. Abrir o mesmo link de novo. | Senha nova funciona; segundo uso rejeita o token. |
| 7 | `/login` com “Lembrar de mim” | Nas ferramentas de desenvolvedor, apagar só o cookie `e2e_sessao`, manter `remember_web_*`, abrir `/perfil`. | Continua autenticado. |
| 8 | `/perfil` | Excluir conta: Cancelar; senha errada; senha certa (só conta de teste). | Modal fecha; erro de senha; depois `/` e login falha. |
| 9 | usuário comum criado no cadastro | URL `/admin/produtos`. | 403. Menu Produtos ausente. |
| 10 | duas abas da **mesma** sessão no carrinho | Alterar quantidade nas duas e Atualizar. | Quantidade final ≤ estoque; sem total enganoso se a linha ficar inválida. |

## Matriz de cobertura (automação)

Há **41 cenários únicos** (`test()` em `testes/e2e/*.spec.ts`). Sem retry. Tags: `@principal` (fluxos de autenticação, CRUD admin, duplicar, estoque, usuários, galeria, carrinho principal, integrado, jornada de compra e foco do modal) e `@somente-mobile` (menu da loja em emulação Pixel 5).

| Arquivo | Cenários únicos | Desktop (3 navegadores) | Emulação mobile |
| --- | --- | --- | --- |
| `isolamento.spec.ts` | 1 | 3 | — |
| `autenticacao.spec.ts` | 4 | 12 | 2 (`@principal`) |
| `perfil.spec.ts` | 4 | 12 | — |
| `recuperacao-senha.spec.ts` | 2 | 6 | — |
| `permissoes.spec.ts` | 3 | 9 | — |
| `admin-produtos.spec.ts` | 5 | 15 | 2 (`@principal`) |
| `duplicar-produto.spec.ts` | 1 | 3 | 1 (`@principal`) |
| `estoque-admin.spec.ts` | 1 | 3 | 1 (`@principal`) |
| `usuarios-admin.spec.ts` | 1 | 3 | 1 (`@principal`) |
| `vitrine.spec.ts` | 3 | 9 | 1 (`@principal`) |
| `carrinho.spec.ts` | 9 | 27 | 3 (`@principal`) |
| `fluxo-integrado.spec.ts` | 1 | 3 | 1 (`@principal`) |
| `jornada-compra.spec.ts` | 1 | 3 | 1 (`@principal`) |
| `navegacao.spec.ts` | 5 | 12 (sem `@somente-mobile`) | 2 (`@principal` + `@somente-mobile`) |

Execuções por `npm run teste:e2e`: **40 × Chromium + 40 × Firefox + 40 × WebKit + 15 × mobile**. Quantidade 0/fracionária no servidor continua coberta pelo PHPUnit (`LojaCarrinhoMutacaoTest`).

Preparação: `e2e:reiniciar` no `beforeEach` (ou no próprio teste quando usa `--com-catalogo`). Não é um teste extra.

## Rollback

Não rode `migrate:rollback` / `fresh` no banco `ecommerce`. O E2E usa `migrate:fresh` só em `ecommerce_e2e`.
