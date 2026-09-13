@php
    $classeCampo = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

<div>
    <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
    <input id="nome" name="nome" type="text" value="{{ $valorNome }}" maxlength="255" autocomplete="off" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('nome')" />
</div>

<div>
    <label for="preco" class="block text-sm font-medium text-gray-700">Preço (BRL)</label>
    <input id="preco" name="preco" type="number" value="{{ $valorPreco }}" inputmode="decimal" step="0.01" min="0" max="99999999.99" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('preco')" />
</div>

<fieldset class="space-y-2">
    <legend class="block text-sm font-medium text-gray-700">Cores</legend>
    <div class="flex flex-wrap gap-3">
        @foreach ($cores as $cor)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="cores[]" value="{{ $cor }}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                    @checked(in_array($cor, $coresSelecionadas, true))>
                <span>{{ \App\Support\CoresProduto::rotulo($cor) }}</span>
            </label>
        @endforeach
    </div>
    <x-erro-campo class="mt-2" :messages="$errors->get('cores')" />
    <x-erro-campo class="mt-2" :messages="$errors->get('cores.*')" />
</fieldset>

<div>
    <label for="descricao_curta" class="block text-sm font-medium text-gray-700">{{ 'Descrição curta' }}</label>
    <input id="descricao_curta" name="descricao_curta" type="text" value="{{ $valorDescricaoCurta }}" maxlength="500" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('descricao_curta')" />
</div>

<div>
    <label for="quantidade" class="block text-sm font-medium text-gray-700">Estoque</label>
    <input id="quantidade" name="quantidade" type="number" value="{{ $valorQuantidade }}" inputmode="numeric" step="1" min="0" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('quantidade')" />
</div>

<div>
    <label for="sku" class="block text-sm font-medium text-gray-700">{{ 'SKU' }}</label>
    <input id="sku" name="sku" type="text" value="{{ $sku }}" maxlength="100" inputmode="text" autocomplete="off" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('sku')" />
</div>

<div>
    <label for="descricao" class="block text-sm font-medium text-gray-700">{{ 'Descrição' }}</label>
    <textarea id="descricao" name="descricao" class="sr-only" rows="1" tabindex="-1">{{ $valorDescricao }}</textarea>
    <div id="editor-descricao" class="mt-1 bg-white"></div>
    <x-erro-campo class="mt-2" :messages="$errors->get('descricao')" />
</div>
