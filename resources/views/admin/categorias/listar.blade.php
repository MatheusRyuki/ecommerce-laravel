<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Categorias</h2></x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <a href="{{ route('admin.categorias.criar') }}" class="inline-flex mb-4 items-center px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Cadastrar categoria</a>
        <div class="bg-white p-6 shadow sm:rounded-lg">
            @if (session('status'))<p class="mb-3 text-sm text-green-700">{{ session('status') }}</p>@endif
            @if ($categorias->isEmpty())
                <p class="text-sm text-gray-600">Nenhuma categoria.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead><tr><th class="text-left py-2">Nome</th><th class="text-left">Slug</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($categorias as $categoria)
                        <tr>
                            <td class="py-2">{{ $categoria->nome }}</td>
                            <td>{{ $categoria->slug }}</td>
                            <td class="space-x-3">
                                <a class="text-blue-600" href="{{ route('admin.categorias.editar', $categoria) }}">Editar</a>
                                <form class="inline" method="POST" action="{{ route('admin.categorias.excluir', $categoria) }}">@csrf @method('DELETE')<button class="text-red-600">Excluir</button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $categorias->links() }}</div>
            @endif
        </div>
    </div>
</x-layout-aplicacao>
