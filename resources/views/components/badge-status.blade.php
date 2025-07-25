@props(['status'])

@php
    $colors = [
        'pagada' => 'badge-success',
        'pendiente' => 'badge-warning',
        'vencida' => 'badge-danger',
    ];
    $class = $colors[$status] ?? 'badge-secondary';
@endphp

<span {{ $attributes->merge(['class' => "badge $class"]) }}>
    {{ ucfirst($status) }}
</span>