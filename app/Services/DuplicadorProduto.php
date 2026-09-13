<?php

namespace App\Services;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DuplicadorProduto
{
    public function __construct(
        private Filesystem $disco,
        private RegistradorEstoque $estoque,
    ) {}

    public function duplicar(Produto $origem, array $atributos, Usuario $responsavel): Produto
    {
        $origem->load('imagens');
        $caminhosNovos = [];

        try {
            return DB::transaction(function () use ($origem, $atributos, $responsavel, &$caminhosNovos): Produto {
                $copia = Produto::query()->create([
                    'nome' => $atributos['nome'],
                    'preco' => $atributos['preco'],
                    'cores' => array_values($atributos['cores']),
                    'descricao_curta' => $atributos['descricao_curta'],
                    'quantidade' => 0,
                    'sku' => $atributos['sku'],
                    'publicado' => false,
                    'categoria_id' => $atributos['categoria_id'] ?? $origem->categoria_id,
                    'descricao' => $atributos['descricao'],
                ]);

                foreach ($origem->imagens as $posicao => $imagem) {
                    $extensao = pathinfo($imagem->caminho, PATHINFO_EXTENSION) ?: 'jpg';
                    $destino = 'products/'.Str::uuid()->toString().'.'.$extensao;

                    if (! $this->disco->exists($imagem->caminho) || ! $this->disco->copy($imagem->caminho, $destino)) {
                        throw new RuntimeException('Não foi possível copiar as imagens do produto.');
                    }

                    $caminhosNovos[] = $destino;
                    $copia->imagens()->create([
                        'caminho' => $destino,
                        'posicao' => $posicao,
                    ]);
                }

                $this->estoque->definir(
                    $copia,
                    (int) $atributos['quantidade'],
                    MovimentacaoEstoque::TIPO_DUPLICACAO,
                    'Duplicação do produto '.$origem->sku.'.',
                    $responsavel,
                );

                return $copia->load('imagens');
            });
        } catch (Throwable $excecao) {
            foreach ($caminhosNovos as $caminho) {
                $this->disco->delete($caminho);
            }

            throw $excecao;
        }
    }
}
