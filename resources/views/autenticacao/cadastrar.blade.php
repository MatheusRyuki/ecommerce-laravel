<x-layout-convidado>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Nome -->
        <div>
            <x-rotulo-campo for="name" :value="'Nome'" />
            <x-campo-texto id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-erro-campo :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- E-mail -->
        <div class="mt-4">
            <x-rotulo-campo for="email" :value="'E-mail'" />
            <x-campo-texto id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-erro-campo :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-4">
            <x-rotulo-campo for="password" :value="'Senha'" />

            <x-campo-texto id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

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
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ 'Já tem conta?' }}
            </a>

            <x-botao-principal class="ms-4">
                {{ 'Cadastrar' }}
            </x-botao-principal>
        </div>
    </form>
</x-layout-convidado>
