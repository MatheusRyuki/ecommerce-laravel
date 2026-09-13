<?php

namespace App\Console\Commands;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Support\CoresProduto;
use App\Support\DiscoArquivosProduto;
use App\Support\IsolamentoE2e;
use Database\Seeders\AdministradorSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReiniciarAmbienteE2eCommand extends Command
{
    protected $signature = 'e2e:reiniciar {--com-catalogo=0 : Quantidade extra de produtos para paginação}';

    protected $description = 'Recria somente o banco e os diretórios do ambiente E2E.';

    public function handle(): int
    {
        IsolamentoE2e::garantir();

        $this->prepararDiretorios();
        $this->gravarMarcador();
        $this->limparCorreio();

        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->output->write(Artisan::output());

        $this->call('db:seed', [
            '--class' => AdministradorSeeder::class,
            '--force' => true,
        ]);

        $extras = (int) $this->option('com-catalogo');

        if ($extras > 0) {
            $this->criarCatalogo($extras);
        }

        $this->info('Ambiente E2E recriado no banco '.IsolamentoE2e::BANCO.'.');

        return self::SUCCESS;
    }

    private function prepararDiretorios(): void
    {
        $caminhos = [
            IsolamentoE2e::raizAbsoluta(),
            storage_path('e2e/app/publico/products'),
            storage_path('e2e/framework/sessions'),
            storage_path('e2e/framework/cache/data'),
            storage_path('e2e/framework/views'),
            storage_path('e2e/correio'),
            storage_path('e2e/logs'),
        ];

        foreach ($caminhos as $caminho) {
            if (str_starts_with($caminho, IsolamentoE2e::raizAbsoluta()) === false) {
                throw new \RuntimeException('Caminho fora do ambiente E2E: '.$caminho);
            }

            File::ensureDirectoryExists($caminho);
        }

        DiscoArquivosProduto::disco()->deleteDirectory('products');
        File::ensureDirectoryExists(DiscoArquivosProduto::disco()->path('products'));
    }

    private function gravarMarcador(): void
    {
        File::put(IsolamentoE2e::caminhoMarcador(), (string) Str::uuid());
    }

    private function limparCorreio(): void
    {
        File::put(IsolamentoE2e::caminhoCorreio(), '');
    }

    private function criarCatalogo(int $quantidade): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=', true);

        for ($i = 1; $i <= $quantidade; $i++) {
            $produto = Produto::query()->create([
                'nome' => 'Produto catálogo '.$i,
                'preco' => '10.00',
                'cores' => [CoresProduto::todas()[0]],
                'descricao_curta' => 'Item gerado para paginação E2E.',
                'quantidade' => 5,
                'sku' => sprintf('E2E-CAT-%04d', $i),
                'descricao' => '<p>Descrição do produto '.$i.'.</p>',
            ]);

            $caminho = 'products/e2e-cat-'.$produto->id.'.png';
            DiscoArquivosProduto::disco()->put($caminho, $png ?: 'png');

            ImagemProduto::query()->create([
                'produto_id' => $produto->id,
                'caminho' => $caminho,
                'posicao' => 0,
            ]);
        }
    }
}
