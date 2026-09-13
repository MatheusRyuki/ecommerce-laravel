<?php

namespace App\Http\Requests;

use App\Support\RegrasFormularioProduto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequisicaoCadastroProduto extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acessar-admin') === true;
    }

    protected function prepareForValidation(): void
    {
        RegrasFormularioProduto::preparar($this);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            RegrasFormularioProduto::novasImagens(obrigatorio: true),
            RegrasFormularioProduto::regrasCampos(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return RegrasFormularioProduto::mensagens();
    }
}
