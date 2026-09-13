<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CriadorProduto
{
    public function __construct(private Filesystem $disco) {}

    /**
     * @param  array<string, mixed>  $atributos
     * @param  list<UploadedFile>  $imagens
     */
    public function criar(array $atributos, array $imagens): Produto
    {
        $caminhos = [];

        try {
            return DB::transaction(function () use ($atributos, $imagens, &$caminhos): Produto {
                $produto = Produto::query()->create([
                    'name' => $atributos['name'],
                    'price' => $atributos['price'],
                    'colors' => array_values($atributos['colors']),
                    'short_description' => $atributos['short_description'],
                    'qty' => $atributos['qty'],
                    'sku' => $atributos['sku'],
                    'description' => $atributos['description'],
                ]);

                foreach (array_values($imagens) as $posicao => $imagem) {
                    $nome = Str::uuid()->toString().'.'.$this->extensao($imagem);
                    $caminho = $this->disco->putFileAs('products', $imagem, $nome);

                    if ($caminho === false) {
                        throw new RuntimeException('Não foi possível gravar a imagem do produto.');
                    }

                    $caminhos[] = $caminho;

                    $produto->imagens()->create([
                        'path' => $caminho,
                        'position' => $posicao,
                    ]);
                }

                return $produto->load('imagens');
            });
        } catch (Throwable $excecao) {
            foreach ($caminhos as $caminho) {
                $this->disco->delete($caminho);
            }

            throw $excecao;
        }
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
