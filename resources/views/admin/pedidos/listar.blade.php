<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Pedidos</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow sm:rounded-lg">
            @if ($pedidos->isEmpty())
                <p class="text-sm text-gray-600">Nenhum pedido.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($pedidos as $pedido)
                        <li><a class="text-blue-600 font-medium" href="{{ route('admin.pedidos.exibir', $pedido) }}">{{ $pedido->codigo }}</a> — {{ $pedido->usuario->email }} — {{ $pedido->totalFormatado() }}</li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $pedidos->links() }}</div>
            @endif
        </div>
    </div>
</x-layout-aplicacao>
