<x-layout-aplicacao>
    <x-slot name="cabecalho">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel administrativo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p>
                        {{ __('Você está autenticado como administrador.') }}
                    </p>
                    <p>
                        <span class="font-medium">{{ __('Nome') }}:</span>
                        {{ $admin->name }}
                    </p>
                    <p>
                        <span class="font-medium">{{ __('E-mail') }}:</span>
                        {{ $admin->email }}
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ __('Não há template administrativo no pacote da loja; esta tela usa o layout autenticado do Breeze.') }}
                    </p>
                    <p class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.produtos.listar') }}" class="relative z-10 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Produtos
                        </a>
                        <a id="link-cadastrar-produto" href="{{ route('admin.produtos.criar') }}" class="relative z-10 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Cadastrar produto
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout-aplicacao>
