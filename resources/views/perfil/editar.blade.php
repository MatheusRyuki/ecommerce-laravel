<x-layout-aplicacao>
    <x-slot name="cabecalho">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Perfil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('perfil.partials.formulario-dados')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('perfil.partials.formulario-senha')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('perfil.partials.formulario-excluir-conta')
                </div>
            </div>
        </div>
    </div>
</x-layout-aplicacao>
