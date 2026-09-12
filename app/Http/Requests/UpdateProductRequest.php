<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\ProductFormRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    protected function prepareForValidation(): void
    {
        ProductFormRules::prepare($this);

        $removeIds = $this->input('remove_image_ids', []);

        if (! is_array($removeIds)) {
            $removeIds = [];
        }

        $this->merge([
            'remove_image_ids' => array_values(array_filter(
                array_map(static fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null, $removeIds),
                static fn (?int $id): bool => $id !== null,
            )),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->product();

        return array_merge(
            ProductFormRules::newImages(required: false),
            ProductFormRules::attributes($product),
            [
                'remove_image_ids' => ['sometimes', 'array', 'distinct'],
                'remove_image_ids.*' => [
                    'integer',
                    Rule::exists('product_images', 'id')->where('product_id', $product->id),
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

            $product = $this->product()->loadMissing('images');
            $removeIds = $this->input('remove_image_ids', []);
            $kept = $product->images->whereNotIn('id', $removeIds)->count();
            $incoming = count($this->newImageFiles());
            $total = $kept + $incoming;

            if ($total < 1 || $total > 5) {
                $validator->errors()->add(
                    'images',
                    __('The product must have between 1 and 5 images after the update. Files must be chosen again if validation fails.'),
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(ProductFormRules::messages(), [
            'remove_image_ids.*.exists' => __('The selected image does not belong to this product.'),
        ]);
    }

    public function product(): Product
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $product;
    }

    /**
     * @return list<UploadedFile>
     */
    public function newImageFiles(): array
    {
        return array_values(array_filter(
            Arr::wrap($this->file('images')),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }
}
