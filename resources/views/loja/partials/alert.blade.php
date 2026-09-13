@props([
    'type' => 'info',
    'dismissible' => false,
    'title' => null,
    'message' => null,
    'class' => '',
])

@php
    $tom = match ($type) {
        'sucesso' => 'alert-success',
        'erro' => 'alert-danger',
        'aviso' => 'alert-warning',
        default => 'alert-info',
    };
    $role = $type === 'sucesso' || $type === 'info' ? 'status' : 'alert';
    $text = $message ?? '';
@endphp

<div class="alert {{ $tom }} {{ $dismissible ? 'alert-dismissible fade show' : '' }} {{ $class }}" role="{{ $role }}">
    @if ($title)
        <strong class="d-block">{{ $title }}</strong>
    @endif
    {{ $text }}
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    @endif
</div>

