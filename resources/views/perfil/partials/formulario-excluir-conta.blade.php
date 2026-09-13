<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Quando a conta for excluída, os dados associados saem de forma permanente. Esta ação não pode ser desfeita.
        </p>
    </header>

    <x-botao-perigo
        x-data=""
        x-on:click.prevent="$dispatch('abrir-modal', 'confirmar-exclusao-usuario')"
    >Excluir conta</x-botao-perigo>

    <x-janela-modal name="confirmar-exclusao-usuario" :show="$errors->exclusao_usuario->isNotEmpty()" focavel>
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
                <x-rotulo-campo for="password" value="Senha" class="sr-only" />

                <x-campo-texto
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                />

                <x-erro-campo :messages="$errors->exclusao_usuario->get('password')" class="mt-2" />
                <x-erro-campo :messages="$errors->exclusao_usuario->get('exclusao_usuario')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-botao-secundario x-on:click="$dispatch('fechar')">
                    Cancelar
                </x-botao-secundario>

                <x-botao-perigo class="ms-3">
                    Excluir conta
                </x-botao-perigo>
            </div>
        </form>
    </x-janela-modal>
</section>
