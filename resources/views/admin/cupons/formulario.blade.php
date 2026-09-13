<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $cupom ? 'Editar cupom' : 'Novo cupom' }}</h2></x-slot>
    <div class="py-12 max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ $cupom ? route('admin.cupons.atualizar', $cupom) : route('admin.cupons.salvar') }}" class="bg-white p-6 shadow sm:rounded-lg space-y-4">
            @csrf
            @if ($cupom) @method('PATCH') @endif
            <div><label class="block text-sm" for="codigo">Código</label><input id="codigo" name="codigo" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('codigo', $cupom?->codigo) }}"><x-erro-campo :messages="$errors->get('codigo')" /></div>
            <div>
                <label class="block text-sm" for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="fixo" @selected(old('tipo', $cupom?->tipo) === 'fixo')>Valor fixo (BRL)</option>
                    <option value="percentual" @selected(old('tipo', $cupom?->tipo) === 'percentual')>Percentual</option>
                </select>
            </div>
            <div><label class="block text-sm" for="valor">Valor</label><input id="valor" name="valor" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('valor', $cupom?->valor) }}"><x-erro-campo :messages="$errors->get('valor')" /></div>
            <div><label class="block text-sm" for="valido_de">Válido de</label><input id="valido_de" name="valido_de" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('valido_de', optional($cupom?->valido_de)?->format('Y-m-d\TH:i')) }}"></div>
            <div><label class="block text-sm" for="valido_ate">Válido até</label><input id="valido_ate" name="valido_ate" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('valido_ate', optional($cupom?->valido_ate)?->format('Y-m-d\TH:i')) }}"></div>
            <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $cupom?->ativo ?? true))> Ativo</label>
            <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="uso_unico" value="1" @checked(old('uso_unico', $cupom?->uso_unico))> Uso único global</label>
            <button class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Salvar</button>
        </form>
    </div>
</x-layout-aplicacao>
