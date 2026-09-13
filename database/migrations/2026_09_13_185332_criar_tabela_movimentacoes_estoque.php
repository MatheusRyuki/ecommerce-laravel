<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable();
            $table->string('tipo', 40);
            $table->unsignedInteger('quantidade_anterior');
            $table->unsignedInteger('quantidade_final');
            $table->integer('delta');
            $table->string('motivo', 255);
            $table->timestamps();
        });

        $adminId = DB::table('users')->where('administrador', true)->orderBy('id')->value('id');

        foreach (DB::table('produtos')->orderBy('id')->get(['id', 'quantidade']) as $produto) {
            DB::table('movimentacoes_estoque')->insert([
                'produto_id' => $produto->id,
                'usuario_id' => $adminId,
                'pedido_id' => null,
                'tipo' => 'posicao_inicial',
                'quantidade_anterior' => $produto->quantidade,
                'quantidade_final' => $produto->quantidade,
                'delta' => 0,
                'motivo' => 'Posição inicial após atualização do sistema.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};
