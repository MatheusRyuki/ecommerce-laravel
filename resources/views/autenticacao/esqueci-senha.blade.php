<x-layout-convidado>
    <x-slot name="titulo">Redefinir senha — {{ config('app.name') }}</x-slot>

    <h1 class="text-lg font-semibold text-gray-900 mb-4">Redefinir senha</h1>

    <div class="mb-4 text-sm text-gray-600">
        Informe o e-mail para receber um link e definir uma senha nova.
    </div>

    <!-- Estado da sessão -->
    <x-status-sessao class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- E-mail -->
        <div>
            <x-rotulo-campo for="email" :value="'E-mail'" />
            <x-campo-texto id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-erro-campo :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-botao-principal>
                Enviar link de redefinição
            </x-botao-principal>
        </div>
    </form>
</x-layout-convidado>
