# e-Commerce

Projeto de estudo em Laravel: loja com vitrine, detalhes de produto, carrinho (sessão para visitante e persistente após o login), checkout sem pagamento e painel administrativo. O visual da loja usa Bootstrap (template em Blade); o admin usa Tailwind/Breeze.

Não processa pagamento. Pedidos ficam em *aguardando pagamento*.

## Funcionalidades

- Vitrine pública (`GET /`): produtos **publicados**, 12 por página, mais recentes primeiro. Filtros por busca (`q`), cor, disponibilidade e categoria (`/categorias/{slug}`). Estoque zero permanece visível como indisponível; produto oculto não aparece e o detalhe responde 404.
- Detalhes (`GET /produtos/{produto}`). A URL antiga `/product-details` redireciona para a vitrine; `/products/{id}` redireciona para `/produtos/{id}` quando o registro existe.
- Carrinho: visitante na sessão Laravel; após login/cadastro as linhas da sessão **mesclam** com o carrinho da conta (conta primeiro, depois visitante até o estoque). Cupom, endereço e revisão exigem e-mail verificado.
- Checkout (`/checkout`): confirma pedido com snapshot de valores, baixa estoque com bloqueio de linha, cupom e frete por faixa de CEP. Idempotente por `usuario` + chave. **Nenhum pagamento é cobrado.**
- Conta: verificação de e-mail (`MustVerifyEmail`), favoritos, endereços (um padrão), pedidos.
- Painel (`/admin/...`, `verified` + `acessar-admin`): produtos (busca, publicação, duplicar cópia oculta), estoque com histórico, categorias, cupons, faixas de frete, pedidos, usuários (promover/rebaixar; último admin não pode ser excluído).
- Autenticação Breeze. Perfil (`/perfil`) permanece acessível sem e-mail verificado para corrigir o endereço.

## Tecnologias e requisitos

Versões deste repositório (podem diferir do material do curso):

- Laravel 13.31, Breeze 2.4.2 (Blade + PHPUnit), PHP 8.5 (imagem Sail), MySQL 8.4
- `ext-bcmath` obrigatória (`composer.json`); totais do carrinho usam BCMath
- Node.js para Vite (`npm install` / `npm run build`)
- Docker Desktop e WSL. Não é necessário PHP nem Composer no host.

Portas locais (evitam conflito com outro app em 8001): HTTP **8002**, Vite **5174**, MySQL no host **3307**. Projeto Compose: `ecommerce`. Serviços: `laravel.test` e `mysql` apenas.

## Instalação a partir de um clone

O Sail (`./vendor/bin/sail` e `compose.yaml`) depende de `vendor/laravel/sail`. Instale as dependências PHP **antes** de subir os containers.

```bash
git clone <url-do-repositorio>
cd e-Commerce
cp .env.example .env
```

No `.env`, defina `APP_URL=http://localhost:8002` (já vem no exemplo). A senha do MySQL do Compose é a de `DB_PASSWORD` do exemplo (`password`); isso é o padrão do Sail, não uma credencial de produção.

Instale o Composer com a imagem oficial `composer:latest` (sem PHP no host). Essa imagem só serve para gerar `vendor/` (incluindo `vendor/laravel/sail`). A flag `--ignore-platform-req=ext-bcmath` vale **somente** nesse passo: essa imagem não traz `ext-bcmath`, exigida pelo `composer.json`. Não use ignore de plataforma no container Sail que executa a aplicação.

Não use `laravelsail/php85-composer`: essa tag não é publicada no Docker Hub. O runtime do app continua sendo o PHP **8.5** definido em `compose.yaml`, onde `ext-bcmath` está presente.

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/app" \
  -w /app \
  composer:latest \
  composer install --ignore-platform-req=ext-bcmath
```

O `composer.lock` declara plataforma `php: ^8.3` e deve ser respeitado na instalação.

Suba o ambiente, gere a chave, rode as migrations, o link de storage e o build dos assets. Em seguida, confira os requisitos da plataforma **no Sail**:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail composer check-platform-reqs
```

`storage:link` é necessário para as imagens de produto no disco `public`. A coluna `caminho` em `imagens_produto` guarda o caminho relativo no disco; uploads novos e já existentes usam o prefixo `products/` nesse disco (compatibilidade com arquivos gravados antes da consolidação do schema).

