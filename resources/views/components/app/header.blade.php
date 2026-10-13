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

        {{-- Profile popout: gives the signed-in identity a place to hold the logout action. --}}
        <div class="relative" x-data="{ terbuka: false }" @keydown.escape.window="terbuka = false">
            <button
                type="button"
                class="flex items-center gap-3 rounded-lg px-2 py-1 transition-colors hover:bg-sunken"
                @click="terbuka = ! terbuka"
                :aria-expanded="terbuka"
                aria-haspopup="menu"
            >
                <span class="flex size-8 items-center justify-center rounded-full bg-sunken text-[12px] font-semibold text-label">
                    {{ auth()->user()?->initials() }}
                </span>
                <div class="leading-tight text-left">
                    <p class="text-[15px] text-heading">{{ auth()->user()?->Nama }}</p>
                    <p class="text-[13px] text-muted">{{ auth()->user()?->role?->Nama_Role }}</p>
                </div>
            </button>

            <div
                x-cloak
                x-show="terbuka"
                @click.outside="terbuka = false"
                role="menu"
                aria-label="Menu profil"
                class="absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-lg border border-hairline bg-surface shadow-sm"
            >
                <div class="border-b border-hairline px-4 py-3">
                    <p class="truncate text-[15px] font-medium text-heading">{{ auth()->user()?->Nama }}</p>
                    <p class="truncate text-[13px] text-muted">{{ auth()->user()?->Email }}</p>
                    <p class="mt-1 text-[12px] text-placeholder">{{ auth()->user()?->role?->Nama_Role }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="p-1.5">
                    @csrf
                    <button
                        type="submit"
                        role="menuitem"
                        class="flex w-full items-center gap-2.5 rounded px-2.5 py-2 text-left text-[15px] text-body transition-colors hover:bg-sunken"
                    >
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-muted" aria-hidden="true">
                            <path d="M8 17H4.5A1.5 1.5 0 0 1 3 15.5v-11A1.5 1.5 0 0 1 4.5 3H8"/><path d="M13 13.5 17 10l-4-3.5"/><path d="M17 10H8"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
