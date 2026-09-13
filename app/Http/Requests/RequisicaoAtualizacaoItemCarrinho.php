<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RequisicaoAtualizacaoItemCarrinho extends FormRequest
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
            'quantidade' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $item = (string) $this->route('item');

        if ($validator->errors()->has('quantidade')) {
            $validator->errors()->add(
                'itens_carrinho.'.$item,
                $validator->errors()->first('quantidade')
            );
            $validator->errors()->forget('quantidade');
        }

        throw new ValidationException($validator);
    }
}
