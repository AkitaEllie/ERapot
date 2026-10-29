@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

{{-- A labelled form control with its validation message underneath. --}}
<div {{ $attributes->class('flex min-w-0 flex-col gap-1.5') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="text-[13px] font-medium text-muted">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($error)
        @error($error)
            <p class="flex items-center gap-1.5 text-[13px] font-medium text-rose-700">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="size-4 shrink-0" aria-hidden="true"><path d="M10 3 2.5 16.5h15L10 3Z"/><path d="M10 8.5v3.5M10 14.2h.01"/></svg>
                {{ $message }}
            </p>
        @enderror
    @endif

    @if ($hint)
        <p class="text-[12px] text-muted">{{ $hint }}</p>
    @endif
</div>
