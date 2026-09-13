<?php

namespace App\Models;

use App\Support\Cep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endereco extends Model
{
    protected $table = 'enderecos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'destinatario',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'padrao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'padrao' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function cepFormatado(): string
    {
        return Cep::formatar($this->cep);
    }

    /**
     * @return array<string, string>
     */
    public function paraSnapshot(): array
    {
        return [
            'destinatario' => $this->destinatario,
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'uf' => $this->uf,
        ];
    }

    public function linhaCompleta(): string
    {
        $complemento = $this->complemento ? ', '.$this->complemento : '';

        return $this->logradouro.', '.$this->numero.$complemento.' — '.$this->bairro.', '.$this->cidade.'/'.$this->uf.' CEP '.$this->cepFormatado();
    }
}
