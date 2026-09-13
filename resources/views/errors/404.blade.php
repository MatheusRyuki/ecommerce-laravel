@extends('loja.layouts.aplicacao')

@section('title', 'Página não encontrada')

@section('content')
    <section class="pagina-404">
        <div class="container">
            <h1>Página não encontrada</h1>
            <p class="mt-3">O endereço não existe ou o produto não está mais disponível.</p>
            <p class="mt-4">
                <a href="{{ route('inicio') }}" class="common_btn">Voltar à loja</a>
            </p>
        </div>
    </section>
@endsection
