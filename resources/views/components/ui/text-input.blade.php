@props(['error' => false])

<input {{ $attributes->class([
    'h-[46px] w-full rounded-lg border bg-surface px-4 text-[13px] text-body',
    'placeholder:text-placeholder',
    'focus:outline-none focus:ring-0',
    $error
        ? 'border-primary focus:border-primary'
        : 'border-hairline-strong focus:border-label',
]) }}>
