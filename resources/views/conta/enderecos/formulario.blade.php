<x-layout-aplicacao>
    <x-slot name="cabecalho"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $endereco ? 'Editar endereço' : 'Novo endereço' }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ $endereco ? route('conta.enderecos.atualizar', $endereco) : route('conta.enderecos.salvar') }}" class="bg-white p-6 shadow sm:rounded-lg space-y-4">
                @csrf
                @if ($endereco) @method('PATCH') @endif
                @foreach (['destinatario' => 'Destinatário', 'cep' => 'CEP', 'logradouro' => 'Logradouro', 'numero' => 'Número', 'complemento' => 'Complemento', 'bairro' => 'Bairro', 'cidade' => 'Cidade'] as $campo => $rotulo)
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="{{ $campo }}">{{ $rotulo }}</label>
                        <input id="{{ $campo }}" name="{{ $campo }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old($campo, $endereco?->{$campo}) }}">
                        <x-erro-campo :messages="$errors->get($campo)" />
                    </div>
                @endforeach
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="uf">UF</label>
                    <select id="uf" name="uf" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Selecione</option>
                        @foreach (\App\Support\Cep::UFS as $uf)
                            <option value="{{ $uf }}" @selected(old('uf', $endereco?->uf) === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                    <x-erro-campo :messages="$errors->get('uf')" />
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="padrao" value="1" @checked(old('padrao', $endereco?->padrao))> Endereço padrão</label>
                <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase rounded-md">Salvar</button>
            </form>
        </div>
    </div>
</x-layout-aplicacao>
