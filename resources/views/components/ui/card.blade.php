@props([
    'padded' => true,
    'label' => null,
    'value' => null,
    'hint' => null,
])

<div {{ $attributes->class([
    'rounded-xl border border-hairline bg-surface',
    $padded ? 'p-5' : '',
]) }}>
    @if ($label !== null)
        <p class="text-[14px] text-muted">{{ $label }}</p>
        <p class="mt-1 text-[30px] leading-none font-semibold text-heading">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1.5 text-[13px] text-muted">{{ $hint }}</p>
        @endif
    @else
        {{ $slot }}
    @endif
</div>
