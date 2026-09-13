<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const CORES = [
        'Red' => 'Vermelho',
        'Yellow' => 'Amarelo',
        'Blue' => 'Azul',
        'Green' => 'Verde',
    ];

    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasTable('produtos')) {
            Schema::rename('products', 'produtos');
        }

        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table): void {
                if (Schema::hasColumn('produtos', 'name')) {
                    $table->renameColumn('name', 'nome');
                }
                if (Schema::hasColumn('produtos', 'price')) {
                    $table->renameColumn('price', 'preco');
                }
                if (Schema::hasColumn('produtos', 'colors')) {
                    $table->renameColumn('colors', 'cores');
                }
                if (Schema::hasColumn('produtos', 'short_description')) {
                    $table->renameColumn('short_description', 'descricao_curta');
                }
                if (Schema::hasColumn('produtos', 'qty')) {
                    $table->renameColumn('qty', 'quantidade');
                }
                if (Schema::hasColumn('produtos', 'description')) {
                    $table->renameColumn('description', 'descricao');
                }
            });

            $this->normalizarCores();
        }

        if (Schema::hasTable('product_images') && ! Schema::hasTable('imagens_produto')) {
            Schema::rename('product_images', 'imagens_produto');
        }

        if (Schema::hasTable('imagens_produto')) {
            Schema::table('imagens_produto', function (Blueprint $table): void {
                if (Schema::hasColumn('imagens_produto', 'product_id')) {
                    $table->renameColumn('product_id', 'produto_id');
                }
                if (Schema::hasColumn('imagens_produto', 'position')) {
                    $table->renameColumn('position', 'posicao');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_admin') && ! Schema::hasColumn('users', 'administrador')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('administrador')->default(false);
            });

            DB::table('users')->update([
                'administrador' => DB::raw('is_admin'),
            ]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'administrador') && ! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false);
            });

            DB::table('users')->update([
                'is_admin' => DB::raw('administrador'),
            ]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('administrador');
            });
        }

        if (Schema::hasTable('imagens_produto')) {
            Schema::table('imagens_produto', function (Blueprint $table): void {
                if (Schema::hasColumn('imagens_produto', 'produto_id')) {
                    $table->renameColumn('produto_id', 'product_id');
                }
                if (Schema::hasColumn('imagens_produto', 'posicao')) {
                    $table->renameColumn('posicao', 'position');
                }
            });

            if (! Schema::hasTable('product_images')) {
                Schema::rename('imagens_produto', 'product_images');
            }
        }

        if (Schema::hasTable('produtos')) {
            $this->reverterCores();

            Schema::table('produtos', function (Blueprint $table): void {
                if (Schema::hasColumn('produtos', 'nome')) {
                    $table->renameColumn('nome', 'name');
                }
                if (Schema::hasColumn('produtos', 'preco')) {
                    $table->renameColumn('preco', 'price');
                }
                if (Schema::hasColumn('produtos', 'cores')) {
                    $table->renameColumn('cores', 'colors');
                }
                if (Schema::hasColumn('produtos', 'descricao_curta')) {
                    $table->renameColumn('descricao_curta', 'short_description');
                }
                if (Schema::hasColumn('produtos', 'quantidade')) {
                    $table->renameColumn('quantidade', 'qty');
                }
                if (Schema::hasColumn('produtos', 'descricao')) {
                    $table->renameColumn('descricao', 'description');
                }
            });

            if (! Schema::hasTable('products')) {
                Schema::rename('produtos', 'products');
            }
        }
    }

    private function normalizarCores(): void
    {
        foreach (DB::table('produtos')->get() as $produto) {
            $cores = $this->decodificarCores($produto->cores ?? null);

            if ($cores === []) {
                continue;
            }

            $normalizadas = [];

            foreach ($cores as $cor) {
                if (! is_string($cor) || $cor === '') {
                    continue;
                }

                $normalizadas[] = self::CORES[$cor] ?? $cor;
            }

            DB::table('produtos')->where('id', $produto->id)->update([
                'cores' => json_encode(array_values($normalizadas), JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    private function reverterCores(): void
    {
        $inverso = array_flip(self::CORES);

        foreach (DB::table('produtos')->get() as $produto) {
            $cores = $this->decodificarCores($produto->cores ?? null);
            $revertidas = [];

            foreach ($cores as $cor) {
                if (! is_string($cor) || $cor === '') {
                    continue;
                }

                $revertidas[] = $inverso[$cor] ?? $cor;
            }

            DB::table('produtos')->where('id', $produto->id)->update([
                'cores' => json_encode(array_values($revertidas)),
            ]);
        }
    }

    /**
     * @return list<mixed>
     */
    private function decodificarCores(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (! is_string($valor) || $valor === '') {
            return [];
        }

        $decodificado = json_decode($valor, true);

        return is_array($decodificado) ? $decodificado : [];
    }
};
