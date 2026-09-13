@push('vite')
    @vite(['resources/js/formulario-produto-admin.js'])
@endpush

@php
    $classeCampo = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
    $coresSelecionadas = old('cores', $produto->cores ?? []);
    $idsRemover = old('ids_imagens_remover', []);
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
                    <h3 class="text-lg font-semibold text-gray-800">Editar produto</h3>
                    <a id="voltar-produtos" href="{{ route('admin.produtos.listar') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ 'Voltar' }}
                    </a>
                </div>

                <form id="formulario-produto-admin" method="POST" action="{{ route('admin.produtos.atualizar', $produto) }}" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    @if ($errors->any())
                        <x-alerta-admin class="mb-4" :status="'Corrija os campos destacados.'" tom="erro" :dismissible="false" />
                    @endif

                    <div>
                        <p class="block text-sm font-medium text-gray-700">Imagens atuais</p>
                        <ul class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($produto->imagens as $imagem)
                                @php
                                    $urlImagem = $imagem->url();
                                    $arquivoExiste = \App\Support\DiscoArquivosProduto::disco()->exists($imagem->caminho);
                                @endphp
                                <li class="flex items-start gap-3 rounded-md border border-gray-200 p-3">
                                    @if ($arquivoExiste)
                                        <img src="{{ $urlImagem }}" alt="{{ $produto->nome }}" class="h-16 w-16 rounded object-contain border border-gray-200 bg-gray-50">
                                    @else
                                        <span class="inline-flex h-16 w-16 items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400">Sem capa</span>
                                    @endif
                                    <div class="min-w-0">
                                        @if ($imagem->posicao === 0)
                                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Capa</p>
                                        @endif
                                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="ids_imagens_remover[]" value="{{ $imagem->id }}"
                                                @checked(in_array($imagem->id, array_map('intval', (array) $idsRemover), true))>
                                            Remover ao salvar
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <x-erro-campo class="mt-2" :messages="$errors->get('ids_imagens_remover')" />
                        <x-erro-campo class="mt-2" :messages="$errors->get('ids_imagens_remover.*')" />
                    </div>

                    <div>
                        <label for="imagens" class="block text-sm font-medium text-gray-700">Novas imagens</label>
                        <input id="imagens" name="imagens[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                            class="{{ $classeCampo }} file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Opcional. JPEG, PNG ou WebP, até 2048 KB cada. Junto com as imagens mantidas, o total deve ficar entre 1 e 5. Novos arquivos precisam ser escolhidos de novo se a validação falhar.</p>
                        <x-erro-campo class="mt-2" :messages="$errors->get('imagens')" />
                        <x-erro-campo class="mt-2" :messages="$errors->get('imagens.*')" />
                    </div>

                    @include('admin.produtos.partials.fields', [
                        'cores' => $cores,
                        'coresSelecionadas' => $coresSelecionadas,
                        'valorNome' => old('nome', $produto->nome),
                        'valorPreco' => old('preco', $produto->preco),
                        'valorDescricaoCurta' => old('descricao_curta', $produto->descricao_curta),
                        'valorQuantidade' => old('quantidade', $produto->quantidade),
                        'sku' => old('sku', $produto->sku),
                        'valorDescricao' => old('descricao', $produto->descricao),
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
</x-layout-aplicacao>
