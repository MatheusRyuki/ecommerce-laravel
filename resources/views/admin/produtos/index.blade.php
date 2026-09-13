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
                    <h3 class="text-lg font-semibold text-gray-800">Produtos</h3>
                    <a id="create-product-link" href="{{ route('admin.produtos.criar') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Cadastrar produto
                    </a>
                </div>

                <div class="p-4 sm:p-6">
                    @php
                        $statusKey = session('status');
                        $statusMessage = match ($statusKey) {
                            'produto-criado' => 'Produto cadastrado.',
                            'produto-atualizado' => 'Produto atualizado.',
                            'produto-excluido' => 'Produto excluído.',
                            'produto-excluido-com-limpeza-pendente' => 'O produto foi excluído, mas algumas imagens não puderam ser removidas e precisam de limpeza.',
                            default => $statusKey,
                        };
                        $statusTone = $statusKey === 'produto-excluido-com-limpeza-pendente' ? 'warning' : 'success';
                    @endphp
                    <x-admin-alert class="mb-4" :status="$statusMessage" :tone="$statusTone" :dismissible="$statusKey !== 'produto-excluido-com-limpeza-pendente'" />

                    @if ($produtos->isEmpty())
                        <div class="text-center py-10 space-y-4">
                            <p class="text-sm text-gray-600">Nenhum produto cadastrado ainda.</p>
                            <a href="{{ route('admin.produtos.criar') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Cadastrar produto
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Capa</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Nome</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ 'SKU' }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Preço (BRL)</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Qtd.</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Cores</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($produtos as $produto)
                                        @php
                                            $coverUrl = $produto->urlCapa();
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-3">
                                                @if ($coverUrl)
                                                    <img src="{{ $coverUrl }}" alt="{{ $produto->name }}" class="h-12 w-12 rounded object-contain border border-gray-200 bg-gray-50">
                                                @else
                                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400">Sem capa</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-gray-800 whitespace-nowrap">{{ $produto->name }}</td>
                                            <td class="px-3 py-3 text-gray-700 font-mono whitespace-nowrap">{{ $produto->sku }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $produto->precoFormatado() }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $produto->qty }}</td>
                                            <td class="px-3 py-3 text-gray-700">{{ implode(', ', array_map(fn (string $cor): string => \App\Support\CoresProduto::rotulo($cor), $produto->coresExibidas())) }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                    <a id="edit-product-{{ $produto->id }}" href="{{ route('admin.produtos.editar', $produto) }}" class="relative z-10 font-semibold text-blue-600 hover:text-blue-500">
                                                        Editar
                                                    </a>
                                                    <button
                                                        type="button"
                                                        id="delete-product-{{ $produto->id }}"
                                                        class="relative z-10 font-semibold text-red-600 hover:text-red-500"
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-product-deletion-{{ $produto->id }}')"
                                                    >
                                                        Excluir
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @foreach ($produtos as $produto)
                            <x-modal name="confirm-product-deletion-{{ $produto->id }}" maxWidth="lg" focusable>
                                <form method="POST" action="{{ route('admin.produtos.excluir', $produto) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')

                                    <h2 class="text-lg font-medium text-gray-900">
                                        Excluir produto
                                    </h2>

                                    <p class="mt-2 text-sm text-gray-600">
                                        Você vai excluir definitivamente {{ $produto->name }} (SKU {{ $produto->sku }}). Isso não pode ser desfeito e também remove as imagens.
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">
                                            Cancelar
                                        </x-secondary-button>

                                        <x-danger-button class="ms-3">
                                            Excluir
                                        </x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach

                        <div class="mt-6">
                            {{ $produtos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
