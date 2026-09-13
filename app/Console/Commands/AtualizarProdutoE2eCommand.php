<?php

namespace App\Console\Commands;

use App\Models\Produto;
use App\Support\IsolamentoE2e;
use Illuminate\Console\Command;

class AtualizarProdutoE2eCommand extends Command
{
    protected $signature = 'e2e:atualizar-produto
        {--sku= : SKU do produto}
        {--quantidade= : Novo estoque}
        {--preco= : Novo preço}
        {--nome= : Novo nome}
        {--cores= : Cores separadas por vírgula}
        {--excluir : Remove o produto}';

    protected $description = 'Altera um produto somente no banco E2E.';

    public function handle(): int
    {
        IsolamentoE2e::garantir();

        $sku = (string) $this->option('sku');
        $produto = Produto::query()->where('sku', $sku)->first();

        if ($produto === null) {
            $this->error('Produto não encontrado.');

            return self::FAILURE;
        }

        if ($this->option('excluir')) {
            $produto->delete();
            $this->info('excluido');

            return self::SUCCESS;
        }

        $dados = [];

        if ($this->option('quantidade') !== null) {
            $dados['quantidade'] = (int) $this->option('quantidade');
        }

        if ($this->option('preco') !== null) {
            $dados['preco'] = (string) $this->option('preco');
        }

        if ($this->option('nome') !== null) {
            $dados['nome'] = (string) $this->option('nome');
        }

        if ($this->option('cores') !== null) {
            $dados['cores'] = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('cores')))));
        }

        $produto->update($dados);
        $this->info('atualizado');

        return self::SUCCESS;
    }
}
