<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Quando a conta for excluída, os dados associados saem de forma permanente. Baixe o que quiser guardar antes de continuar.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('abrir-modal', 'confirmar-exclusao-usuario')"
    >Excluir conta</x-danger-button>

    <x-modal name="confirmar-exclusao-usuario" :show="$errors->exclusao_usuario->isNotEmpty()" focusable>
        <form method="post" action="{{ route('perfil.excluir') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Tem certeza de que deseja excluir a conta?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                A exclusão é permanente. Digite a senha para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Senha" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                />

                <x-input-error :messages="$errors->exclusao_usuario->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('fechar')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
