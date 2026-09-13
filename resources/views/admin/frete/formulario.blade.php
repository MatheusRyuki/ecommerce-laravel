<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $faixa ? 'Editar faixa' : 'Nova faixa de frete' }}</h2></x-slot>
    <div class="py-12 max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ $faixa ? route('admin.frete.atualizar', $faixa) : route('admin.frete.salvar') }}" class="bg-white p-6 shadow sm:rounded-lg space-y-4">
            @csrf
            @if ($faixa) @method('PATCH') @endif
            <div><label class="block text-sm" for="cep_inicio">CEP início</label><input id="cep_inicio" name="cep_inicio" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('cep_inicio', $faixa?->cep_inicio) }}"><x-erro-campo :messages="$errors->get('cep_inicio')" /></div>
            <div><label class="block text-sm" for="cep_fim">CEP fim</label><input id="cep_fim" name="cep_fim" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('cep_fim', $faixa?->cep_fim) }}"><x-erro-campo :messages="$errors->get('cep_fim')" /></div>
            <div><label class="block text-sm" for="valor">Valor (BRL)</label><input id="valor" name="valor" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('valor', $faixa?->valor) }}"><x-erro-campo :messages="$errors->get('valor')" /></div>
            <button class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Salvar</button>
        </form>
    </div>
</x-layout-aplicacao>
