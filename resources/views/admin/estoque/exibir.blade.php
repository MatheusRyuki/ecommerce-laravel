<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Estoque — {{ $produto->nome }}</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <p class="text-sm">Quantidade atual: <strong>{{ $produto->quantidade }}</strong></p>
        <form method="POST" action="{{ route('admin.estoque.registrar', $produto) }}" class="bg-white p-6 shadow sm:rounded-lg space-y-4 max-w-xl">
            @csrf
            @if (session('status'))<p class="text-sm text-green-700">{{ session('status') }}</p>@endif
            <div>
                <label class="block text-sm" for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                    <option value="ajuste">Ajuste (definir quantidade final)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm" for="quantidade">Quantidade</label>
                <input id="quantidade" name="quantidade" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300" required>
                <x-erro-campo :messages="$errors->get('quantidade')" />
            </div>
            <div>
                <label class="block text-sm" for="motivo">Motivo</label>
                <input id="motivo" name="motivo" class="mt-1 block w-full rounded-md border-gray-300" required>
                <x-erro-campo :messages="$errors->get('motivo')" />
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Registrar</button>
        </form>
        <div class="bg-white p-6 shadow sm:rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr><th class="text-left py-2">Quando</th><th>Tipo</th><th>Antes</th><th>Depois</th><th>Delta</th><th>Motivo</th><th>Responsável</th></tr></thead>
                <tbody>
                @forelse ($movimentacoes as $mov)
                    <tr>
                        <td class="py-2">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $mov->rotuloTipo() }}</td>
                        <td>{{ $mov->quantidade_anterior }}</td>
                        <td>{{ $mov->quantidade_final }}</td>
                        <td>{{ $mov->delta }}</td>
                        <td>{{ $mov->motivo }}</td>
                        <td>{{ $mov->usuario?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 text-gray-600">Nenhuma movimentação.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $movimentacoes->links() }}</div>
        </div>
    </div>
</x-layout-aplicacao>
