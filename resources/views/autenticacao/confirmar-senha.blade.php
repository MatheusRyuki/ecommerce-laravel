<x-layout-convidado>
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
