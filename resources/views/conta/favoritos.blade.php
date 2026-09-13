<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Favoritos</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif
                @if ($favoritos->isEmpty())
                    <p class="text-sm text-gray-600">Você ainda não salvou produtos.</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($favoritos as $favorito)
                            <li class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
                                <a class="font-medium text-blue-600" href="{{ route('loja.produtos.exibir', $favorito->produto) }}">{{ $favorito->produto->nome }}</a>
                                <form method="POST" action="{{ route('conta.favoritos.remover', $favorito->produto) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm text-red-600">Remover</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-layout-aplicacao>
