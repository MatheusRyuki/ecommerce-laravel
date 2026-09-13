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
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $item = (string) $this->route('item');

        if ($validator->errors()->has('quantity')) {
            $validator->errors()->add(
                'cart_items.'.$item,
                $validator->errors()->first('quantity')
            );
            $validator->errors()->forget('quantity');
        }

        throw new ValidationException($validator);
    }
}
