<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('tipo', 20);
            $table->decimal('valor', 10, 2);
            $table->timestamp('valido_de')->nullable();
            $table->timestamp('valido_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('uso_unico')->default(false);
            $table->timestamp('consumido_em')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