Uma instalação nova aplica as migrations consolidadas de usuários/cache/filas/produtos/imagens **e** as incrementais de catálogo (categorias, publicação, carrinho persistente, favoritos, endereços, estoque, cupons, frete, pedidos). Não altere os cinco arquivos consolidados; novas tabelas entram só em migrations posteriores.

Não use `migrate:fresh` se já houver dados locais que devam ser preservados.

### Instalações que já rodaram as migrations antigas

A consolidação **não** é uma atualização automática de schema. Bancos que já executaram a sequência anterior (criação em inglês + `add_is_admin` + dois renomes) já estão no schema final; o que muda é só o **histórico** na tabela `migrations`.

No banco local `ecommerce` essa adoção **já foi feita**: os cinco registros intermediários foram substituídos pelos dois arquivos consolidados (`criar_tabela_produtos` e `criar_tabela_imagens_produto`), no **batch 1**, junto com `users`, `cache` e `jobs`. `migrate:status` deve listar as cinco migrations atuais como executadas e `migrate` não deve ter nada a aplicar. Não rode `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback` nem `db:wipe` se precisar preservar os dados.

Efeito no rollback: os passos intermediários (`is_admin`, tabelas em inglês, `path`) **não existem mais** no histórico. Um `migrate:rollback` do batch 1 tentaria desfazer a **base inteira** (usuários, cache, filas, produtos e imagens) de uma vez — o `down()` das migrations consolidadas não reconstrói o caminho antigo. Trate rollback como incompatível com este banco de desenvolvimento já preenchido.

Outros bancos ainda no histórico antigo precisam do recorte **manual** (backup da tabela `migrations`, conferência do schema, substituição só daquelas cinco linhas). Alternativa: dump dos dados, banco vazio, `migrate` da sequência nova e importação.

Índices herdados da criação em inglês podem continuar com os nomes `products_sku_unique` e `product_images_product_id_foreign`; instalações novas usam `produtos_sku_unique` e `imagens_produto_produto_id_foreign`. Isso não muda unicidade nem o FK em `produto_id` (`ON DELETE CASCADE`).

## URLs

Loja (após `sail up`):

