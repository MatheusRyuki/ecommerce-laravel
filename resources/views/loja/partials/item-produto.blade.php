                <div class="col-xxl-3 col-md-6 col-lg-4">
                    <div class="wsus__product_item">
                        <div class="img">
                            @if ($produto->urlCapa())
                                @include('loja.partials.foto-produto', [
                                    'url' => $produto->urlCapa(),
                                    'alt' => $produto->nome,
                                ])
                            @else
                                @include('loja.partials.foto-produto', [
                                    'url' => null,
                                    'alt' => $produto->nome,
                                ])
                            @endif
                            <a href="{{ route('loja.produtos.exibir', $produto) }}" class="add_cart">
                                <span><img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="" class="img-fluid w-100"></span>
                                Adicionar ao carrinho
                            </a>
                        </div>
                        @unless ($produto->estaDisponivel())
                            <span class="new">Indisponível</span>
                        @endunless
                        <div class="text">
                            <a href="{{ route('loja.produtos.exibir', $produto) }}" class="title">{{ $produto->nome }}</a>
                            <h4>{{ $produto->precoFormatado() }}</h4>
                        </div>
                    </div>
                </div>
