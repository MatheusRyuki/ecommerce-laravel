<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 min-w-0">
            <div class="flex min-w-0">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('inicio') }}" aria-label="Freeit">
                        <img src="{{ asset('frontend/images/logo.png') }}" alt="" class="block h-9 w-auto">
                    </a>
                </div>

                <div class="hidden space-x-6 min-[1536px]:-my-px min-[1536px]:ms-8 min-[1536px]:flex min-[1536px]:items-stretch">
                    @php
                        $contaAtiva = request()->routeIs('dashboard');
                        $produtosAtivos = request()->routeIs('admin.produtos.*', 'admin.estoque.*');
                        $adminAberto = request()->routeIs('admin.*');
                    @endphp
                    <x-link-navegacao
                        id="nav-conta"
                        :href="route('dashboard')"
                        :active="$contaAtiva"
                        :aria-current="$contaAtiva ? 'page' : false"
                    >
                        Conta
                    </x-link-navegacao>
                    @auth
                        @if (Auth::user()->hasVerifiedEmail())
                            <x-link-navegacao :href="route('conta.favoritos')" :active="request()->routeIs('conta.favoritos')">Favoritos</x-link-navegacao>
                            <x-link-navegacao :href="route('conta.pedidos.listar')" :active="request()->routeIs('conta.pedidos.*')">Meus pedidos</x-link-navegacao>
                            <x-link-navegacao :href="route('conta.enderecos.listar')" :active="request()->routeIs('conta.enderecos.*')">Endereços</x-link-navegacao>
                        @endif
                    @endauth
                    @can('acessar-admin')
                        <div
                            class="relative flex items-center"
                            x-data="{ aberto: false }"
                            @click.outside="aberto = false"
                            @keydown.escape.window="if (aberto) { aberto = false; $refs.adminNav?.focus() }"
                        >
                            <button
                                type="button"
                                x-ref="adminNav"
                                id="nav-administracao"
                                @click="aberto = ! aberto"
                                :aria-expanded="aberto.toString()"
                                aria-haspopup="true"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition ease-in-out duration-150 {{ $adminAberto ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300' }}"
                            >
                                Administração
                            </button>
                            <div
                                x-show="aberto"
                                x-cloak
                                class="absolute left-0 top-full z-50 mt-1 w-56 rounded-md bg-white py-1 ring-1 ring-black ring-opacity-5"
                                style="display: none;"
                            >
                                <x-link-menu id="nav-produtos" :href="route('admin.produtos.listar')" :aria-current="$produtosAtivos ? 'page' : false">Produtos</x-link-menu>
                                <x-link-menu :href="route('admin.categorias.listar')" :aria-current="request()->routeIs('admin.categorias.*') ? 'page' : false">Categorias</x-link-menu>
                                <x-link-menu :href="route('admin.cupons.listar')" :aria-current="request()->routeIs('admin.cupons.*') ? 'page' : false">Cupons</x-link-menu>
                                <x-link-menu :href="route('admin.frete.listar')" :aria-current="request()->routeIs('admin.frete.*') ? 'page' : false">Frete</x-link-menu>
                                <x-link-menu :href="route('admin.pedidos.listar')" :aria-current="request()->routeIs('admin.pedidos.*') ? 'page' : false">Pedidos da loja</x-link-menu>
                                <x-link-menu :href="route('admin.usuarios.listar')" :aria-current="request()->routeIs('admin.usuarios.*') ? 'page' : false">Usuários</x-link-menu>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="hidden min-[1536px]:flex min-[1536px]:items-center min-[1536px]:ms-6 shrink-0">
                <x-menu-suspenso align="right" width="48">
                    <x-slot name="acionador">
                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="conteudo">
                        <x-link-menu :href="route('perfil.editar')">
                            Perfil
                        </x-link-menu>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-link-menu :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Sair
                            </x-link-menu>
                        </form>
                    </x-slot>
                </x-menu-suspenso>
            </div>

            <div class="-me-2 flex items-center min-[1536px]:hidden">
                <button
                    type="button"
                    @click="open = ! open"
                    aria-label="Menu"
                    :aria-expanded="open.toString()"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden min-[1536px]:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @php
                $contaAtiva = request()->routeIs('dashboard');
                $produtosAtivos = request()->routeIs('admin.produtos.*', 'admin.estoque.*');
            @endphp
            <x-link-navegacao-responsivo
                id="nav-conta-movel"
                :href="route('dashboard')"
                :active="$contaAtiva"
                :aria-current="$contaAtiva ? 'page' : false"
            >
                Conta
            </x-link-navegacao-responsivo>
            @auth
                @if (Auth::user()->hasVerifiedEmail())
                    <x-link-navegacao-responsivo :href="route('conta.favoritos')" :active="request()->routeIs('conta.favoritos')">Favoritos</x-link-navegacao-responsivo>
                    <x-link-navegacao-responsivo :href="route('conta.pedidos.listar')" :active="request()->routeIs('conta.pedidos.*')">Meus pedidos</x-link-navegacao-responsivo>
                    <x-link-navegacao-responsivo :href="route('conta.enderecos.listar')" :active="request()->routeIs('conta.enderecos.*')">Endereços</x-link-navegacao-responsivo>
                @endif
            @endauth
            @can('acessar-admin')
                <x-link-navegacao-responsivo
                    id="nav-produtos-movel"
                    :href="route('admin.produtos.listar')"
                    :active="$produtosAtivos"
                    :aria-current="$produtosAtivos ? 'page' : false"
                >
                    Produtos
                </x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.categorias.listar')" :active="request()->routeIs('admin.categorias.*')">Categorias</x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.cupons.listar')" :active="request()->routeIs('admin.cupons.*')">Cupons</x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.frete.listar')" :active="request()->routeIs('admin.frete.*')">Frete</x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.pedidos.listar')" :active="request()->routeIs('admin.pedidos.*')">Pedidos da loja</x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.usuarios.listar')" :active="request()->routeIs('admin.usuarios.*')">Usuários</x-link-navegacao-responsivo>
            @endcan
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-link-navegacao-responsivo :href="route('perfil.editar')">
                    Perfil
                </x-link-navegacao-responsivo>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-link-navegacao-responsivo :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Sair
                    </x-link-navegacao-responsivo>
                </form>
            </div>
        </div>
    </div>
</nav>
