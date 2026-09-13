<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faixas_frete', function (Blueprint $table) {
            $table->id();
            $table->char('cep_inicio', 8);
            $table->char('cep_fim', 8);
            $table->decimal('valor', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faixas_frete');
    }
};
