<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_carrinho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('cor', 40);
            $table->unsignedInteger('quantidade');
            $table->timestamps();

            $table->unique(['usuario_id', 'produto_id', 'cor']);
        });

        Schema::create('estados_carrinho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('cupom_codigo', 40)->nullable();
            $table->unsignedBigInteger('endereco_id')->nullable();
            $table->uuid('chave_checkout')->nullable();
            $table->json('revisao_checkout')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_carrinho');
        Schema::dropIfExists('itens_carrinho');
    }
};
