<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class ConsultaCatalogo
{
    /**
     * @return LengthAwarePaginator<int, Produto>
     */
    public function paginar(Request $request, ?Categoria $categoria = null): LengthAwarePaginator
    {
        $consulta = Produto::query()->publicados()->with(['imagens', 'categoria']);

        if ($categoria !== null) {
            $consulta->where('categoria_id', $categoria->id);
        }

        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            $consulta->where(function ($interno) use ($q): void {
                $interno->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%');
            });
        }

        $cor = CoresProduto::normalizar((string) $request->query('cor', ''));

        if ($cor !== '' && CoresProduto::ehPermitida($cor)) {
            $consulta->where(function ($interno) use ($cor): void {
                $interno->whereJsonContains('cores', $cor)
                    ->orWhere('cores', 'like', '%"'.$cor.'"%');
            });
        }

        if ($request->query('disponivel') === '1') {
            $consulta->where('quantidade', '>', 0);
        }

        return $consulta
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return array{q: string, cor: string, disponivel: bool}
     */
    public static function filtros(Request $request): array
    {
        $cor = CoresProduto::normalizar((string) $request->query('cor', ''));

        return [
            'q' => trim((string) $request->query('q', '')),
            'cor' => CoresProduto::ehPermitida($cor) ? $cor : '',
            'disponivel' => $request->query('disponivel') === '1',
        ];
    }
}
