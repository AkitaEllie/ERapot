@php
    use App\Models\User;

    $menu = match (auth()->user()?->Role_ID) {
        'GURU' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['key' => 'indikator', 'label' => 'Indikator Penilaian', 'icon' => 'checklist'],
            ['key' => 'rapor', 'label' => 'Rapor Siswa', 'icon' => 'document'],
            ['key' => 'ekspor', 'label' => 'Daftar Rapor & Export', 'icon' => 'download'],
        ],
        'TATAUSA' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['key' => 'tahun-ajaran', 'label' => 'Tahun Ajaran', 'icon' => 'calendar'],
            ['key' => 'pengguna', 'label' => 'Data Guru & Pengguna', 'icon' => 'users'],
            ['key' => 'siswa', 'label' => 'Data Siswa', 'icon' => 'users'],
            ['key' => 'kelas', 'label' => 'Data Kelas', 'icon' => 'board'],
            ['key' => 'penempatan', 'label' => 'Penempatan Siswa', 'icon' => 'switch'],
            ['key' => 'mapel', 'label' => 'Mata Pelajaran', 'icon' => 'book'],
            ['key' => 'penugasan', 'label' => 'Penugasan Mengajar', 'icon' => 'clipboard'],
        ],
        'KEPSEKOL' => [
            ['key' => 'dashboard', 'label' => 'Dashboard Progres', 'icon' => 'home'],
            ['key' => 'review', 'label' => 'Review Draf Rapor', 'icon' => 'document'],
            ['key' => 'persetujuan', 'label' => 'Persetujuan Rapor', 'icon' => 'check'],
        ],
        default => [],
    };

    $roleLabel = match (auth()->user()?->Role_ID) {
        'GURU' => 'GURU',
        'TATAUSA' => 'TATA USAHA',
        'KEPSEKOL' => 'KEPALA SEKOLAH',
        'ADMIN' => 'ADMINISTRATOR',
        default => 'PENGGUNA',
    };

    // Not on ERD/CD: sidebar entries map to the built screens; screens that do not exist
    // yet keep a null target and render as disabled placeholders.
    $targets = [
        'indikator' => fn (): string => route('indikator'),
        'rapor' => fn (): string => route('rapor.index'),
        'dashboard-tata-usaha' => fn (): string => route('dashboard.tata-usaha'),
        'dashboard-kepsekol' => fn (): string => route('dashboard.kepsekol'),
        'siswa' => fn (): string => route('siswa.index'),
        'penempatan' => fn (): string => route('penempatan'),
        'review' => fn (): string => route('review-rapor'),
        'persetujuan' => fn (): string => route('review-rapor'),
    ];

    $icons = [
        'home' => '<path d="M3 9.5 10 4l7 5.5V16a1 1 0 0 1-1 1h-4v-4H8v4H4a1 1 0 0 1-1-1V9.5Z"/>',
        'calendar' => '<rect x="3" y="4.5" width="14" height="12" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/>',
        'users' => '<circle cx="7" cy="7" r="2.6"/><path d="M2.8 16c.4-2.4 2.2-3.9 4.2-3.9S10.8 13.6 11.2 16"/><path d="M13 5.2a2.4 2.4 0 0 1 0 4.6M14 12.4c1.7.5 2.8 1.9 3.1 3.6"/>',
        'board' => '<rect x="3" y="3.5" width="14" height="13" rx="1.5"/><path d="M3 8h14M8 8v8.5"/>',
        'switch' => '<path d="M4 7h9l-2.5-2.5M16 13H7l2.5 2.5"/>',
        'book' => '<path d="M4 4.5h5a2 2 0 0 1 2 2V16a1.5 1.5 0 0 0-1.5-1.5H4v-10ZM16 4.5h-5a2 2 0 0 0-2 2V16a1.5 1.5 0 0 1 1.5-1.5H16v-10Z"/>',
        'clipboard' => '<rect x="5" y="4" width="10" height="12.5" rx="1.5"/><path d="M8 4V3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1M8 9h4M8 12h4"/>',
        'checklist' => '<path d="M4 6.5 5.5 8 8 5M11 6.5h5M4 13.5 5.5 15 8 12M11 13.5h5"/>',
        'document' => '<path d="M5 3.5h6l4 4v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-12a1 1 0 0 1 1-1Z"/><path d="M11 3.5v4h4M7.5 11.5h5M7.5 14h5"/>',
        'download' => '<path d="M10 3.5v9M6.5 9 10 12.5 13.5 9M4 15.5h12"/>',
        'check' => '<path d="M4.5 10.5 8 14l7.5-8"/>',
    ];
@endphp

<aside class="flex h-screen w-60 shrink-0 flex-col border-r border-hairline bg-surface">
    <div class="flex items-center gap-3 px-6 pt-7 pb-6">
        <img src="/images/logo-tk-cktc.png" alt="" class="size-10 rounded-lg object-contain">
        <div class="leading-tight">
            <p class="text-[17px] font-semibold text-heading">E-RAPOR</p>
            <p class="text-[13px] text-muted">TK CKTC</p>
        </div>
    </div>

    <p class="px-6 pb-3 text-[12px] font-semibold tracking-wide text-muted">{{ $roleLabel }}</p>

    <nav class="flex flex-col gap-2 px-3">
        @foreach ($menu as $item)
            <a
                href="{{ $item['key'] === 'dashboard' ? route(auth()->user()?->Role_ID === 'TATAUSA' ? 'dashboard.tata-usaha' : (auth()->user()?->Role_ID === 'KEPSEKOL' ? 'dashboard.kepsekol' : 'dashboard')) : (isset($targets[$item['key']]) ? $targets[$item['key']]() : '#') }}"
                @if ($item['key'] === 'dashboard') wire:navigate @endif
                @class([
                    'flex h-10 items-center gap-2 rounded-lg px-3 text-[16px] transition-colors',
                    'bg-accent-soft text-accent-deep' => $active === $item['key'],
                    'text-label hover:bg-canvas' => $active !== $item['key'],
                ])
            >
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0">
                    {!! $icons[$item['icon']] !!}
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
