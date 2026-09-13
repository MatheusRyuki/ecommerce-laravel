<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExcluirProduto
{
    public function __construct(private Filesystem $disco) {}

    /**
     * @return list<string> Caminhos que permaneceram no disco após a exclusão no banco.
     */
    public function excluir(Produto $produto): array
    {
        $produto->loadMissing('imagens');

        $idProduto = $produto->id;
        $caminhos = $produto->imagens
            ->pluck('path')
            ->filter(fn (mixed $caminho): bool => is_string($caminho) && $caminho !== '')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($produto): void {
            $produto->delete();
        });

        $pendencias = [];

        foreach ($caminhos as $caminho) {
            try {
                if (! $this->disco->exists($caminho)) {
                    continue;
                }

                if ($this->disco->delete($caminho) === false) {
                    $pendencias[] = $caminho;
                    $this->registrarLimpezaPendente($idProduto, $caminho);
                }
            } catch (Throwable $excecao) {
                $pendencias[] = $caminho;
                $this->registrarLimpezaPendente($idProduto, $caminho, $excecao->getMessage());
            }
        }

        return $pendencias;
    }

    private function registrarLimpezaPendente(int $idProduto, string $caminho, ?string $excecao = null): void
    {
        Log::warning('Limpeza pendente de imagem após exclusão do produto.', array_filter([
            'disk' => 'public',
            'product_id' => $idProduto,
            'path' => $caminho,
            'exception' => $excecao,
        ]));
    }
}
