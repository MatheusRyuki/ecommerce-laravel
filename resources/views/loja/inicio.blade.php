@extends('store.layouts.app')

@section('title', 'Shop')

@section('content')
    <!--============================
        PRODUCT START
    =============================-->
    <section class="wsus__product mt_145 pb_100">
        <div class="container">
            @if ($products->isEmpty())
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <p>{{ __('No products are available in the store yet.') }}</p>
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach ($products as $product)
                        @include('store.partials.product-item', ['product' => $product])
                    @endforeach
                </div>
                @if ($products->hasPages())
                    <div class="wsus__pagination mt_60">
                        {{ $products->links('pagination.store-bootstrap') }}
                    </div>
                @endif
            @endif
        </div>
    </section>
    <!--============================
        PRODUCT END
    =============================-->
@endsection
