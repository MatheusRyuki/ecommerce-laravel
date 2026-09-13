<?php

namespace Database\Seeders;

use App\Models\Produto;
use App\Services\CriadorProduto;
use App\Support\CoresProduto;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class DemoGalleryProductSeeder extends Seeder
{
    public const SKU = 'DEMO-GALLERY-01';

    /**
     * @var list<string>
     */
    public const IMAGE_FILES = [
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

        foreach (self::IMAGE_FILES as $arquivo) {
            $caminho = public_path('frontend/images/'.$arquivo);

            $imagens[] = new UploadedFile($caminho, $arquivo, 'image/jpeg', null, true);
        }

        $criador->criar([
            'name' => 'Bolsa Galeria Demo',
            'price' => '89.90',
            'colors' => [CoresProduto::todas()[2], CoresProduto::todas()[3]],
            'short_description' => 'Produto de demonstração com três fotos do template para a galeria.',
            'qty' => 20,
            'sku' => self::SKU,
            'description' => '<p>Registro de estudo para capa, galeria e miniatura do carrinho.</p>',
        ], $imagens);
    }
}
