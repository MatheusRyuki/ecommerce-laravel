<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $categoria ? 'Editar categoria' : 'Nova categoria' }}</h2></x-slot>
    <div class="py-12 max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ $categoria ? route('admin.categorias.atualizar', $categoria) : route('admin.categorias.salvar') }}" class="bg-white p-6 shadow sm:rounded-lg space-y-4">
            @csrf
            @if ($categoria) @method('PATCH') @endif
            <div>
                <label class="block text-sm" for="nome">Nome</label>
                <input id="nome" name="nome" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('nome', $categoria?->nome) }}">
                <x-erro-campo :messages="$errors->get('nome')" />
            </div>
            <div>
                <label class="block text-sm" for="slug">Slug</label>
                <input id="slug" name="slug" class="mt-1 block w-full rounded-md border-gray-300" value="{{ old('slug', $categoria?->slug) }}">
                <x-erro-campo :messages="$errors->get('slug')" />
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Salvar</button>
        </form>
    </div>
</x-layout-aplicacao>
