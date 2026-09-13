<?php

namespace App\Support;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegrasFormularioProduto
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
                'description' => app(SanitizadorDescricaoProduto::class)->sanitizar($request->description),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributes(?Produto $ignoredForSku = null): array
    {
        $skuUnique = Rule::unique('products', 'sku');

        if ($ignoredForSku !== null) {
            $skuUnique = $skuUnique->ignore($ignoredForSku);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'colors' => ['required', 'array', 'min:1', 'distinct'],
            'colors.*' => ['required', 'string', Rule::in(CoresProduto::todas())],
            'short_description' => ['required', 'string', 'max:500'],
            'qty' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QTY],
            'sku' => ['required', 'string', 'max:100', $skuUnique],
            'description' => [
                'required',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! app(SanitizadorDescricaoProduto::class)->temTextoEfetivo($value)) {
                        $fail('A descrição precisa ter texto visível.');
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
            'images.required' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'images.min' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'images.max' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'sku.unique' => 'Este SKU já está em uso.',
        ];
    }
}
