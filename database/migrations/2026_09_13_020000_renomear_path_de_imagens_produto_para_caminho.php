<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imagens_produto') && Schema::hasColumn('imagens_produto', 'path') && ! Schema::hasColumn('imagens_produto', 'caminho')) {
            Schema::table('imagens_produto', function (Blueprint $table) {
                $table->renameColumn('path', 'caminho');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('imagens_produto') && Schema::hasColumn('imagens_produto', 'caminho') && ! Schema::hasColumn('imagens_produto', 'path')) {
            Schema::table('imagens_produto', function (Blueprint $table) {
                $table->renameColumn('caminho', 'path');
            });
        }
    }
};
