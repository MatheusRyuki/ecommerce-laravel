<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class DiscoArquivosProduto
{
    public static function nome(): string
    {
        return (string) config('filesystems.disco_produtos', 'public');
    }

    public static function disco(): Filesystem
    {
        return Storage::disk(self::nome());
    }
}
