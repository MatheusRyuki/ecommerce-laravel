<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('status', 40);
            $table->decimal('subtotal_produtos', 10, 2);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('frete', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('cupom_codigo', 40)->nullable();
            $table->json('endereco_entrega');
            $table->string('chave_idempotencia', 64);
            $table->text('observacao_pagamento');
            $table->timestamps();

            $table->unique(['usuario_id', 'chave_idempotencia']);
        });

        Schema::create('itens_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('nome', 255);
            $table->string('sku', 100);
            $table->string('cor', 40);
            $table->unsignedInteger('quantidade');
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->foreign('pedido_id')->references('id')->on('pedidos')->nullOnDelete();
        });

        Schema::table('cupons', function (Blueprint $table) {
            $table->foreign('pedido_id')->references('id')->on('pedidos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
        });

        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
        });

        Schema::dropIfExists('itens_pedido');
        Schema::dropIfExists('pedidos');
    }
};
