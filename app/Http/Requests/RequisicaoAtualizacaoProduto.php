<?php

namespace App\Http\Requests;

use App\Models\Produto;
use App\Support\RegrasFormularioProduto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RequisicaoAtualizacaoProduto extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acessar-admin') === true;
    }

    protected function prepareForValidation(): void
    {
        RegrasFormularioProduto::preparar($this);

        $ids = $this->input('ids_imagens_remover', []);

        if (! is_array($ids)) {
            $ids = [];
        }

        $this->merge([
            'ids_imagens_remover' => array_values(array_filter(
                array_map(static fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null, $ids),
                static fn (?int $id): bool => $id !== null,
            )),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $produto = $this->produto();

        return array_merge(
            RegrasFormularioProduto::novasImagens(obrigatorio: false),
            RegrasFormularioProduto::regrasCampos($produto, incluirQuantidade: false),
            [
                'ids_imagens_remover' => ['sometimes', 'array', 'distinct'],
                'ids_imagens_remover.*' => [
                    'integer',
                    Rule::exists('imagens_produto', 'id')->where('produto_id', $produto->id),
                ],
            ],
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $produto = $this->produto()->loadMissing('imagens');
            $ids = $this->input('ids_imagens_remover', []);
            $mantidas = $produto->imagens->whereNotIn('id', $ids)->count();
            $novas = count($this->novosArquivosImagem());
            $total = $mantidas + $novas;

            if ($total < 1 || $total > 5) {
                $validator->errors()->add(
                    'imagens',
                    'O produto deve ficar com 1 a 5 imagens após a atualização. Os arquivos precisam ser escolhidos de novo se a validação falhar.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(RegrasFormularioProduto::mensagens(), [
            'ids_imagens_remover.*.exists' => 'A imagem selecionada não pertence a este produto.',
        ]);
    }

    public function produto(): Produto
    {
        /** @var Produto $produto */
        $produto = $this->route('produto');

        return $produto;
    }

    /**
     * @return list<UploadedFile>
     */
    public function novosArquivosImagem(): array
    {
        return array_values(array_filter(
            Arr::wrap($this->file('imagens')),
            static fn (mixed $arquivo): bool => $arquivo instanceof UploadedFile,
        ));
    }
}
