                <div class="col-xxl-3 col-md-6 col-lg-4">
                    <article class="wsus__product_item">
                        <a href="{{ route('loja.produtos.exibir', $produto) }}" class="wsus__product_item__link">
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
                                <span class="add_cart">Ver produto</span>
                            </div>
                            @unless ($produto->estaDisponivel())
                                <span class="new">Indisponível</span>
                            @endunless
                            <div class="text">
                                <span class="title">{{ $produto->nome }}</span>
                                <p class="preco-produto">{{ $produto->precoFormatado() }}</p>
                            </div>
                        </a>
                    </article>
                </div>
