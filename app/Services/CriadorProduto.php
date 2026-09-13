<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductCreator
{
    public function __construct(private Filesystem $disk) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $images
     */
    public function create(array $attributes, array $images): Product
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($attributes, $images, &$storedPaths): Product {
                $product = Product::query()->create([
                    'name' => $attributes['name'],
                    'price' => $attributes['price'],
                    'colors' => array_values($attributes['colors']),
                    'short_description' => $attributes['short_description'],
                    'qty' => $attributes['qty'],
                    'sku' => $attributes['sku'],
                    'description' => $attributes['description'],
                ]);

                foreach (array_values($images) as $position => $image) {
                    $filename = Str::uuid()->toString().'.'.$this->extensionFor($image);
                    $path = $this->disk->putFileAs('products', $image, $filename);

                    if ($path === false) {
                        throw new RuntimeException('Unable to store the product image.');
                    }

                    $storedPaths[] = $path;

                    $product->images()->create([
                        'path' => $path,
                        'position' => $position,
                    ]);
                }

                return $product->load('images');
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                $this->disk->delete($path);
            }

            throw $exception;
        }
    }

    private function extensionFor(UploadedFile $image): string
    {
        return match ($image->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Unsupported image type.'),
        };
    }
}
