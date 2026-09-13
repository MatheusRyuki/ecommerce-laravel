<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagens_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('caminho');
            $table->unsignedTinyInteger('posicao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagens_produto');
    }
};
