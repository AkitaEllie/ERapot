@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'border border-hairline-strong bg-surface text-label hover:bg-canvas',
        'ghost' => 'text-label hover:bg-canvas',
        'danger' => 'border border-hairline-strong bg-surface text-label hover:bg-canvas',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-[13px]',
        'md' => 'h-10 px-4 text-[13px]',
    ];
@endphp

<button
    type="{{ $type }}"
    @disabled($disabled)
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap',
        'transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        'disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]) }}
>
    {{ $slot }}
</button>
