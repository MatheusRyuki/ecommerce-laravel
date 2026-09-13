@extends('loja.layouts.aplicacao')

@section('title', 'Carrinho')

@section('content')
    <section class="wsus__cart loja-conteudo pagina-carrinho">
        <div class="container">
            <div class="pagina-carrinho__corpo">
                @if (session('status'))
                    @include('loja.partials.alert', ['type' => 'sucesso', 'dismissible' => true, 'message' => session('status')])
                @endif

                @if ($linhas->isEmpty())
                    <p class="pagina-carrinho__vazio">{{ 'Seu carrinho está vazio.' }}</p>
                @else
                    <div class="wsus__cart_list">
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
                                        $chaveErroLinha = 'itens_carrinho.'.$linha->id;
                                        $valorQuantidade = old('item_em_atualizacao') === $linha->id ? old('quantidade', $linha->quantidade) : $linha->quantidade;
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
                                                @include('loja.partials.alert', ['type' => 'aviso', 'message' => 'Indisponível', 'class' => 'mt-2 mb-0'])
                                            @elseif ($linha->precisaAjuste())
                                                @include('loja.partials.alert', ['type' => 'aviso', 'message' => 'Este item precisa de ajuste', 'class' => 'mt-2 mb-0'])
                                            @endif
                                        </td>
                                        <td class="pro_select">
                                            <div class="campo-linha-carrinho">
                                                <span class="campo-linha-carrinho__rotulo">{{ 'Quantidade' }}</span>
                                                <div class="campo-linha-carrinho__controle">
                                                    @if ($linha->podeAlterarQuantidade())
                                                        <form method="POST" action="{{ route('loja.carrinho.itens.atualizar', $linha->id) }}" class="formulario-qtd-carrinho" id="formulario-qtd-carrinho-{{ $linha->id }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="item_em_atualizacao" value="{{ $linha->id }}">
                                                            <div class="grupo-qtd-carrinho">
                                                                <div class="quentity_btn">
                                                                    <button class="btn btn-danger minus" type="button" aria-label="{{ 'Diminuir quantidade' }}"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                                                    <input id="qtd-carrinho-{{ $linha->id }}" type="number" name="quantidade" min="1" step="1" required data-salvo="{{ $linha->quantidade }}" value="{{ $valorQuantidade }}" aria-label="{{ 'Quantidade' }}">
                                                                    <button class="btn btn-success plus" type="button" aria-label="{{ 'Aumentar quantidade' }}"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                                                </div>
                                                                <button type="submit" class="common_btn">{{ 'Atualizar' }}</button>
                                                            </div>
                                                            <p class="qtd-carrinho-pendente aviso-qtd-pendente d-none" role="status">{{ 'Quantidade ainda não salva' }}</p>
                                                            @if ($errors->has($chaveErroLinha))
                                                                @include('loja.partials.alert', ['type' => 'erro', 'message' => $errors->first($chaveErroLinha), 'class' => 'mt-2 mb-0'])
                                                            @endif
                                                        </form>
                                                    @else
                                                        <div class="quentity_btn">
                                                            <input type="text" value="{{ $linha->quantidade }}" readonly disabled aria-label="{{ 'Quantidade' }}">
                                                        </div>
                                                        @if ($errors->has($chaveErroLinha))
                                                            @include('loja.partials.alert', ['type' => 'erro', 'message' => $errors->first($chaveErroLinha), 'class' => 'mt-2 mb-0'])
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="pro_tk">
                                            <dl class="precos-linha-carrinho mb-0">
                                                <div class="precos-linha-carrinho__item">
                                                    <dt>{{ 'Preço unitário' }}</dt>
                                                    <dd>{{ $linha->precoUnitarioFormatado() ?? '—' }}</dd>
                                                </div>
                                                <div class="precos-linha-carrinho__item">
                                                    <dt>Subtotal</dt>
                                                    <dd>{{ $linha->subtotalFormatado() ?? '—' }}</dd>
                                                </div>
                                            </dl>
                                        </td>
                                        <td class="pro_icon">
                                            <div class="campo-linha-carrinho">
                                                <span class="campo-linha-carrinho__rotulo">{{ 'Ação' }}</span>
                                                <form method="POST" action="{{ route('loja.carrinho.itens.remover', $linha->id) }}" id="formulario-remover-carrinho-{{ $linha->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="botao-remover-carrinho" aria-label="{{ 'Remover '.$linha->nome().' ('.$linha->rotuloCor().')' }}">
                                                        {{ 'Remover' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @include('loja.partials.resumo-pedido', [
                    'totalProdutosFormatado' => $totalProdutosFormatado,
                    'checkout' => $checkout ?? null,
                ])

                @if ($errors->has('checkout') || $errors->has('cupom'))
                    @include('loja.partials.alert', ['type' => 'erro', 'message' => $errors->first('checkout') ?: $errors->first('cupom')])
                @endif

                @if ($verificado ?? false)
                    <form method="POST" action="{{ route('loja.carrinho.cupom') }}" class="mb-3">
                        @csrf
                        <label class="form-label" for="cupom">Cupom</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <input id="cupom" name="cupom" class="form-control" value="{{ old('cupom', $cupomCodigo) }}" maxlength="40">
                            <button type="submit" class="common_btn">Aplicar cupom</button>
                        </div>
                    </form>

                    @if ($enderecos->isNotEmpty())
                        <form method="POST" action="{{ route('loja.carrinho.endereco') }}" class="mb-3">
                            @csrf
                            <label class="form-label" for="endereco_id">Endereço de entrega</label>
                            <select id="endereco_id" name="endereco_id" class="form-select">
                                @foreach ($enderecos as $endereco)
                                    <option value="{{ $endereco->id }}" @selected((int) $enderecoId === $endereco->id || ($enderecoId === null && $endereco->padrao))>{{ $endereco->linhaCompleta() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="common_btn mt-2">Usar este endereço</button>
                        </form>
                    @else
                        <p><a href="{{ route('conta.enderecos.criar') }}">Cadastre um endereço</a> para calcular o frete.</p>
                    @endif
                @elseif ($autenticado ?? false)
                    <p>Confirme seu e-mail para aplicar cupom, escolher entrega e finalizar o pedido.</p>
                @endif

                <div class="pagina-carrinho__acoes">
                    <a href="{{ route('inicio') }}" class="common_btn">{{ 'Continuar comprando' }}</a>
                    @if (($verificado ?? false) && ($checkout['valido'] ?? false))
                        <a href="{{ route('checkout.revisar') }}" class="common_btn">Revisar pedido</a>
                    @else
                        <p class="checkout-indisponivel" aria-disabled="true">{{ ($checkout['motivo'] ?? null) ?: 'Conclua login, verificação, endereço e itens válidos para revisar o pedido. Nenhum pagamento é processado nesta loja.' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
