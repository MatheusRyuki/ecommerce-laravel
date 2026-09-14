# e-Commerce

Projeto de estudo em Laravel com catálogo de produtos, carrinho, checkout interno e painel administrativo. A loja usa o template Freeit convertido para Blade, com Bootstrap. A autenticação e o painel usam Breeze e Tailwind.

O checkout registra pedidos, mas não processa pagamentos. Os pedidos ficam com o status **aguardando pagamento**.

## Funcionalidades

- **Catálogo:** busca por nome ou SKU, filtros por cor, disponibilidade e categoria, paginação e detalhes com galeria de imagens.
- **Carrinho:** sessão para visitantes e persistência por conta após o login, com atualização de quantidades e cálculo de totais.
- **Conta:** verificação de e-mail, favoritos, endereços de entrega com um endereço padrão e consulta de pedidos.
- **Checkout:** aplicação de cupom, frete por faixa de CEP e registro do pedido com os valores e o endereço utilizados na compra.
- **Produtos no painel:** cadastro, edição, exclusão, busca, publicação e duplicação com novo SKU. As cópias são criadas como ocultas.
- **Estoque:** entradas, saídas e ajustes com histórico. A edição comum do produto não altera sua quantidade.
- **Gestão administrativa:** categorias, cupons, faixas de frete, pedidos e usuários, com promoção e remoção de privilégios de administrador e proteção do último administrador.

A vitrine mostra 12 produtos por página, dos mais recentes para os mais antigos. Produtos sem estoque continuam visíveis como indisponíveis. Produtos ocultos não aparecem no catálogo e seus detalhes públicos retornam 404.

Ao entrar ou criar uma conta, os itens do carrinho visitante são incorporados ao carrinho salvo. As quantidades da conta têm prioridade; os itens do visitante são acrescentados até o limite de estoque.

Favoritos, endereços, aplicação de cupons e checkout exigem e-mail verificado, assim como o painel. O perfil continua acessível sem verificação para permitir a correção do endereço de e-mail.

Os pedidos preservam os valores e o endereço registrados na confirmação. Uma chave vinculada ao usuário identifica cada confirmação para evitar a criação de pedidos duplicados em reenvios.

## Capturas de tela

![Vitrine em desktop](docs/screenshots/vitrine-desktop.png)

![Detalhes da Bolsa Galeria Demo](docs/screenshots/detalhes-galeria.png)

![Administração de produtos](docs/screenshots/admin-produtos.png)

![Carrinho no celular](docs/screenshots/carrinho-celular.png)

## Tecnologias

| Componente | Versão ou uso |
| --- | --- |
| Laravel | 13.31 |
| Laravel Breeze | 2.4.2, com Blade e PHPUnit |
| PHP | 8.5 no container Sail |
| MySQL | 8.4 |
| BCMath | Cálculos monetários; extensão obrigatória no PHP da aplicação |
| Bootstrap | Interface da loja |
| Tailwind CSS | Autenticação e painel administrativo |
| Vite | Build dos assets |
| Playwright | Testes de ponta a ponta |

## Ambiente local

Os comandos abaixo consideram o terminal WSL no Windows, com a integração do Docker Desktop habilitada. PHP e Composer rodam em containers. Para executar o Playwright pelo terminal, é necessário ter Node.js e npm no WSL.

| Serviço | Porta no host |
| --- | --- |
| Aplicação | 8002 |
| Vite | 5174 |
| MySQL | 3307 |

O projeto Compose se chama `ecommerce` e usa os serviços `laravel.test` e `mysql`.

## Instalação

Substitua `URL_DO_REPOSITORIO` pelo endereço deste repositório:

```bash
git clone URL_DO_REPOSITORIO e-Commerce
cd e-Commerce
cp .env.example .env
```

O `.env.example` inclui `APP_URL=http://localhost:8002` e a configuração local do MySQL. A senha padrão `password` é destinada ao ambiente de desenvolvimento. O arquivo `.env` fica fora do versionamento.

Instale as dependências PHP antes de iniciar o Sail, pois o Compose usa arquivos de `vendor/laravel/sail`:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/app" \
  -w /app \
  composer:latest \
  composer install --ignore-platform-req=ext-bcmath
