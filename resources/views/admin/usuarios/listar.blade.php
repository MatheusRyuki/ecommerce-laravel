<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuários</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <form method="GET" class="mb-4 flex gap-2">
            <input name="q" value="{{ $q }}" class="rounded-md border-gray-300" placeholder="Nome ou e-mail">
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
                                    <form method="POST" action="{{ route('admin.usuarios.rebaixar', $usuario) }}">@csrf<button class="text-red-600">Remover admin</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.usuarios.promover', $usuario) }}">@csrf<button class="text-blue-600">Promover</button></form>
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
</x-layout-aplicacao>
