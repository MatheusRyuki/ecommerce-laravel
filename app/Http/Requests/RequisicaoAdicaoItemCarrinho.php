<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequisicaoAdicaoItemCarrinho extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'cor' => ['required', 'string'],
            'quantidade' => ['required', 'integer', 'min:1'],
        ];
    }
}
