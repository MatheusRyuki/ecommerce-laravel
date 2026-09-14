<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Cupons</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <a href="{{ route('admin.cupons.criar') }}" class="inline-flex mb-4 items-center px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Cadastrar cupom</a>
        <div class="bg-white p-6 shadow sm:rounded-lg overflow-x-auto">
            @if (session('status'))<p class="mb-3 text-sm text-green-700">{{ session('status') }}</p>@endif
            @if ($cupons->isEmpty())
                <p class="text-sm text-gray-600">Nenhum cupom.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead><tr><th class="text-left py-2">Código</th><th>Tipo</th><th>Valor</th><th>Ativo</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($cupons as $cupom)
                        <tr>
                            <td class="py-2">{{ $cupom->codigo }}</td>
                            <td>{{ $cupom->rotuloTipo() }}</td>
                            <td>{{ $cupom->valor }}</td>
                            <td>{{ $cupom->rotuloAtivo() }}{{ $cupom->consumido_em ? ' (usado)' : '' }}</td>
                            <td><a class="text-blue-600" href="{{ route('admin.cupons.editar', $cupom) }}">Editar</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $cupons->links() }}</div>
            @endif
        </div>
    </div>
</x-layout-aplicacao>
