@props([
    'status' => null,
    'tone' => 'success',
    'dismissible' => true,
])

@if ($status)
    @php
        $toneClasses = match ($tone) {
            'warning' => 'border-amber-300 bg-amber-50 text-amber-900',
            'danger' => 'border-red-300 bg-red-50 text-red-800',
            default => 'border-green-300 bg-green-50 text-green-800',
        };
        $role = $tone === 'danger' || $tone === 'warning' ? 'alert' : 'status';
    @endphp

    <div
        x-data="{ show: true }"
        x-show="show"
        {{ $attributes->merge(['class' => 'mb-4 rounded-md border px-4 py-3 text-sm '.$toneClasses]) }}
        role="{{ $role }}"
    >
        <div class="flex items-start justify-between gap-3">
            <p class="font-medium">{{ $status }}</p>
            @if ($dismissible)
                <button
                    type="button"
                    class="shrink-0 rounded p-1 text-current focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    aria-label="Fechar"
                    @click="show = false"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            @endif
        </div>
    </div>
@endif
