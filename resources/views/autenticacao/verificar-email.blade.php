<x-layout-convidado>
    <div class="mb-4 text-sm text-gray-600">
        Obrigado por se cadastrar. Confirme o e-mail pelo link que enviamos. Se a mensagem não chegou, podemos reenviar.
    </div>

    @if (session('status') == 'link-verificacao-enviado')
        <div class="mb-4 font-medium text-sm text-green-600">
            Enviamos um novo link para o e-mail informado no cadastro.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-botao-principal>
                    Reenviar e-mail de verificação
                </x-botao-principal>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Sair
            </button>
        </form>
    </div>
</x-layout-convidado>
