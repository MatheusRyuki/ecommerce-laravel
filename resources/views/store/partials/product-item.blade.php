                <div class="col-xxl-3 col-md-6 col-lg-4">
                    <div class="wsus__product_item">
                        <div class="img">
                            @if ($product->coverUrl())
                                @include('store.partials.product-photo', [
                                    'url' => $product->coverUrl(),
                                    'alt' => $product->name,
                                ])
                            @else
                                @include('store.partials.product-photo', [
                                    'url' => null,
                                    'alt' => $product->name,
                                ])
                            @endif
                            <a href="{{ route('store.products.show', $product) }}" class="add_cart">
                                <span><img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="cart" class="img-fluid w-100"></span>
                                {{ __('Add To Cart') }}
                            </a>
                        </div>
                        @unless ($product->isAvailable())
                            <span class="new">{{ __('Unavailable') }}</span>
                        @endunless
                        <div class="text">
                            <a href="{{ route('store.products.show', $product) }}" class="title">{{ $product->name }}</a>
                            <h4>{{ $product->formattedPrice() }}</h4>
                        </div>
                    </div>
                </div>
