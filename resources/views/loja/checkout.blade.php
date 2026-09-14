@extends('loja.layouts.aplicacao')

@section('title', 'Revisar pedido')

@section('content')
    <section class="wsus__cart loja-conteudo pagina-carrinho">
        <div class="container">
            <h1 class="h3 mb-4">Revisar pedido</h1>
            @if ($errors->any())
                @include('loja.partials.alert', ['type' => 'erro', 'message' => $errors->first()])
            @endif
            <p>Nenhum pagamento será processado. O pedido ficará como aguardando pagamento.</p>
            <ul class="list-unstyled">
                @foreach ($linhas as $linha)
                    <li class="mb-2">{{ $linha->nome() }} — {{ $linha->rotuloCor() }} × {{ $linha->quantidade }} — {{ $linha->subtotalFormatado() }}</li>
                @endforeach
            </ul>
            <p>Endereço: {{ $calculo['endereco']->linhaCompleta() }}</p>
            @if ($calculo['cupom'])
                <p>Cupom: {{ $calculo['cupom']->codigo }}</p>
            @endif
            @include('loja.partials.resumo-pedido', [
                'totalProdutosFormatado' => $subtotalFormatado,
                'checkout' => [
                    'valido' => true,
                    'desconto' => $calculo['desconto'],
                    'frete' => $calculo['frete'],
                    'total' => $calculo['total'],
                ],
            ])
            <form method="POST" action="{{ route('checkout.confirmar') }}" class="mt-4">
                @csrf
                <input type="hidden" name="chave" value="{{ $chave }}">
                <button type="submit" class="common_btn">Confirmar pedido</button>
                <a href="{{ route('carrinho') }}" class="ms-3">Voltar ao carrinho</a>
            </form>
        </div>
    </section>
@endsection
