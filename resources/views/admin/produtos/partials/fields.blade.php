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

<div>
    <label for="cores" class="block text-sm font-medium text-gray-700">Cores</label>
    <select id="cores" name="cores[]" multiple size="4" class="{{ $classeCampo }}">
        @foreach ($cores as $cor)
            <option value="{{ $cor }}" @selected(in_array($cor, $coresSelecionadas, true))>{{ \App\Support\CoresProduto::rotulo($cor) }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500">{{ 'Use Ctrl ou Cmd para marcar mais de uma cor.' }}</p>
    <x-erro-campo class="mt-2" :messages="$errors->get('cores')" />
    <x-erro-campo class="mt-2" :messages="$errors->get('cores.*')" />
</div>

<div>
    <label for="descricao_curta" class="block text-sm font-medium text-gray-700">{{ 'Descrição curta' }}</label>
    <input id="descricao_curta" name="descricao_curta" type="text" value="{{ $valorDescricaoCurta }}" maxlength="500" class="{{ $classeCampo }}">
    <x-erro-campo class="mt-2" :messages="$errors->get('descricao_curta')" />
</div>

<div>
    <label for="quantidade" class="block text-sm font-medium text-gray-700">Qtd.</label>
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
