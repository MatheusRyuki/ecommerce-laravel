@extends('loja.layouts.aplicacao')

@section('title', $categoriaAtual ? $categoriaAtual->nome : 'Loja')

@section('content')
    <section class="wsus__product loja-conteudo pb_100">
        <div class="container">
            <h1 class="h3 mb-4">{{ $categoriaAtual ? $categoriaAtual->nome : 'Produtos' }}</h1>

            <form method="GET" action="{{ $categoriaAtual ? route('loja.categorias.exibir', $categoriaAtual) : route('inicio') }}" class="filtros-vitrine mb-4" aria-label="Filtros da vitrine">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="filtro-q">Buscar por nome ou SKU</label>
                        <input id="filtro-q" class="form-control" type="search" name="q" value="{{ $filtros['q'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filtro-cor">Cor</label>
                        <select id="filtro-cor" class="form-select" name="cor">
                            <option value="">Todas</option>
                            @foreach ($cores as $cor)
                                <option value="{{ $cor }}" @selected($filtros['cor'] === $cor)>{{ $cor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="disponivel" value="1" id="filtro-disponivel" @checked($filtros['disponivel'])>
                            <label class="form-check-label" for="filtro-disponivel">Só disponíveis</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="common_btn w-100">Filtrar</button>
                    </div>
                </div>
                @if ($filtros['q'] !== '' || $filtros['cor'] !== '' || $filtros['disponivel'])
                    <p class="mt-3 mb-0 filtros-vitrine__ativos">
                        Filtros ativos:
                        @if ($filtros['q'] !== '') busca “{{ $filtros['q'] }}” @endif
                        @if ($filtros['cor'] !== '') cor {{ $filtros['cor'] }} @endif
                        @if ($filtros['disponivel']) apenas disponíveis @endif
                        —
                        <a href="{{ $categoriaAtual ? route('loja.categorias.exibir', $categoriaAtual) : route('inicio') }}">Limpar filtros</a>
                    </p>
                @endif
            </form>

            @if ($produtos->isEmpty())
                <div class="row">
                    <div class="col-12 text-center py-5">
                        @if ($filtros['q'] !== '' || $filtros['cor'] !== '' || $filtros['disponivel'] || $categoriaAtual)
                            <p>Nenhum produto encontrado com os filtros atuais.</p>
                            <a href="{{ route('inicio') }}" class="common_btn">Ver toda a loja</a>
                        @else
                            <p>Ainda não há produtos na loja.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach ($produtos as $produto)
                        @include('loja.partials.item-produto', ['produto' => $produto])
                    @endforeach
                </div>
                @if ($produtos->hasPages())
                    <div class="wsus__pagination mt_60">
                        {{ $produtos->links('pagination.loja-bootstrap') }}
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
