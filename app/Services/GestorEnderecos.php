<?php

namespace App\Services;

use App\Models\Endereco;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class GestorEnderecos
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(Usuario $usuario, array $dados): Endereco
    {
        return DB::transaction(function () use ($usuario, $dados): Endereco {
            $existe = Endereco::query()->where('usuario_id', $usuario->id)->lockForUpdate()->exists();
            $dados['usuario_id'] = $usuario->id;
            $dados['padrao'] = ! $existe || ($dados['padrao'] ?? false);

            $endereco = Endereco::query()->create($dados);

            if ($endereco->padrao) {
                $this->marcarPadrao($usuario, $endereco);
            }

            return $endereco;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Endereco $endereco, array $dados): Endereco
    {
        return DB::transaction(function () use ($endereco, $dados): Endereco {
            $endereco->fill($dados);
            $endereco->save();

            if ($endereco->padrao) {
                $this->marcarPadrao($endereco->usuario, $endereco);
            } else {
                $this->garantirUmPadrao($endereco->usuario);
            }

            return $endereco;
        });
    }

    public function excluir(Endereco $endereco): void
    {
        DB::transaction(function () use ($endereco): void {
            $usuario = $endereco->usuario;
            $eraPadrao = $endereco->padrao;
            $endereco->delete();

            if ($eraPadrao) {
                $this->garantirUmPadrao($usuario);
            }
        });
    }

    public function marcarPadrao(Usuario $usuario, Endereco $endereco): void
    {
        Endereco::query()->where('usuario_id', $usuario->id)->update(['padrao' => false]);
        $endereco->padrao = true;
        $endereco->save();
    }

    private function garantirUmPadrao(Usuario $usuario): void
    {
        if (Endereco::query()->where('usuario_id', $usuario->id)->where('padrao', true)->exists()) {
            return;
        }

        $proximo = Endereco::query()->where('usuario_id', $usuario->id)->orderBy('id')->first();

        if ($proximo !== null) {
            $proximo->padrao = true;
            $proximo->save();
        }
    }
}