```

A imagem de Composer usada nessa etapa não inclui BCMath. A opção `--ignore-platform-req=ext-bcmath` se aplica somente a essa instalação inicial, a partir do `composer.lock`. O PHP do Sail deve atender a todos os requisitos da aplicação.

Inicie o ambiente, confira esses requisitos e prepare a aplicação:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer check-platform-reqs
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Acesse a loja em [http://localhost:8002](http://localhost:8002).

O comando `storage:link` permite servir as imagens pelo disco `public`. Os arquivos de produtos usam a pasta `products/`; o banco guarda seus caminhos relativos na coluna `caminho` da tabela `imagens_produto`.

As migrations incluem a base consolidada e as alterações posteriores de catálogo, contas, estoque e pedidos. Use `migrate` para aplicar as pendentes. Alterações de esquema devem ser feitas em migrations novas, mantendo as consolidadas. Não use `migrate:fresh` em um banco com dados que precisam ser mantidos.

## Administrador local

Configure `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env` e execute:

```bash
./vendor/bin/sail artisan db:seed --class=AdministradorSeeder
```

O `DatabaseSeeder` não cria o administrador. Quando nome e e-mail não são informados, os padrões são `Administrador` e `admin@example.test`.

Na primeira criação, se `ADMIN_PASSWORD` estiver vazia, o seeder gera uma senha e a grava no `.env` local. Consulte esse arquivo para entrar na conta. Mantenha a senha vazia no `.env.example`.

Se o e-mail já pertencer a um usuário comum, o seeder encerra sem modificar a conta.

### Verificação de e-mail

Administradores novos também precisam verificar o e-mail para acessar o painel. Após entrar na conta, gere o link de verificação local:

```bash
./vendor/bin/sail artisan verificacao:url admin@example.test
```

Se você configurou outro `ADMIN_EMAIL`, use esse endereço no comando. Abra o link gerado no mesmo navegador em que a conta está autenticada. O procedimento não altera a senha. O reenvio pode ser solicitado na tela `/verify-email`.

## Dados de demonstração

Para cadastrar um produto com três imagens:

```bash
./vendor/bin/sail artisan db:seed --class=ProdutoGaleriaDemoSeeder
```

O seeder cria a **Bolsa Galeria Demo**, com SKU `DEMO-GALLERY-01`, preço de R$ 89,90, estoque de 20 unidades e cores Azul e Verde. Se o SKU já existir, o registro permanece como está.

As imagens de origem estão em `public/frontend/images/product_slide_show_{1,2,3}.jpg`. Esse é o único produto recriado pelo seeder; os demais cadastros locais não fazem parte dos dados de demonstração distribuídos com o projeto.

## Páginas

Os caminhos abaixo usam a base `http://localhost:8002`.

| Página | Caminho |
| --- | --- |
| Vitrine | `/` |
| Categoria | `/categorias/{slug}` |
| Detalhes do produto | `/produtos/{id}` |
| Carrinho | `/carrinho` |
| Checkout | `/checkout` |
| Login | `/login` |
| Perfil | `/perfil` |
| Administração | `/admin/painel` |
| Produtos no painel | `/admin/produtos` |

Substitua `{id}` e `{slug}` por valores de registros existentes. `/admin/painel` encaminha para a listagem administrativa de produtos.

As URLs antigas `/product-details` e `/products/{id}` continuam com redirecionamento para a vitrine e o detalhe em português, respectivamente. O redirecionamento do detalhe depende da existência do produto.

## Testes

### PHPUnit

A suíte padrão, em `tests/Feature` e `tests/Unit`, usa SQLite em memória e não utiliza o banco MySQL `ecommerce`:

```bash
./vendor/bin/sail artisan test
```

### E2E

Os testes Playwright usam a aplicação na porta HTTP **8003** e o banco separado `ecommerce_e2e`. A suíte inclui Chromium, Firefox, WebKit e emulação de celular.

```bash
./scripts/e2e/preparar-ambiente.sh
npm run teste:e2e
```

A preparação e o comando de testes compartilham o bloqueio `storage/e2e/execucao.lock`. Uma segunda execução é recusada antes de reiniciar o banco ou sobrescrever o relatório.

O relatório HTML fica em `storage/e2e/relatorio-playwright/index.html`. A configuração dos navegadores, o isolamento e os cenários estão descritos em [docs/e2e.md](docs/e2e.md).

### Verificações no MySQL

Depois de preparar o ambiente E2E, execute as verificações de estoque e cupom com:

```bash
./vendor/bin/sail artisan test --env=e2e --configuration=phpunit.concorrencia.xml
```

Esse comando recria o banco `ecommerce_e2e`. Execute-o com a suíte Playwright encerrada, pois ambos usam o mesmo banco. O banco de desenvolvimento `ecommerce` não é o alvo dessa configuração.

**Limite da cobertura atual:** as verificações MySQL são sequenciais. Elas não comprovam o comportamento de duas transações concorrentes executadas por processos distintos.

## Limitações

- Não há integração com gateway de pagamento nem cálculo de impostos.
- O frete usa valores fixos por faixa de CEP cadastrados no painel, sem cotação de transportadoras.
- A confirmação do checkout salva os dados comerciais do pedido e baixa o estoque. Isso não representa uma cobrança ou confirmação de pagamento.

## Instalações anteriores à consolidação das migrations

Uma instalação nova aplica a sequência atual normalmente. Bancos que executaram as migrations antigas, com tabelas em inglês e renomeações posteriores, precisam ter o esquema e o histórico da tabela `migrations` conferidos antes da atualização. A consolidação não adapta esse histórico automaticamente.

Faça backup antes de ajustar um banco antigo. O alinhamento do histórico exige conferir quais alterações já existem no esquema; não deve ser resolvido com uma exclusão indiscriminada dos registros de `migrations`.

O rollback do batch que contém a base consolidada pode remover as tabelas iniciais e seus dados. Ele não reconstrói a sequência antiga de renomeações. Por isso, não use comandos de reset ou rollback para tentar corrigir esse histórico.

Índices herdados podem manter os nomes em inglês. Isso, por si só, não altera a unicidade do SKU nem a chave estrangeira das imagens de produto.

## Template e créditos

O template de referência está em `original-template/`. Os assets utilizados pela loja ficam em `public/frontend/`.

- **Freeit:** template e crédito no rodapé.
- **Mahamudul Hassan Sazal:** crédito em `css/spacing.css`.
- **Font Awesome Free:** CSS e webfonts utilizados na loja. O kit Pro `js/Font-Awesome.js` não é carregado.

A licença indicada em `composer.json` é MIT. O template e as dependências mantêm seus próprios créditos e termos de uso.
