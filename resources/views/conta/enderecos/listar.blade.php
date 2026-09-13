<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Endereços</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <a href="{{ route('conta.enderecos.criar') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Cadastrar endereço</a>
            <div class="bg-white p-6 shadow sm:rounded-lg">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif
                @if ($enderecos->isEmpty())
                    <p class="text-sm text-gray-600">Nenhum endereço cadastrado.</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($enderecos as $endereco)
                            <li class="border-b border-gray-100 pb-3">
                                <p>{{ $endereco->destinatario }} {{ $endereco->padrao ? '(padrão)' : '' }}</p>
                                <p class="text-sm text-gray-600">{{ $endereco->linhaCompleta() }}</p>
                                <div class="mt-2 flex flex-wrap gap-3 text-sm">
                                    <a class="text-blue-600" href="{{ route('conta.enderecos.editar', $endereco) }}">Editar</a>
                                    @unless ($endereco->padrao)
                                        <form method="POST" action="{{ route('conta.enderecos.padrao', $endereco) }}">@csrf<button class="text-blue-600">Tornar padrão</button></form>
                                    @endunless
                                    <form method="POST" action="{{ route('conta.enderecos.excluir', $endereco) }}">@csrf @method('DELETE')<button class="text-red-600">Excluir</button></form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-layout-aplicacao>
