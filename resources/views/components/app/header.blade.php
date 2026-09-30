@props(['title', 'subtitle' => null])

<header class="flex h-[72px] shrink-0 items-center justify-between border-b border-hairline bg-surface pr-16 pl-8">
    <div class="leading-tight">
        <h1 class="text-[22px] font-semibold text-heading">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-[15px] text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex items-center gap-6">
        @isset($actions)
            <div class="flex items-center gap-3">{{ $actions }}</div>
        @endisset

        <button type="button" class="flex size-6 items-center justify-center rounded text-muted transition-colors hover:text-label" aria-label="Notifikasi">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" class="size-5">
                <path d="M10 3.5a4 4 0 0 0-4 4v2.2L4.8 12h10.4L14 9.7V7.5a4 4 0 0 0-4-4Z"/><path d="M8.2 14a1.8 1.8 0 0 0 3.6 0"/>
            </svg>
        </button>

        <div class="flex items-center gap-3">
            <span class="flex size-8 items-center justify-center rounded-full bg-sunken text-[12px] font-semibold text-label">
                {{ auth()->user()?->initials() }}
            </span>
            <div class="leading-tight">
                <p class="text-[15px] text-heading">{{ auth()->user()?->Nama }}</p>
                <p class="text-[13px] text-muted">{{ auth()->user()?->role?->Nama_Role }}</p>
            </div>
        </div>
    </div>
</header>
