<?php

namespace App\Http\Requests;

use App\Support\Cep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisicaoEndereco extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep' => Cep::normalizar((string) $this->input('cep', '')),
            'uf' => mb_strtoupper(trim((string) $this->input('uf', '')), 'UTF-8'),
            'padrao' => $this->boolean('padrao'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'destinatario' => ['required', 'string', 'max:120'],
            'cep' => ['required', 'digits:8'],
            'logradouro' => ['required', 'string', 'max:180'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:80'],
            'bairro' => ['required', 'string', 'max:80'],
            'cidade' => ['required', 'string', 'max:80'],
            'uf' => ['required', 'string', Rule::in(Cep::UFS)],
            'padrao' => ['sometimes', 'boolean'],
        ];
    }
}
