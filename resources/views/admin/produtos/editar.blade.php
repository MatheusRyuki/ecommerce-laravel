@push('vite')
    @vite(['resources/js/formulario-produto-admin.js'])
@endpush

@php
    $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
    $selectedColors = old('colors', $produto->colors ?? []);
    $removeIds = old('remove_image_ids', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-blue-600 leading-tight">
            Painel
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Editar produto</h3>
                    <a id="go-back-products" href="{{ route('admin.produtos.index') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ 'Voltar' }}
                    </a>
                </div>

                <form id="admin-product-form" method="POST" action="{{ route('admin.produtos.atualizar', $produto) }}" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    @if ($errors->any())
                        <x-admin-alert class="mb-4" :status="'Corrija os campos destacados.'" tone="danger" :dismissible="false" />
                    @endif

                    <div>
                        <p class="block text-sm font-medium text-gray-700">Imagens atuais</p>
                        <ul class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($produto->imagens as $image)
                                @php
                                    $imageUrl = $image->url();
                                    $available = \Illuminate\Support\Facades\Storage::disk('public')->exists($image->path);
                                @endphp
                                <li class="flex items-start gap-3 rounded-md border border-gray-200 p-3">
                                    @if ($available)
                                        <img src="{{ $imageUrl }}" alt="{{ $produto->name }}" class="h-16 w-16 rounded object-contain border border-gray-200 bg-gray-50">
                                    @else
                                        <span class="inline-flex h-16 w-16 items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400">Sem capa</span>
                                    @endif
                                    <div class="min-w-0">
                                        @if ($image->position === 0)
                                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Capa</p>
                                        @endif
                                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}"
                                                @checked(in_array($image->id, array_map('intval', (array) $removeIds), true))>
                                            Remover ao salvar
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <x-input-error class="mt-2" :messages="$errors->get('remove_image_ids')" />
                        <x-input-error class="mt-2" :messages="$errors->get('remove_image_ids.*')" />
                    </div>

                    <div>
                        <label for="images" class="block text-sm font-medium text-gray-700">Novas imagens</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                            class="{{ $fieldClass }} file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Opcional. JPEG, PNG ou WebP, até 2048 KB cada. Junto com as imagens mantidas, o total deve ficar entre 1 e 5. Novos arquivos precisam ser escolhidos de novo se a validação falhar.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('images')" />
                        <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
                    </div>

                    @include('admin.produtos.partials.fields', [
                        'cores' => $cores,
                        'selectedColors' => $selectedColors,
                        'name' => old('name', $produto->name),
                        'price' => old('price', $produto->price),
                        'shortDescription' => old('short_description', $produto->short_description),
                        'qty' => old('qty', $produto->qty),
                        'sku' => old('sku', $produto->sku),
                        'description' => old('description', $produto->description),
                    ])

                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ 'Salvar alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
