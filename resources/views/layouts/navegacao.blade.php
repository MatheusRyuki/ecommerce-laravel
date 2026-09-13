<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Menu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('inicio') }}">
                        <img src="{{ asset('frontend/images/logo.png') }}" alt="Freeit" class="block h-9 w-auto">
                    </a>
                </div>

                <!-- Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-link-navegacao :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Conta
                    </x-link-navegacao>
                    @can('acessar-admin')
                        <x-link-navegacao :href="route('admin.painel')" :active="request()->routeIs('admin.*')">
                            {{ __('Administração') }}
                        </x-link-navegacao>
                        <x-link-navegacao id="nav-produtos" :href="route('admin.produtos.listar')" :active="request()->routeIs('admin.produtos.*')">
                            Produtos
                        </x-link-navegacao>
                    @endcan
                </div>
            </div>

            <!-- Conta -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-menu-suspenso align="right" width="48">
                    <x-slot name="acionador">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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

                        <!-- Encerrar sessão -->
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

            <!-- Menu móvel -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu responsivo -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-link-navegacao-responsivo :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Conta
            </x-link-navegacao-responsivo>
            @can('acessar-admin')
                <x-link-navegacao-responsivo :href="route('admin.painel')" :active="request()->routeIs('admin.painel')">
                    {{ __('Administração') }}
                </x-link-navegacao-responsivo>
                <x-link-navegacao-responsivo :href="route('admin.produtos.listar')" :active="request()->routeIs('admin.produtos.*')">
                    Produtos
                </x-link-navegacao-responsivo>
            @endcan
        </div>

        <!-- Opções da conta -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-link-navegacao-responsivo :href="route('perfil.editar')">
                    Perfil
                </x-link-navegacao-responsivo>

                <!-- Encerrar sessão -->
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
