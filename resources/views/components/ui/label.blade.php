@props(['for' => null])

<label @if($for) for="{{ $for }}" @endif {{ $attributes->class('block text-[12px] font-semibold text-label') }}>
    {{ $slot }}
</label>
