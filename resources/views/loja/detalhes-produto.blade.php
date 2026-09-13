@extends('store.layouts.app')

@section('title', $product->name)

@php
    $gallery = $product->images;
    $sanitizedDescription = app(\App\Support\ProductDescriptionSanitizer::class)->sanitize($product->description);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/select2.min.css') }}">
@endpush

@section('content')
    <!--============================
        PRODUCT DETAILS START
    =============================-->
    <section class="wsus__product_details mt_170 mb_100">
        <div class="container">
            <div class="mb-4">
                <a href="{{ route('home') }}" class="common_btn">{{ __('Back to shop') }}</a>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xl-5 wow fadeInLeft">
                    <div class="wsus__product_details_slider_area">
                        @if ($gallery->isEmpty())
                            <div class="wsus__product_details_slide_show_img">
                                @include('store.partials.product-photo', ['url' => null, 'alt' => $product->name])
                            </div>
                        @else
                            <div class="row slider-forFive">
                                @foreach ($gallery as $image)
                                    <div class="col-xl-12">
                                        <div class="wsus__product_details_slide_show_img">
                                            @include('store.partials.product-photo', [
                                                'url' => $image->availableUrl(),
                                                'alt' => $product->name.' — '.__('Image').' '.$loop->iteration,
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($gallery->count() > 1)
                                <div class="wsus__product_details_slider">
                                    <div class="row slider-navFive">
                                        @foreach ($gallery as $image)
                                            <div class="col-xl-2">
                                                <div class="wsus__product_details_slider_img">
                                                    @include('store.partials.product-photo', [
                                                        'url' => $image->availableUrl(),
                                                        'alt' => $product->name.' — '.__('Thumbnail').' '.$loop->iteration,
                                                    ])
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="col-lg-6 col-xl-7 wow fadeInRight">
                    <div class="wsus__product_summary">
                        <h2>{{ $product->name }}</h2>
                        <h6>{{ $product->formattedPrice() }}</h6>
                        <p>{{ $product->short_description }}</p>
                        <p>
                            @if ($product->isAvailable())
                                {{ __('In stock') }}: {{ $product->qty }}
                            @else
                                {{ __('Unavailable') }}
                            @endif
                        </p>

                        @if ($errors->has('cart'))
                            @include('store.partials.alert', ['type' => 'danger', 'message' => $errors->first('cart')])
                        @endif
                        @error('color')
                            @include('store.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror
                        @error('quantity')
                            @include('store.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror
                        @error('product_id')
                            @include('store.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror

                        @if ($product->isAvailable())
                            <form method="POST" action="{{ route('store.cart.items.store') }}" id="add-to-cart-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">

                                @if ($product->displayColors() !== [])
                                    <h6 class="mt_30">{{ __('Color') }}</h6>
                                    <select class="select_2" name="color" required>
                                        @if (count($product->displayColors()) > 1)
                                            <option value="">{{ __('Select a color') }}</option>
                                        @endif
                                        @foreach ($product->displayColors() as $color)
                                            <option value="{{ $color }}" @selected(old('color', count($product->displayColors()) === 1 ? $color : '') === $color)>{{ $color }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="wsus__product_add_cart">
                                    <div class="wsus__product_quantity">
                                        <button class="minus" type="button"><i class="fas fa-minus"></i></button>
                                        <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" step="1" required>
                                        <button class="plus" type="button"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div class="wsus__buy_cart_button">
                                        <button type="submit" class="cart" aria-label="{{ __('Add To Cart') }}">
                                            <img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="{{ __('Add To Cart') }}" class="img-fluid w-100">
                                        </button>
                                        <span class="common_btn pe-none opacity-50" aria-disabled="true">{{ __('Buy Now') }}</span>
                                    </div>
                                </div>
                            </form>
                        @else
                            @if ($product->displayColors() !== [])
                                <h6 class="mt_30">{{ __('Color') }}</h6>
                                <select class="select_2" name="color" disabled>
                                    @foreach ($product->displayColors() as $color)
                                        <option value="{{ $color }}">{{ $color }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <div class="wsus__product_add_cart">
                                <div class="wsus__product_quantity">
                                    <button class="minus" type="button" disabled><i class="fas fa-minus"></i></button>
                                    <input type="text" value="1" disabled>
                                    <button class="plus" type="button" disabled><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="wsus__buy_cart_button">
                                    <span class="cart pe-none opacity-50" aria-disabled="true"><img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="cart"
                                            class="img-fluid w-100"></span>
                                    <span class="common_btn pe-none opacity-50" aria-disabled="true">{{ __('Buy Now') }}</span>
                                </div>
                            </div>
                        @endif
                        <ul class="details">
                            <li>{{ __('SKU') }}:<span>{{ $product->sku }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="wsus__product_details_menu_contant">
                        <div class="wsus__product_description wow fadeInUp">
                            {!! $sanitizedDescription !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
       PRODUCT DETAILS END
    =============================-->
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/slick.min.js') }}"></script>
    <script src="{{ asset('frontend/js/select2.min.js') }}"></script>
@endpush
