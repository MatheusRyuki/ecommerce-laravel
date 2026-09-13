@push('vite')
    @vite(['resources/js/formulario-produto-admin.js'])
@endpush

@php
    $classeCampo = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
    $coresSelecionadas = old('cores', []);
@endphp

<x-layout-aplicacao>
    <x-slot name="cabecalho">
        <h2 class="font-semibold text-xl text-blue-600 leading-tight">
            Painel
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Cadastrar produto</h3>
                    <a id="voltar-produtos" href="{{ route('admin.produtos.listar') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ 'Voltar' }}
                    </a>
                </div>

                <form id="formulario-produto-admin" method="POST" action="{{ route('admin.produtos.salvar') }}" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5">
                    @csrf

                    @if ($errors->any())
                        <x-alerta-admin class="mb-4" :status="'Corrija os campos destacados.'" tom="erro" :dismissible="false" />
                    @endif
                    <x-alerta-admin class="mb-4" :status="session('status') === 'produto-criado' ? 'Produto cadastrado.' : session('status')" />

                    <div>
                        <label for="imagens" class="block text-sm font-medium text-gray-700">{{ 'Imagens' }}</label>
                        <input id="imagens" name="imagens[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                            class="{{ $classeCampo }} file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm">
                        <p class="mt-1 text-xs text-gray-500">JPEG, PNG ou WebP. 1 a 5 arquivos, até 2048 KB cada. Os arquivos precisam ser escolhidos de novo se a validação falhar.</p>
                        <x-erro-campo class="mt-2" :messages="$errors->get('imagens')" />
                        <x-erro-campo class="mt-2" :messages="$errors->get('imagens.*')" />
                    </div>

                    @include('admin.produtos.partials.fields', [
                        'cores' => $cores,
                        'coresSelecionadas' => $coresSelecionadas,
                        'valorNome' => old('nome'),
                        'valorPreco' => old('preco'),
                        'valorDescricaoCurta' => old('descricao_curta'),
                        'valorQuantidade' => old('quantidade'),
                        'sku' => old('sku'),
                        'valorDescricao' => old('descricao'),
                    ])

                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Cadastrar produto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layout-aplicacao>
