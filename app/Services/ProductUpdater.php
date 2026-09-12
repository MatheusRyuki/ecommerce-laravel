<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductUpdater
{
    public function __construct(private Filesystem $disk) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeImageIds
     */
    public function update(Product $product, array $attributes, array $newImages, array $removeImageIds): Product
    {
        $product->loadMissing('images');

        $removedImages = $product->images->whereIn('id', $removeImageIds)->values();
        $keptImages = $product->images->whereNotIn('id', $removeImageIds)->values();
        $newPaths = [];

        try {
            foreach (array_values($newImages) as $image) {
                $filename = Str::uuid()->toString().'.'.$this->extensionFor($image);
                $path = $this->disk->putFileAs('products', $image, $filename);

                if ($path === false) {
                    throw new RuntimeException('Unable to store the product image.');
                }

                $newPaths[] = $path;
            }

            DB::transaction(function () use ($product, $attributes, $removeImageIds, $keptImages, $newPaths): void {
                $product->update([
                    'name' => $attributes['name'],
                    'price' => $attributes['price'],
                    'colors' => array_values($attributes['colors']),
                    'short_description' => $attributes['short_description'],
                    'qty' => $attributes['qty'],
                    'sku' => $attributes['sku'],
                    'description' => $attributes['description'],
                ]);

                if ($removeImageIds !== []) {
                    $product->images()->whereIn('id', $removeImageIds)->delete();
                }

                $position = 0;

                foreach ($keptImages as $image) {
                    $image->update(['position' => $position]);
                    $position++;
                }

                foreach ($newPaths as $path) {
                    $product->images()->create([
                        'path' => $path,
                        'position' => $position,
                    ]);
                    $position++;
                }
            });
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                $this->disk->delete($path);
            }

            throw $exception;
        }

        foreach ($removedImages as $image) {
            try {
                if ($this->disk->delete($image->path) === false) {
                    Log::warning('Pending product image cleanup after update.', [
                        'product_id' => $product->id,
                        'path' => $image->path,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('Pending product image cleanup after update.', [
                    'product_id' => $product->id,
                    'path' => $image->path,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $product->refresh()->load('images');
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
