<x-layout-convidado>
    <x-slot name="titulo">Confirmar senha — {{ config('app.name') }}</x-slot>

    <h1 class="text-lg font-semibold text-gray-900 mb-4">Confirmar senha</h1>

    <div class="mb-4 text-sm text-gray-600">
        Área protegida. Confirme a senha para continuar.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Senha -->
        <div>
            <x-rotulo-campo for="password" :value="'Senha'" />

            <x-campo-texto id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-erro-campo :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-botao-principal>
                Confirmar
            </x-botao-principal>
        </div>
    </form>
</x-layout-convidado>
