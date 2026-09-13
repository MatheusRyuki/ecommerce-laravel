    <!--============================
        MENU START
    =============================-->
    <nav class="navbar navbar-expand-lg main_menu">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Freeit" class="img-fluid">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav m-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') || request()->routeIs('store.products.show') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Portfolio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') || request()->routeIs('store.products.show') ? 'active' : '' }}" href="{{ route('home') }}">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Blog</a>
                    </li>
                </ul>
                <ul class="right_menu d-flex flex-wrap align-items-center">
                    <li>
                        <a href="{{ route('cart') }}" class="wsus__manu_cart icon">
                            <span>
                                <img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="cart" class="img-fluid">
                                <b>{{ $cartQuantity }}</b>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="common_btn">Let’s Talk</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!--============================
        MENU END
    =============================-->
