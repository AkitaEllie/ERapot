@props(['title', 'subtitle' => null, 'width' => 'max-w-[560px]', 'close' => null])

{{-- Centred dialog over a dimmed page. Rendered only while the page shows it,
     so the open state lives in the Livewire component, not in Alpine. --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6" role="dialog" aria-modal="true" aria-label="{{ $title }}"
    @if ($close) wire:keydown.escape.window="{{ $close }}" @endif>
    <div {{ $attributes->class(['w-full rounded-xl bg-surface px-7 py-6 shadow-lg', $width]) }}>
        <h2 class="text-[18px] font-semibold text-heading">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-[14px] text-muted">{{ $subtitle }}</p>
        @endif

        <div class="mt-5">{{ $slot }}</div>

        @isset($footer)
            <div class="mt-6 flex items-center justify-end gap-3">{{ $footer }}</div>
        @endisset
    </div>
</div>
