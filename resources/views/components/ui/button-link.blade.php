@props([
    'href',
    'variant' => 'secondary',
    'size' => 'md',
    // Livewire SPA navigation; turn off for downloads and other non-page targets.
    'navigate' => true,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'border border-hairline-strong bg-surface text-label hover:bg-canvas',
        'ghost' => 'text-label hover:bg-canvas',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-[13px]',
        'md' => 'h-10 px-4 text-[13px]',
    ];

    $classes = [
        'inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        $variants[$variant] ?? $variants['secondary'],
        $sizes[$size] ?? $sizes['md'],
    ];
@endphp

{{-- A link that looks like x-ui.button. A disabled one renders as an inert span
     so it cannot be followed, while keeping its place in the layout. --}}
@if ($disabled)
    <span aria-disabled="true" {{ $attributes->class([...$classes, 'cursor-not-allowed opacity-50']) }}>{{ $slot }}</span>
@else
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@endif
