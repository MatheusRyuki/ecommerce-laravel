<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductCreator;
use App\Support\ProductColors;
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

    public function run(ProductCreator $creator): void
    {
        if (Product::query()->where('sku', self::SKU)->exists()) {
            return;
        }

        $images = [];

        foreach (self::IMAGE_FILES as $filename) {
            $path = public_path('frontend/images/'.$filename);

            $images[] = new UploadedFile($path, $filename, 'image/jpeg', null, true);
        }

        $creator->create([
            'name' => 'Bolsa Galeria Demo',
            'price' => '89.90',
            'colors' => [ProductColors::all()[2], ProductColors::all()[3]],
            'short_description' => 'Produto de demonstração com três fotos do template para a galeria.',
            'qty' => 20,
            'sku' => self::SKU,
            'description' => '<p>Registro de estudo para capa, galeria e miniatura do carrinho.</p>',
        ], $images);
    }
}
