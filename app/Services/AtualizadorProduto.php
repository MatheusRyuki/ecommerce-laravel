<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AtualizadorProduto
{
    public function __construct(private Filesystem $disco) {}

    /**
     * @param  array<string, mixed>  $atributos
     * @param  list<UploadedFile>  $novasImagens
     * @param  list<int>  $idsParaRemover
     */
    public function atualizar(Produto $produto, array $atributos, array $novasImagens, array $idsParaRemover): Produto
    {
        $produto->loadMissing('imagens');

        $imagensRemovidas = $produto->imagens->whereIn('id', $idsParaRemover)->values();
        $imagensMantidas = $produto->imagens->whereNotIn('id', $idsParaRemover)->values();
        $caminhosNovos = [];

        try {
            foreach (array_values($novasImagens) as $imagem) {
                $nome = Str::uuid()->toString().'.'.$this->extensao($imagem);
                $caminho = $this->disco->putFileAs('products', $imagem, $nome);

                if ($caminho === false) {
                    throw new RuntimeException('Não foi possível gravar a imagem do produto.');
                }

                $caminhosNovos[] = $caminho;
            }

            DB::transaction(function () use ($produto, $atributos, $idsParaRemover, $imagensMantidas, $caminhosNovos): void {
                $produto->update([
                    'nome' => $atributos['nome'],
                    'preco' => $atributos['preco'],
                    'cores' => array_values($atributos['cores']),
                    'descricao_curta' => $atributos['descricao_curta'],
                    'quantidade' => $atributos['quantidade'],
                    'sku' => $atributos['sku'],
                    'descricao' => $atributos['descricao'],
                ]);

                if ($idsParaRemover !== []) {
                    $produto->imagens()->whereIn('id', $idsParaRemover)->delete();
                }

                $posicao = 0;

                foreach ($imagensMantidas as $imagem) {
                    $imagem->update(['posicao' => $posicao]);
                    $posicao++;
                }

                foreach ($caminhosNovos as $caminho) {
                    $produto->imagens()->create([
                        'path' => $caminho,
                        'posicao' => $posicao,
                    ]);
                    $posicao++;
                }
            });
        } catch (Throwable $excecao) {
            foreach ($caminhosNovos as $caminho) {
                $this->disco->delete($caminho);
            }

            throw $excecao;
        }

        foreach ($imagensRemovidas as $imagem) {
            try {
                if ($this->disco->delete($imagem->path) === false) {
                    Log::warning('Limpeza pendente de imagem após atualização do produto.', [
                        'id_produto' => $produto->id,
                        'path' => $imagem->path,
                    ]);
                }
            } catch (Throwable $excecao) {
                Log::warning('Limpeza pendente de imagem após atualização do produto.', [
                    'id_produto' => $produto->id,
                    'path' => $imagem->path,
                    'exception' => $excecao->getMessage(),
                ]);
            }
        }

        return $produto->refresh()->load('imagens');
    }

    private function extensao(UploadedFile $imagem): string
    {
        return match ($imagem->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Tipo de imagem não suportado.'),
        };
    }
}
