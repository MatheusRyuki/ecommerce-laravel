<?php

namespace App\Support;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegrasFormularioProduto
{
    public const QUANTIDADE_MAXIMA = 4_294_967_295;

    public static function preparar(FormRequest $request): void
    {
        if (is_string($request->sku)) {
            $request->merge([
                'sku' => mb_strtoupper(trim($request->sku), 'UTF-8'),
            ]);
        }

        if (is_string($request->descricao)) {
            $request->merge([
                'descricao' => app(SanitizadorDescricaoProduto::class)->sanitizar($request->descricao),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function regrasCampos(?Produto $produtoIgnoradoNoSku = null): array
    {
        $skuUnico = Rule::unique('produtos', 'sku');

        if ($produtoIgnoradoNoSku !== null) {
            $skuUnico = $skuUnico->ignore($produtoIgnoradoNoSku);
        }

        return [
            'nome' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'cores' => ['required', 'array', 'min:1', 'distinct'],
            'cores.*' => ['required', 'string', Rule::in(CoresProduto::todas())],
            'descricao_curta' => ['required', 'string', 'max:500'],
            'quantidade' => ['required', 'integer', 'min:0', 'max:'.self::QUANTIDADE_MAXIMA],
            'sku' => ['required', 'string', 'max:100', $skuUnico],
            'descricao' => [
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
    public static function novasImagens(bool $obrigatorio): array
    {
        return [
            'imagens' => $obrigatorio
                ? ['required', 'array', 'min:1', 'max:5']
                : ['sometimes', 'array', 'max:5'],
            'imagens.*' => [
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
    public static function mensagens(): array
    {
        return [
            'imagens.required' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'imagens.min' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'imagens.max' => 'Selecione de 1 a 5 imagens. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
            'sku.unique' => 'Este SKU já está em uso.',
        ];
    }
}
