<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Atualizar senha
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Use uma senha longa e difícil de adivinhar.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-rotulo-campo for="senha-atual" :value="'Senha atual'" />
            <x-campo-texto id="senha-atual" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-erro-campo :messages="$errors->atualizacao_senha->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-rotulo-campo for="nova-senha" :value="'Nova senha'" />
            <x-campo-texto id="nova-senha" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-erro-campo :messages="$errors->atualizacao_senha->get('password')" class="mt-2" />
        </div>

        <div>
            <x-rotulo-campo for="confirmacao-nova-senha" :value="'Confirmar senha'" />
            <x-campo-texto id="confirmacao-nova-senha" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-erro-campo :messages="$errors->atualizacao_senha->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-botao-principal>Salvar</x-botao-principal>

            @if (session('status') === 'senha-atualizada')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
