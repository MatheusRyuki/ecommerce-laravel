<div class="resumo-pedido-carrinho">
    <h2>{{ 'Resumo do pedido' }}</h2>
    @if ($totalProdutosFormatado !== null)
        <div class="resumo-pedido-carrinho__linha total-produtos-carrinho">
            <span>{{ 'Total dos produtos' }}</span>
            <span class="resumo-pedido-carrinho__valor">{{ $totalProdutosFormatado }}</span>
        </div>
    @else
        @include('loja.partials.alert', ['type' => 'aviso', 'message' => 'O total dos produtos só aparece quando todos os itens estão disponíveis.', 'class' => 'mb-0'])
    @endif
</div>
