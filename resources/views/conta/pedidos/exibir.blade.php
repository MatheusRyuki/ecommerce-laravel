<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Pedido {{ $pedido->codigo }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg space-y-3 text-sm">
                @if (session('status'))
                    <p class="text-green-700">{{ session('status') }}</p>
                @endif
                <p>{{ $pedido->observacao_pagamento }}</p>
                <p>Status: aguardando pagamento</p>
                <ul>
                    @foreach ($pedido->itens as $item)
                        <li>{{ $item->nome }} ({{ $item->sku }}) — {{ $item->cor }} × {{ $item->quantidade }} — {{ \App\Support\Dinheiro::formatarBrl((string) $item->subtotal) }}</li>
                    @endforeach
                </ul>
                <p>Produtos: {{ \App\Support\Dinheiro::formatarBrl((string) $pedido->subtotal_produtos) }}</p>
                <p>Desconto: {{ \App\Support\Dinheiro::formatarBrl((string) $pedido->desconto) }}</p>
                <p>Frete: {{ \App\Support\Dinheiro::formatarBrl((string) $pedido->frete) }}</p>
                <p>Total: {{ $pedido->totalFormatado() }}</p>
                <p>Entrega: {{ $pedido->endereco_entrega['logradouro'] ?? '' }}, {{ $pedido->endereco_entrega['numero'] ?? '' }} — CEP {{ \App\Support\Cep::formatar($pedido->endereco_entrega['cep'] ?? '') }}</p>
            </div>
        </div>
    </div>
</x-layout-aplicacao>
