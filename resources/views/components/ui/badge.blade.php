@props([
    'status' => null,
])

@php
    $classes = match ($status?->value) {
        'belum_diisi' => 'bg-accent-soft text-label ring-hairline',
        'draft' => 'bg-accent-soft text-accent-deep ring-accent/30',
        'menunggu' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'perlu_revisi' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'disetujui' => 'bg-accent/15 text-accent-deep ring-accent/40',
        default => 'bg-sunken text-label ring-hairline',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex h-6 items-center rounded px-2.5 text-[13px] ring-1 ring-inset',
    $classes,
]) }}>{{ $slot }}</span>
