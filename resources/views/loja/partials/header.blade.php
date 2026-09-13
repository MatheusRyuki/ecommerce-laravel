    <nav class="navbar navbar-expand-lg main_menu">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Freeit" class="img-fluid">
            </a>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">Início</a>
                    </li>
                    @foreach ($categoriasMenu as $categoria)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('loja.categorias.exibir') && optional(request()->route('categoria'))->is($categoria) ? 'active' : '' }}" href="{{ route('loja.categorias.exibir', $categoria) }}">{{ $categoria->nome }}</a>
                        </li>
                    @endforeach
                    @auth
                        <li class="nav-item d-lg-none">
                            <a class="nav-link {{ request()->routeIs('dashboard', 'perfil.*', 'conta.*') ? 'active' : '' }}" href="{{ route('dashboard') }}">Conta</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link {{ request()->routeIs('conta.favoritos') ? 'active' : '' }}" href="{{ route('conta.favoritos') }}">Favoritos</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link {{ request()->routeIs('conta.pedidos.*') ? 'active' : '' }}" href="{{ route('conta.pedidos.listar') }}">Pedidos</a>
                        </li>
                    @else
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="{{ route('login') }}">Entrar</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="{{ route('register') }}">Criar conta</a>
                        </li>
                    @endauth
                </ul>
            </div>

            <div class="d-flex align-items-center cabecalho-loja__acoes">
                @php
                    $itensCarrinho = (int) $quantidadeCarrinho;
                    $rotuloCarrinho = $itensCarrinho === 1
                        ? 'Carrinho, 1 item'
                        : 'Carrinho, '.$itensCarrinho.' itens';
                @endphp
                <a href="{{ route('carrinho') }}" class="cabecalho-loja__carrinho wsus__manu_cart {{ request()->routeIs('carrinho') ? 'ativo' : '' }}" aria-label="{{ $rotuloCarrinho }}">
                    <span class="cabecalho-loja__carrinho-icone">
                        <img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="" class="img-fluid" aria-hidden="true">
                        <b class="cabecalho-loja__carrinho-contador" aria-hidden="true">{{ $itensCarrinho }}</b>
                    </span>
                </a>
                @auth
                    <a href="{{ route('conta.favoritos') }}" class="cabecalho-loja__conta d-none d-lg-inline-flex {{ request()->routeIs('conta.favoritos') ? 'ativo' : '' }}">Favoritos</a>
                    <a href="{{ route('conta.pedidos.listar') }}" class="cabecalho-loja__conta d-none d-lg-inline-flex {{ request()->routeIs('conta.pedidos.*') ? 'ativo' : '' }}">Pedidos</a>
                    <a href="{{ route('dashboard') }}" class="cabecalho-loja__conta d-none d-lg-inline-flex {{ request()->routeIs('dashboard', 'perfil.*') ? 'ativo' : '' }}">Conta</a>
                @else
                    <a href="{{ route('login') }}" class="cabecalho-loja__conta d-none d-lg-inline-flex">Entrar</a>
                    <a href="{{ route('register') }}" class="cabecalho-loja__conta d-none d-lg-inline-flex">Criar conta</a>
                @endauth
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Abrir menu">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </nav>
