@props(['messages'])

@php
    $itens = collect(\Illuminate\Support\Arr::flatten((array) $messages))
        ->filter(fn (mixed $mensagem): bool => is_string($mensagem) && $mensagem !== '')
        ->unique()
        ->all();
@endphp

@if ($itens !== [])
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }} role="alert">
        @foreach ($itens as $mensagem)
            <li>{{ $mensagem }}</li>
        @endforeach
    </ul>
@endif
