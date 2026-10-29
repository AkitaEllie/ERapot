@props(['invalid' => false])

<input {{ $attributes->class([
    'h-10 w-full rounded border bg-surface px-3 text-[15px] text-body placeholder:text-placeholder',
    'focus:ring-2 focus:outline-none disabled:bg-canvas disabled:text-muted',
    $invalid ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-hairline-strong focus:border-accent focus:ring-accent/30',
]) }}>