- Vitrine: [http://localhost:8002](http://localhost:8002)
- Detalhes: [http://localhost:8002/produtos/{id}](http://localhost:8002/produtos/1)
- Carrinho: [http://localhost:8002/carrinho](http://localhost:8002/carrinho)

Administração:

- Login: [http://localhost:8002/login](http://localhost:8002/login)
- Painel: [http://localhost:8002/admin/painel](http://localhost:8002/admin/painel)
- Produtos: [http://localhost:8002/admin/produtos](http://localhost:8002/admin/produtos)

Administradores **novos** (incluindo o seeder local) nascem **sem** e-mail verificado. O painel exige verificação. Com a conta autenticada, gere o link local (não altera a senha):

```bash
./vendor/bin/sail artisan verificacao:url admin@example.test
```

Abra a URL no mesmo navegador. Reenvio: `/verify-email`. Não marque `email_verified_at` à mão no banco de desenvolvimento.

## Administrador local

O `DatabaseSeeder` **não** cria o administrador. Use:

```bash
./vendor/bin/sail artisan db:seed --class=AdministradorSeeder
```

Variáveis no `.env` / `.env.example`: `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`.

- Deixe a senha **vazia** no `.env.example` e não a publique.
- Na primeira execução, se `ADMIN_PASSWORD` estiver vazia, o seeder gera uma senha aleatória e grava **somente** no `.env` local. Consulte esse arquivo depois do seed; não versionar `.env`.
- Se o e-mail já existir como usuário comum, o seeder aborta sem alterar o registro.
- Padrão quando nome e e-mail estão vazios: nome `Administrador`, e-mail `admin@example.test`.

## Demonstração da galeria

```bash
./vendor/bin/sail artisan db:seed --class=ProdutoGaleriaDemoSeeder
```

Cria **Bolsa Galeria Demo**, SKU `DEMO-GALLERY-01`, R$ 89,90, estoque 20, cores Azul e Verde, com três fotos. As origens versionáveis estão em `public/frontend/images/product_slide_show_{1,2,3}.jpg`. O seeder é idempotente: se o SKU já existir, não altera o registro.

Outros produtos locais (por exemplo a Bolsa Demo Editada, SKU `DEMO-0001`) **não** são criados por esse seeder e devem ser preservados no banco de desenvolvimento. Após um clone limpo, só a galeria demo reaparece com o comando acima.

## Limitações atuais

- Pagamento, impostos e gateway ficam de fora: o pedido é gravado como aguardando pagamento.
- Frete é faixa de CEP cadastrada no admin (não há cotação de transportadora).
- Edição de produto **não** altera quantidade; use a tela de estoque.

## Testes

Os testes PHPUnit em `tests/Feature` e `tests/Unit` usam SQLite em memória (`phpunit.xml`) e **não** usam o MySQL `ecommerce`.

```bash
./vendor/bin/sail artisan test
```

Provas de estoque/cupom no **MySQL isolado** `ecommerce_e2e` (recria esse banco; não toca `ecommerce`):

```bash
./vendor/bin/sail artisan test --env=e2e --configuration=phpunit.concorrencia.xml
```

Há uma suíte E2E com Playwright (navegador, Laravel e MySQL `ecommerce_e2e` na porta **8003**). Isolamento, matriz de cenários e comandos: [docs/e2e.md](docs/e2e.md).

```bash
./scripts/e2e/preparar-ambiente.sh
npm run teste:e2e
```

`teste:e2e` e a preparação compartilham `storage/e2e/execucao.lock` (`flock`). Uma segunda execução é recusada com mensagem clara e não reinicia o banco nem sobrescreve o relatório.

O build de assets da loja (quando necessário):

```bash
./vendor/bin/sail npm run build
```

## Matriz das 14 entregas

| # | Entrega | Implementação | Validação |
| --- | --- | --- | --- |
| 1 | Busca na vitrine e no admin | Query `q` + paginação com query string | PHPUnit catálogo/listagem; E2E vitrine |
| 2 | Publicação | `publicado`; `scopePublicados` | PHPUnit oculto 404 |
| 3 | Duplicar produto | Cópia oculta, imagens novas, estoque via movimentação | PHPUnit `AdminProdutoSalvarTest` |
| 4 | Verificação de e-mail | `MustVerifyEmail`; `verificacao:url` local | PHPUnit auth; E2E cadastro/perfil |
| 5 | Carrinho persistente e mescla | `itens_carrinho` + `MescladorCarrinho` | PHPUnit; E2E jornada |
| 6 | Categorias | CRUD admin + vitrine por slug | PHPUnit; E2E jornada |
| 7 | Favoritos | Conta verificada | PHPUnit; E2E jornada |
| 8 | Endereços | Um padrão; exclusão promove o mais antigo | PHPUnit; E2E jornada |
| 9 | Estoque | Lock + histórico; edição de produto não muda qty | PHPUnit produto/estoque; concorrência MySQL |
| 10 | Checkout e pedidos | Snapshot, idempotência, sem pagamento | PHPUnit; E2E jornada |
| 11 | Cupons | Fixo/percentual, uso único | PHPUnit; E2E jornada; concorrência MySQL |
| 12 | Frete por CEP | Faixas no admin | PHPUnit CEP sem cobertura; E2E jornada |
| 13 | Usuários admin | Promover/rebaixar; último admin protegido | PHPUnit `AdminUsuarioTest` / perfil |
| 14 | Filtros de catálogo (cor/disponível) | `ConsultaCatalogo` | PHPUnit `PublicacaoCatalogoFavoritoTest` |

Pendências conhecidas: disputa **simultânea** de duas transações no mesmo instante (a suíte MySQL cobre a ordem sequencial com `lockForUpdate`; não prova dois processos em paralelo).

## Capturas

![Vitrine em desktop](docs/screenshots/vitrine-desktop.png)

![Detalhes da Bolsa Galeria Demo](docs/screenshots/detalhes-galeria.png)

![Administração de produtos](docs/screenshots/admin-produtos.png)

![Carrinho no celular](docs/screenshots/carrinho-celular.png)

## Template original e créditos

`original-template/` permanece como referência (não altere esses arquivos). Assets públicos da loja estão em `public/frontend/`. Créditos: Freeit (rodapé) e Mahamudul Hassan Sazal (`css/spacing.css`). Font Awesome: CSS/webfonts Free em `public/frontend`; o kit Pro `js/Font-Awesome.js` não é carregado.

Licença do skeleton Laravel: MIT (`composer.json`).
