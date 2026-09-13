@extends('loja.layouts.aplicacao')

@section('title', 'Loja')

@section('content')
    <!-- Início da vitrine -->
    <section class="wsus__product mt_145 pb_100">
        <div class="container">
            @if ($produtos->isEmpty())
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <p>Ainda não há produtos na loja.</p>
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
    <!-- Fim da vitrine -->
@endsection
