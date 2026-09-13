<x-layout-convidado>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Token de redefinição -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- E-mail -->
        <div>
            <x-rotulo-campo for="email" :value="'E-mail'" />
            <x-campo-texto id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-erro-campo :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-4">
            <x-rotulo-campo for="password" :value="'Senha'" />
            <x-campo-texto id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-erro-campo :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar senha -->
        <div class="mt-4">
            <x-rotulo-campo for="password_confirmation" :value="'Confirmar senha'" />

            <x-campo-texto id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-erro-campo :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-botao-principal>
                Redefinir senha
            </x-botao-principal>
        </div>
    </form>
</x-layout-convidado>
