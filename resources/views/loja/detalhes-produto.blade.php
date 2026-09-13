@extends('loja.layouts.app')

@section('title', $produto->nome)

@php
    $galeria = $produto->imagens;
    $sanitizedDescription = app(\App\Support\SanitizadorDescricaoProduto::class)->sanitizar($produto->descricao);
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
                <a href="{{ route('inicio') }}" class="common_btn">{{ 'Voltar à loja' }}</a>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xl-5 wow fadeInLeft">
                    <div class="wsus__product_details_slider_area">
                        @if ($galeria->isEmpty())
                            <div class="wsus__product_details_slide_show_img">
                                @include('loja.partials.foto-produto', ['url' => null, 'alt' => $produto->nome])
                            </div>
                        @else
                            <div class="row slider-forFive">
                                @foreach ($galeria as $image)
                                    <div class="col-xl-12">
                                        <div class="wsus__product_details_slide_show_img">
                                            @include('loja.partials.foto-produto', [
                                                'url' => $image->urlDisponivel(),
                                                'alt' => $produto->nome.' — '.'Imagem'.' '.$loop->iteration,
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($galeria->count() > 1)
                                <div class="wsus__product_details_slider">
                                    <div class="row slider-navFive">
                                        @foreach ($galeria as $image)
                                            <div class="col-xl-2">
                                                <div class="wsus__product_details_slider_img">
                                                    @include('loja.partials.foto-produto', [
                                                        'url' => $image->urlDisponivel(),
                                                        'alt' => $produto->nome.' — '.'Miniatura'.' '.$loop->iteration,
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
                        <h2>{{ $produto->nome }}</h2>
                        <h6>{{ $produto->precoFormatado() }}</h6>
                        <p>{{ $produto->descricao_curta }}</p>
                        <p>
                            @if ($produto->estaDisponivel())
                                {{ 'Em estoque' }}: {{ $produto->quantidade }}
                            @else
                                {{ 'Indisponível' }}
                            @endif
                        </p>

                        @if ($errors->has('carrinho'))
                            @include('loja.partials.alert', ['type' => 'danger', 'message' => $errors->first('carrinho')])
                        @endif
                        @error('cor')
                            @include('loja.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror
                        @error('quantidade')
                            @include('loja.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror
                        @error('produto_id')
                            @include('loja.partials.alert', ['type' => 'danger', 'message' => $message])
                        @enderror

                        @if ($produto->estaDisponivel())
                            <form method="POST" action="{{ route('loja.carrinho.itens.adicionar') }}" id="formulario-adicionar-carrinho">
                                @csrf
                                <input type="hidden" name="produto_id" value="{{ $produto->id }}">

                                @if ($produto->coresExibidas() !== [])
                                    <h6 class="mt_30">{{ 'Cor' }}</h6>
                                    <select class="select_2" name="cor" required>
                                        @if (count($produto->coresExibidas()) > 1)
                                            <option value="">{{ 'Selecione uma cor' }}</option>
                                        @endif
                                        @foreach ($produto->coresExibidas() as $color)
                                            <option value="{{ $color }}" @selected(old('cor', count($produto->coresExibidas()) === 1 ? $color : '') === $color)>{{ \App\Support\CoresProduto::rotulo($color) }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="wsus__product_add_cart">
                                    <div class="wsus__product_quantity">
                                        <button class="minus" type="button"><i class="fas fa-minus"></i></button>
                                        <input type="number" name="quantidade" value="{{ old('quantidade', 1) }}" min="1" step="1" required>
                                        <button class="plus" type="button"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div class="wsus__buy_cart_button">
                                        <button type="submit" class="cart" aria-label="{{ 'Adicionar ao carrinho' }}">
                                            <img src="{{ asset('frontend/images/cart_icon_black.svg') }}" alt="{{ 'Adicionar ao carrinho' }}" class="img-fluid w-100">
                                        </button>
                                        <span class="common_btn pe-none opacity-50" aria-disabled="true">Comprar agora</span>
                                    </div>
                                </div>
                            </form>
                        @else
                            @if ($produto->coresExibidas() !== [])
                                <h6 class="mt_30">{{ 'Cor' }}</h6>
                                <select class="select_2" name="cor" disabled>
                                    @foreach ($produto->coresExibidas() as $color)
                                        <option value="{{ $color }}">{{ \App\Support\CoresProduto::rotulo($color) }}</option>
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
                                    <span class="common_btn pe-none opacity-50" aria-disabled="true">Comprar agora</span>
                                </div>
                            </div>
                        @endif
                        <ul class="details">
                            <li>{{ 'SKU' }}:<span>{{ $produto->sku }}</span></li>
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
