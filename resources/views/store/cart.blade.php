@extends('store.layouts.app')

@section('title', 'Cart')

@section('content')
    <section class="wsus__cart mt_170 pb_100">
        <div class="container">
            @if (session('status'))
                @include('store.partials.alert', ['type' => 'success', 'dismissible' => true, 'message' => session('status')])
            @endif

            @if ($lines->isEmpty())
                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <p>{{ __('Your cart is empty.') }}</p>
                        <div class="wsus__cart_summary">
                            <h2>{{ __('Order summary') }}</h2>
                            <div class="wsus__cart_list_pricing">
                                <h6>{{ __('Product total') }} <span>{{ $formattedProductTotal }}</span></h6>
                            </div>
                        </div>
                        <a href="{{ route('home') }}" class="common_btn">{{ __('Continue Shopping') }}</a>
                    </div>
                </div>
            @else
                <div class="row justify-content-center">
                    <div class="col-xl-10 wow fadeInUp">
                        <div class="wsus__cart_list">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="pro_img">{{ __('Item') }}</th>
                                            <th class="pro_name">{{ __('Name') }}</th>
                                            <th class="pro_select">{{ __('Quantity') }}</th>
                                            <th class="pro_tk">{{ __('Price') }}</th>
                                            <th class="pro_icon">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lines as $line)
                                            @php
                                                $lineErrorKey = 'cart_items.'.$line->id;
                                                $quantityValue = old('updating_item') === $line->id ? old('quantity', $line->quantity) : $line->quantity;
                                            @endphp
                                            <tr>
                                                <td class="pro_img">
                                                    @include('store.partials.product-photo', [
                                                        'url' => $line->coverUrl(),
                                                        'alt' => $line->name().' ('.$line->color.')',
                                                    ])
                                                </td>
                                                <td class="pro_name">
                                                    @if ($line->canOpenDetails())
                                                        <a href="{{ route('store.products.show', $line->product) }}">{{ $line->name() }}</a>
                                                    @else
                                                        <span>{{ $line->name() }}</span>
                                                    @endif
                                                    <p class="mb-0">{{ __('Color') }}: {{ $line->color }}</p>
                                                    @if ($line->isUnavailable())
                                                        @include('store.partials.alert', ['type' => 'warning', 'message' => __('Unavailable'), 'class' => 'mt-2 mb-0'])
                                                    @elseif ($line->needsAdjustment())
                                                        @include('store.partials.alert', ['type' => 'warning', 'message' => __('This item needs adjustment'), 'class' => 'mt-2 mb-0'])
                                                    @endif
                                                </td>
                                                <td class="pro_select">
                                                    <div class="cart-line-field">
                                                        <span class="cart-line-field__label">{{ __('Quantity') }}</span>
                                                        <div>
                                                            @if ($line->canChangeQuantity())
                                                                <form method="POST" action="{{ route('store.cart.items.update', $line->id) }}" class="cart-qty-form" id="cart-qty-form-{{ $line->id }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="updating_item" value="{{ $line->id }}">
                                                                    <div class="quentity_btn">
                                                                        <button class="btn btn-danger minus" type="button" aria-label="{{ __('Decrease quantity') }}"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                                                        <input id="cart-qty-{{ $line->id }}" type="number" name="quantity" min="1" step="1" required data-saved="{{ $line->quantity }}" value="{{ $quantityValue }}" aria-label="{{ __('Quantity') }}">
                                                                        <button class="btn btn-success plus" type="button" aria-label="{{ __('Increase quantity') }}"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                                                    </div>
                                                                    <button type="submit" class="common_btn mt-2">{{ __('Update') }}</button>
                                                                    <div class="cart-qty-pending mt-2 d-none">
                                                                        @include('store.partials.alert', ['type' => 'warning', 'message' => __('Unsaved quantity'), 'class' => 'mb-0'])
                                                                    </div>
                                                                </form>
                                                            @else
                                                                <div class="quentity_btn">
                                                                    <input type="text" value="{{ $line->quantity }}" readonly disabled aria-label="{{ __('Quantity') }}">
                                                                </div>
                                                            @endif
                                                            @if ($errors->has($lineErrorKey))
                                                                @include('store.partials.alert', ['type' => 'danger', 'message' => $errors->first($lineErrorKey), 'class' => 'mt-2 mb-0'])
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="pro_tk">
                                                    <dl class="cart-line-prices mb-0">
                                                        <div class="cart-line-field">
                                                            <dt class="cart-line-field__label">{{ __('Unit price') }}</dt>
                                                            <dd class="mb-0">{{ $line->formattedUnitPrice() ?? '—' }}</dd>
                                                        </div>
                                                        <div class="cart-line-field mt-2">
                                                            <dt class="cart-line-field__label">{{ __('Subtotal') }}</dt>
                                                            <dd class="mb-0">{{ $line->formattedSubtotal() ?? '—' }}</dd>
                                                        </div>
                                                    </dl>
                                                </td>
                                                <td class="pro_icon">
                                                    <div class="cart-line-field">
                                                        <span class="cart-line-field__label">{{ __('Action') }}</span>
                                                        <form method="POST" action="{{ route('store.cart.items.destroy', $line->id) }}" id="cart-remove-form-{{ $line->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" aria-label="{{ __('Remove') }}">
                                                                <i class="fas fa-times" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <div class="wsus__cart_summary">
                            <h2>{{ __('Order summary') }}</h2>
                            @if ($formattedProductTotal !== null)
                                <div class="wsus__cart_list_pricing">
                                    <h6>{{ __('Product total') }} <span>{{ $formattedProductTotal }}</span></h6>
                                </div>
                            @else
                                @include('store.partials.alert', ['type' => 'warning', 'message' => __('The product total cannot be calculated until every item is available.'), 'class' => 'mb-0'])
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <ul class="wsus__cart_list_bottom_btn">
                            <li>
                                <a href="{{ route('home') }}" class="common_btn cont_shop">{{ __('Continue Shopping') }}</a>
                            </li>
                            <li>
                                <span class="common_btn common_btn_2 pe-none opacity-50" aria-disabled="true">{{ __('Checkout') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
