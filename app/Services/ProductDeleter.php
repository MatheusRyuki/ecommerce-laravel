<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductDeleter
{
    public function __construct(private Filesystem $disk) {}

    /**
     * Permanently delete a product and its image records, then attempt to remove files.
     *
     * @return list<string> Paths that remain on disk after a confirmed database delete.
     */
    public function delete(Product $product): array
    {
        $product->loadMissing('images');

        $productId = $product->id;
        $paths = $product->images
            ->pluck('path')
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($product): void {
            $product->delete();
        });

        $pending = [];

        foreach ($paths as $path) {
            try {
                if (! $this->disk->exists($path)) {
                    continue;
                }

                if ($this->disk->delete($path) === false) {
                    $pending[] = $path;
                    $this->logPendingCleanup($productId, $path);
                }
            } catch (Throwable $exception) {
                $pending[] = $path;
                $this->logPendingCleanup($productId, $path, $exception->getMessage());
            }
        }

        return $pending;
    }

    private function logPendingCleanup(int $productId, string $path, ?string $exception = null): void
    {
        Log::warning('Pending product image cleanup after delete.', array_filter([
            'disk' => 'public',
            'product_id' => $productId,
            'path' => $path,
            'exception' => $exception,
        ]));
    }
}
