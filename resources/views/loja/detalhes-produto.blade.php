@extends('loja.layouts.aplicacao')

@section('title', $produto->nome)

@php
    $galeria = $produto->imagens;
    $sanitizedDescription = app(\App\Support\SanitizadorDescricaoProduto::class)->sanitizar($produto->descricao);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/slick.css') }}">
@endpush

@section('content')
    <!--============================
        PRODUCT DETAILS START
    =============================-->
    <section class="wsus__product_details loja-conteudo mb_100">
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
                        <h1>{{ $produto->nome }}</h1>
                        <p class="preco-detalhe">{{ $produto->precoFormatado() }}</p>
                        <p>{{ $produto->descricao_curta }}</p>
                        <p>
                            @if ($produto->estaDisponivel())
                                {{ 'Em estoque' }}: {{ $produto->quantidade }}
                            @else
                                {{ 'Indisponível' }}
                            @endif
                        </p>
                        @if ($produto->categoria)
                            <p>Categoria: <a href="{{ route('loja.categorias.exibir', $produto->categoria) }}">{{ $produto->categoria->nome }}</a></p>
                        @endif

                        @if (session('status'))
                            @include('loja.partials.alert', ['type' => 'sucesso', 'dismissible' => true, 'message' => session('status')])
                        @endif

                        @auth
                            @if ($favorito)
                                <form method="POST" action="{{ route('conta.favoritos.remover', $produto) }}" class="mb-3">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-secondary">Remover dos favoritos</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('conta.favoritos.adicionar', $produto) }}" class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary">Salvar nos favoritos</button>
                                </form>
                            @endif
                        @else
                            <p class="mb-3"><a href="{{ route('conta.favoritos.convidado', $produto) }}">Entre</a> para salvar este produto nos favoritos.</p>
                        @endauth

                        @if ($errors->has('carrinho'))
                            @include('loja.partials.alert', ['type' => 'erro', 'message' => $errors->first('carrinho')])
                        @endif
                        @error('cor')
                            @include('loja.partials.alert', ['type' => 'erro', 'message' => $message])
                        @enderror
                        @error('quantidade')
                            @include('loja.partials.alert', ['type' => 'erro', 'message' => $message])
                        @enderror
                        @error('produto_id')
                            @include('loja.partials.alert', ['type' => 'erro', 'message' => $message])
                        @enderror

                        @if ($produto->estaDisponivel())
                            <form method="POST" action="{{ route('loja.carrinho.itens.adicionar') }}" id="formulario-adicionar-carrinho">
                                @csrf
                                <input type="hidden" name="produto_id" value="{{ $produto->id }}">

                                @if ($produto->coresExibidas() !== [])
                                    <div class="mt_30">
                                        <label class="form-label" for="cor-produto">{{ 'Cor' }}</label>
                                        <select id="cor-produto" class="form-select" name="cor" required>
                                            @if (count($produto->coresExibidas()) > 1)
                                                <option value="">{{ 'Selecione uma cor' }}</option>
                                            @endif
                                            @foreach ($produto->coresExibidas() as $color)
                                                <option value="{{ $color }}" @selected(old('cor', count($produto->coresExibidas()) === 1 ? $color : '') === $color)>{{ \App\Support\CoresProduto::rotulo($color) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="wsus__product_add_cart">
                                    <div class="wsus__product_quantity">
                                        <button class="minus" type="button" aria-label="{{ 'Diminuir quantidade' }}"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                        <input type="number" name="quantidade" value="{{ old('quantidade', 1) }}" min="1" step="1" required aria-label="{{ 'Quantidade' }}">
                                        <button class="plus" type="button" aria-label="{{ 'Aumentar quantidade' }}"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                    </div>
                                    <div class="wsus__buy_cart_button">
                                        <button type="submit" class="common_btn">{{ 'Adicionar ao carrinho' }}</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            @if ($produto->coresExibidas() !== [])
                                <div class="mt_30">
                                    <label class="form-label" for="cor-produto-indisponivel">{{ 'Cor' }}</label>
                                    <select id="cor-produto-indisponivel" class="form-select" name="cor" disabled>
                                        @foreach ($produto->coresExibidas() as $color)
                                            <option value="{{ $color }}">{{ \App\Support\CoresProduto::rotulo($color) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
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
@endpush
