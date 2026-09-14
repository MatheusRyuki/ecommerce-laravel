<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuários</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
            <div>
                <label for="q" class="block text-xs font-medium text-gray-600">Buscar por nome ou e-mail</label>
                <input id="q" name="q" type="search" value="{{ $q }}" class="mt-1 rounded-md border-gray-300" placeholder="Nome ou e-mail">
            </div>
            <button class="px-3 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Buscar</button>
            @if ($q !== '')<a class="text-sm text-blue-600 self-center" href="{{ route('admin.usuarios.listar') }}">Limpar</a>@endif
        </form>
        <div class="bg-white p-6 shadow sm:rounded-lg overflow-x-auto">
            @if (session('status'))<p class="mb-3 text-sm text-green-700">{{ session('status') }}</p>@endif
            <table class="min-w-full text-sm">
                <thead><tr><th class="text-left py-2">Nome</th><th>E-mail</th><th>Papel</th><th></th></tr></thead>
                <tbody>
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td class="py-2">{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->administrador ? 'Administrador' : 'Cliente' }}</td>
                        <td>
                            @if ($usuario->id !== auth()->id())
                                @if ($usuario->administrador)
                                    <button
                                        type="button"
                                        id="rebaixar-usuario-{{ $usuario->id }}"
                                        class="text-red-600"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('abrir-modal', { nome: 'confirmar-rebaixar-{{ $usuario->id }}', gatilho: $el.id })"
                                    >Remover admin</button>
                                @else
                                    <button
                                        type="button"
                                        id="promover-usuario-{{ $usuario->id }}"
                                        class="text-blue-600"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('abrir-modal', { nome: 'confirmar-promover-{{ $usuario->id }}', gatilho: $el.id })"
                                    >Promover</button>
                                @endif
                            @else
                                <span class="text-gray-500">Você</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $usuarios->links() }}</div>
        </div>
    </div>

    @foreach ($usuarios as $usuario)
        @if ($usuario->id === auth()->id())
            @continue
        @endif
        @if ($usuario->administrador)
            <x-janela-modal name="confirmar-rebaixar-{{ $usuario->id }}" largura-maxima="lg" focavel>
                <form method="POST" action="{{ route('admin.usuarios.rebaixar', $usuario) }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-gray-900">Remover privilégio de administrador</h2>
                    <p class="mt-2 text-sm text-gray-600">Você vai remover o privilégio de administrador de {{ $usuario->name }} ({{ $usuario->email }}).</p>
                    <div class="mt-6 flex justify-end">
                        <x-botao-secundario x-on:click="$dispatch('fechar')">Cancelar</x-botao-secundario>
                        <x-botao-perigo class="ms-3">Remover admin</x-botao-perigo>
                    </div>
                </form>
            </x-janela-modal>
        @else
            <x-janela-modal name="confirmar-promover-{{ $usuario->id }}" largura-maxima="lg" focavel>
                <form method="POST" action="{{ route('admin.usuarios.promover', $usuario) }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-gray-900">Promover a administrador</h2>
                    <p class="mt-2 text-sm text-gray-600">Você vai promover {{ $usuario->name }} ({{ $usuario->email }}) a administrador.</p>
                    <div class="mt-6 flex justify-end">
                        <x-botao-secundario x-on:click="$dispatch('fechar')">Cancelar</x-botao-secundario>
                        <x-botao-principal class="ms-3">Promover</x-botao-principal>
                    </div>
                </form>
            </x-janela-modal>
        @endif
    @endforeach
</x-layout-aplicacao>
