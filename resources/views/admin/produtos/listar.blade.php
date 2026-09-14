<x-layout-aplicacao>
    <x-slot name="cabecalho">
        <h2 id="titulo-produtos" class="font-semibold text-xl text-gray-800 leading-tight">
            Produtos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <form method="GET" action="{{ route('admin.produtos.listar') }}" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="q" class="block text-xs font-medium text-gray-600">Buscar por nome ou SKU</label>
                            <input id="q" name="q" type="search" value="{{ $q ?? '' }}" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-800 text-white text-xs font-semibold uppercase rounded-md">Buscar</button>
                        @if (($q ?? '') !== '')
                            <a href="{{ route('admin.produtos.listar') }}" class="text-sm text-blue-600">Limpar busca</a>
                        @endif
                    </form>
                    <a id="link-cadastrar-produto" href="{{ route('admin.produtos.criar') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Cadastrar produto
                    </a>
                </div>

                <div class="p-4 sm:p-6">
                    @php
                        $chaveStatus = session('status');
                        $mensagemStatus = match ($chaveStatus) {
                            'produto-criado' => 'Produto cadastrado.',
                            'produto-atualizado' => 'Produto atualizado.',
                            'produto-excluido' => 'Produto excluído.',
                            'produto-excluido-com-limpeza-pendente' => 'O produto foi excluído, mas algumas imagens não puderam ser removidas e precisam de limpeza.',
                            default => $chaveStatus,
                        };
                        $tomStatus = $chaveStatus === 'produto-excluido-com-limpeza-pendente' ? 'aviso' : 'sucesso';
                    @endphp
                    <x-alerta-admin class="mb-4" :status="$mensagemStatus" :tom="$tomStatus" :dismissible="$chaveStatus !== 'produto-excluido-com-limpeza-pendente'" />

                    @if ($produtos->isEmpty())
                        <div class="text-center py-10 space-y-4">
                            <p class="text-sm text-gray-600">{{ ($q ?? '') !== '' ? 'Nenhum produto encontrado para esta busca.' : 'Nenhum produto cadastrado ainda.' }}</p>
                            @if (($q ?? '') !== '')
                                <a href="{{ route('admin.produtos.listar') }}" class="text-sm font-semibold text-blue-600">Limpar busca</a>
                            @else
                            <a href="{{ route('admin.produtos.criar') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Cadastrar produto
                            </a>
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm" aria-labelledby="titulo-produtos">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Capa</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Nome</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ 'SKU' }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Preço (BRL)</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Estoque</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Visibilidade</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Cores</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($produtos as $produto)
                                        @php
                                            $urlCapa = $produto->urlCapa();
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-3">
                                                @if ($urlCapa)
                                                    <img src="{{ $urlCapa }}" alt="{{ $produto->nome }}" class="h-12 w-12 rounded object-contain border border-gray-200 bg-gray-50">
                                                @else
                                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400">Sem capa</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-gray-800 whitespace-nowrap">{{ $produto->nome }}</td>
                                            <td class="px-3 py-3 text-gray-700 font-mono whitespace-nowrap">{{ $produto->sku }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $produto->precoFormatado() }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $produto->quantidade }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $produto->estaPublicado() ? 'Publicado' : 'Oculto' }}</td>
                                            <td class="px-3 py-3 text-gray-700">{{ implode(', ', array_map(fn (string $cor): string => \App\Support\CoresProduto::rotulo($cor), $produto->coresExibidas())) }}</td>
                                            <td class="px-3 py-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1" x-data="{ mais: false }" @keydown.escape.window="if (mais) { mais = false; $refs.mais?.focus() }" @click.outside="mais = false">
                                                    <a id="editar-produto-{{ $produto->id }}" href="{{ route('admin.produtos.editar', $produto) }}" class="relative z-10 font-semibold text-blue-600 hover:text-blue-500">
                                                        Editar
                                                    </a>
                                                    <button
                                                        type="button"
                                                        id="excluir-produto-{{ $produto->id }}"
                                                        class="relative z-10 font-semibold text-red-600 hover:text-red-500"
                                                        x-on:click.prevent="$dispatch('abrir-modal', { nome: 'confirmar-exclusao-produto-{{ $produto->id }}', gatilho: $el.id })"
                                                    >
                                                        Excluir
                                                    </button>
                                                    <div class="relative">
                                                        <button
                                                            type="button"
                                                            x-ref="mais"
                                                            id="mais-produto-{{ $produto->id }}"
                                                            class="relative z-10 font-semibold text-blue-600 hover:text-blue-500"
                                                            @click="mais = ! mais"
                                                            :aria-expanded="mais.toString()"
                                                        >
                                                            Mais
                                                        </button>
                                                        <div x-show="mais" x-cloak class="mt-1 flex flex-col gap-1 items-start" style="display: none;">
                                                            <a href="{{ route('admin.produtos.criar', ['origem' => $produto->id]) }}" class="font-semibold text-blue-600 hover:text-blue-500">
                                                                Duplicar
                                                            </a>
                                                            <a href="{{ route('admin.estoque.exibir', $produto) }}" class="font-semibold text-blue-600 hover:text-blue-500">
                                                                Estoque
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @foreach ($produtos as $produto)
                            <x-janela-modal name="confirmar-exclusao-produto-{{ $produto->id }}" largura-maxima="lg" focavel>
                                <form method="POST" action="{{ route('admin.produtos.excluir', $produto) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')

                                    <h2 class="text-lg font-medium text-gray-900">
                                        Excluir produto
                                    </h2>

                                    <p class="mt-2 text-sm text-gray-600">
                                        Você vai excluir definitivamente {{ $produto->nome }} (SKU {{ $produto->sku }}). Isso não pode ser desfeito e também remove as imagens.
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <x-botao-secundario x-on:click="$dispatch('fechar')">
                                            Cancelar
                                        </x-botao-secundario>

                                        <x-botao-perigo class="ms-3">
                                            Excluir
                                        </x-botao-perigo>
                                    </div>
                                </form>
                            </x-janela-modal>
                        @endforeach

                        <div class="mt-6">
                            {{ $produtos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layout-aplicacao>
