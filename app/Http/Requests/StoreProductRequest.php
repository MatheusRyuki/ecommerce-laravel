<?php

namespace App\Http\Requests;

use App\Support\ProductFormRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    protected function prepareForValidation(): void
    {
        ProductFormRules::prepare($this);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            ProductFormRules::newImages(required: true),
            ProductFormRules::attributes(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ProductFormRules::messages();
    }
}
