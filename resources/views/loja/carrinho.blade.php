@extends('loja.layouts.app')

@section('title', 'Carrinho')

@section('content')
    <section class="wsus__cart mt_170 pb_100">
        <div class="container">
            @if (session('status'))
                @include('loja.partials.alert', ['type' => 'success', 'dismissible' => true, 'message' => session('status')])
            @endif

            @if ($linhas->isEmpty())
                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <p>{{ 'Seu carrinho está vazio.' }}</p>
                        <div class="wsus__cart_summary">
                            <h2>{{ 'Resumo do pedido' }}</h2>
                            <div class="wsus__cart_list_pricing">
                                <h6>{{ 'Total dos produtos' }} <span>{{ $totalProdutosFormatado }}</span></h6>
                            </div>
                        </div>
                        <a href="{{ route('inicio') }}" class="common_btn">{{ 'Continuar comprando' }}</a>
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
                                            <th class="pro_img">{{ 'Item' }}</th>
                                            <th class="pro_name">Nome</th>
                                            <th class="pro_select">{{ 'Quantidade' }}</th>
                                            <th class="pro_tk">{{ 'Preço' }}</th>
                                            <th class="pro_icon">{{ 'Ação' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($linhas as $linha)
                                            @php
                                                $linhaErrorKey = 'cart_items.'.$linha->id;
                                                $quantityValue = old('updating_item') === $linha->id ? old('quantity', $linha->quantidade) : $linha->quantidade;
                                            @endphp
                                            <tr>
                                                <td class="pro_img">
                                                    @include('loja.partials.foto-produto', [
                                                        'url' => $linha->urlCapa(),
                                                        'alt' => $linha->nome().' ('.$linha->cor.')',
                                                    ])
                                                </td>
                                                <td class="pro_name">
                                                    @if ($linha->podeAbrirDetalhes())
                                                        <a href="{{ route('loja.produtos.exibir', $linha->produto) }}">{{ $linha->nome() }}</a>
                                                    @else
                                                        <span>{{ $linha->nome() }}</span>
                                                    @endif
                                                    <p class="mb-0">Cor: {{ $linha->rotuloCor() }}</p>
                                                    @if ($linha->estaIndisponivel())
                                                        @include('loja.partials.alert', ['type' => 'warning', 'message' => 'Indisponível', 'class' => 'mt-2 mb-0'])
                                                    @elseif ($linha->precisaAjuste())
                                                        @include('loja.partials.alert', ['type' => 'warning', 'message' => 'Este item precisa de ajuste', 'class' => 'mt-2 mb-0'])
                                                    @endif
                                                </td>
                                                <td class="pro_select">
                                                    <div class="cart-line-field">
                                                        <span class="cart-line-field__label">{{ 'Quantidade' }}</span>
                                                        <div>
                                                            @if ($linha->podeAlterarQuantidade())
                                                                <form method="POST" action="{{ route('loja.carrinho.itens.atualizar', $linha->id) }}" class="cart-qty-form" id="cart-qty-form-{{ $linha->id }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="updating_item" value="{{ $linha->id }}">
                                                                    <div class="quentity_btn">
                                                                        <button class="btn btn-danger minus" type="button" aria-label="{{ 'Diminuir quantidade' }}"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                                                        <input id="cart-qty-{{ $linha->id }}" type="number" name="quantity" min="1" step="1" required data-saved="{{ $linha->quantidade }}" value="{{ $quantityValue }}" aria-label="{{ 'Quantidade' }}">
                                                                        <button class="btn btn-success plus" type="button" aria-label="{{ 'Aumentar quantidade' }}"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                                                    </div>
                                                                    <button type="submit" class="common_btn mt-2">{{ 'Atualizar' }}</button>
                                                                    <div class="cart-qty-pending mt-2 d-none">
                                                                        @include('loja.partials.alert', ['type' => 'warning', 'message' => 'Quantidade ainda não salva', 'class' => 'mb-0'])
                                                                    </div>
                                                                </form>
                                                            @else
                                                                <div class="quentity_btn">
                                                                    <input type="text" value="{{ $linha->quantidade }}" readonly disabled aria-label="{{ 'Quantidade' }}">
                                                                </div>
                                                            @endif
                                                            @if ($errors->has($linhaErrorKey))
                                                                @include('loja.partials.alert', ['type' => 'danger', 'message' => $errors->first($linhaErrorKey), 'class' => 'mt-2 mb-0'])
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="pro_tk">
                                                    <dl class="cart-line-prices mb-0">
                                                        <div class="cart-line-field">
                                                            <dt class="cart-line-field__label">{{ 'Preço unitário' }}</dt>
                                                            <dd class="mb-0">{{ $linha->precoUnitarioFormatado() ?? '—' }}</dd>
                                                        </div>
                                                        <div class="cart-line-field mt-2">
                                                            <dt class="cart-line-field__label">Subtotal</dt>
                                                            <dd class="mb-0">{{ $linha->subtotalFormatado() ?? '—' }}</dd>
                                                        </div>
                                                    </dl>
                                                </td>
                                                <td class="pro_icon">
                                                    <div class="cart-line-field">
                                                        <span class="cart-line-field__label">{{ 'Ação' }}</span>
                                                        <form method="POST" action="{{ route('loja.carrinho.itens.remover', $linha->id) }}" id="cart-remove-form-{{ $linha->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" aria-label="Remover">
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
                            <h2>{{ 'Resumo do pedido' }}</h2>
                            @if ($totalProdutosFormatado !== null)
                                <div class="wsus__cart_list_pricing">
                                    <h6>{{ 'Total dos produtos' }} <span>{{ $totalProdutosFormatado }}</span></h6>
                                </div>
                            @else
                                @include('loja.partials.alert', ['type' => 'warning', 'message' => 'O total dos produtos só aparece quando todos os itens estão disponíveis.', 'class' => 'mb-0'])
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <ul class="wsus__cart_list_bottom_btn">
                            <li>
                                <a href="{{ route('inicio') }}" class="common_btn cont_shop">{{ 'Continuar comprando' }}</a>
                            </li>
                            <li>
                                <span class="common_btn common_btn_2 pe-none opacity-50" aria-disabled="true">{{ 'Finalizar compra' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
