<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Meus pedidos</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif
                @if ($pedidos->isEmpty())
                    <p class="text-sm text-gray-600">Você ainda não fez pedidos.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($pedidos as $pedido)
                            <li>
                                <a class="font-medium text-blue-600" href="{{ route('conta.pedidos.exibir', $pedido) }}">{{ $pedido->codigo }}</a>
                                — {{ $pedido->totalFormatado() }} — aguardando pagamento
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">{{ $pedidos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layout-aplicacao>
