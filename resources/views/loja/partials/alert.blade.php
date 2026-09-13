@props([
    'type' => 'info',
    'dismissible' => false,
    'title' => null,
    'message' => null,
    'class' => '',
])

@php
    $tone = match ($type) {
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info',
    };
    $role = $type === 'success' || $type === 'info' ? 'status' : 'alert';
    $text = $message ?? '';
@endphp

<div class="alert {{ $tone }} {{ $dismissible ? 'alert-dismissible fade show' : '' }} {{ $class }}" role="{{ $role }}">
    @if ($title)
        <strong class="d-block">{{ $title }}</strong>
    @endif
    {{ $text }}
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    @endif
</div>

