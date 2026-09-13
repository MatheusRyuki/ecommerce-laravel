<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Pedido {{ $pedido->codigo }}</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow sm:rounded-lg space-y-2 text-sm">
            <p>Cliente: {{ $pedido->usuario->name }} ({{ $pedido->usuario->email }})</p>
            <p>{{ $pedido->observacao_pagamento }}</p>
            <ul>
                @foreach ($pedido->itens as $item)
                    <li>{{ $item->nome }} ({{ $item->sku }}) — {{ $item->cor }} × {{ $item->quantidade }}</li>
                @endforeach
            </ul>
            <p>Total: {{ $pedido->totalFormatado() }}</p>
        </div>
    </div>
</x-layout-aplicacao>
