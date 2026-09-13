    <!--============================
        MENU START
    =============================-->
    <nav class="navbar navbar-expand-lg main_menu">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Freeit" class="img-fluid">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Abrir menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav m-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inicio') || request()->routeIs('loja.produtos.exibir') ? 'active' : '' }}" href="{{ route('inicio') }}">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Serviços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Portfólio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inicio') || request()->routeIs('loja.produtos.exibir') ? 'active' : '' }}" href="{{ route('inicio') }}">Loja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Blog</a>
                    </li>
                </ul>
                <ul class="right_menu d-flex flex-wrap align-items-center">
                    <li>
                        <a href="{{ route('carrinho') }}" class="wsus__manu_cart icon">
                            <span>
                                <img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="Carrinho" class="img-fluid">
                                <b>{{ $quantidadeCarrinho }}</b>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="common_btn">Fale conosco</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!--============================
        MENU END
    =============================-->
