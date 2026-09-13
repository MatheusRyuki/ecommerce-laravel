    <nav class="navbar navbar-expand-lg main_menu">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Freeit" class="img-fluid">
            </a>

            <div class="d-flex align-items-center gap-2 ms-auto order-lg-3 cabecalho-loja__acoes">
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
                    <a href="{{ route('dashboard') }}" class="cabecalho-loja__conta {{ request()->routeIs('dashboard') ? 'ativo' : '' }}">Conta</a>
                @else
                    <a href="{{ route('login') }}" class="cabecalho-loja__conta">Entrar</a>
                    <a href="{{ route('register') }}" class="cabecalho-loja__conta">Criar conta</a>
                @endauth
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Abrir menu">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse order-lg-2" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">Início</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
