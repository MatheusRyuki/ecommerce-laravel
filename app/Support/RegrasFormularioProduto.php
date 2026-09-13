<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductFormRules
{
    public const MAX_QTY = 4_294_967_295;

    public static function prepare(FormRequest $request): void
    {
        if (is_string($request->sku)) {
            $request->merge([
                'sku' => mb_strtoupper(trim($request->sku), 'UTF-8'),
            ]);
        }

        if (is_string($request->description)) {
            $request->merge([
                'description' => app(ProductDescriptionSanitizer::class)->sanitize($request->description),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributes(?Product $ignoredForSku = null): array
    {
        $skuUnique = Rule::unique('products', 'sku');

        if ($ignoredForSku !== null) {
            $skuUnique = $skuUnique->ignore($ignoredForSku);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'colors' => ['required', 'array', 'min:1', 'distinct'],
            'colors.*' => ['required', 'string', Rule::in(ProductColors::all())],
            'short_description' => ['required', 'string', 'max:500'],
            'qty' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QTY],
            'sku' => ['required', 'string', 'max:100', $skuUnique],
            'description' => [
                'required',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! app(ProductDescriptionSanitizer::class)->hasEffectiveText($value)) {
                        $fail(__('The description must contain actual text.'));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function newImages(bool $required): array
    {
        return [
            'images' => $required
                ? ['required', 'array', 'min:1', 'max:5']
                : ['sometimes', 'array', 'max:5'],
            'images.*' => [
                'required',
                'file',
                'max:2048',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'images.required' => __('Select between 1 and 5 images. Files must be chosen again if validation fails.'),
            'images.min' => __('Select between 1 and 5 images. Files must be chosen again if validation fails.'),
            'images.max' => __('Select between 1 and 5 images. Files must be chosen again if validation fails.'),
            'sku.unique' => __('This SKU is already in use.'),
        ];
    }
}
