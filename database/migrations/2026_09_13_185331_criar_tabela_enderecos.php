<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('destinatario', 120);
            $table->char('cep', 8);
            $table->string('logradouro', 180);
            $table->string('numero', 20);
            $table->string('complemento', 80)->nullable();
            $table->string('bairro', 80);
            $table->string('cidade', 80);
            $table->char('uf', 2);
            $table->boolean('padrao')->default(false);
            $table->timestamps();
        });

        Schema::table('estados_carrinho', function (Blueprint $table) {
            $table->foreign('endereco_id')->references('id')->on('enderecos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estados_carrinho', function (Blueprint $table) {
            $table->dropForeign(['endereco_id']);
        });

        Schema::dropIfExists('enderecos');
    }
};
