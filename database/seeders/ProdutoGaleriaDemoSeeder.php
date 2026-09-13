<?php

namespace Database\Seeders;

use App\Models\Produto;
use App\Services\CriadorProduto;
use App\Support\CoresProduto;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class ProdutoGaleriaDemoSeeder extends Seeder
{
    public const SKU = 'DEMO-GALLERY-01';

    /**
     * @var list<string>
     */
    public const ARQUIVOS_IMAGEM = [
        'product_slide_show_1.jpg',
        'product_slide_show_2.jpg',
        'product_slide_show_3.jpg',
    ];

    public function run(CriadorProduto $criador): void
    {
        if (Produto::query()->where('sku', self::SKU)->exists()) {
            return;
        }

        $imagens = [];

        foreach (self::ARQUIVOS_IMAGEM as $arquivo) {
            $caminho = public_path('frontend/images/'.$arquivo);

            $imagens[] = new UploadedFile($caminho, $arquivo, 'image/jpeg', null, true);
        }

        $criador->criar([
            'nome' => 'Bolsa Galeria Demo',
            'preco' => '89.90',
            'cores' => [CoresProduto::todas()[2], CoresProduto::todas()[3]],
            'descricao_curta' => 'Produto de demonstração com três fotos do template para a galeria.',
            'quantidade' => 20,
            'sku' => self::SKU,
            'descricao' => '<p>Registro de estudo para capa, galeria e miniatura do carrinho.</p>',
        ], $imagens);
    }
}
