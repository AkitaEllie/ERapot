@props(['rapor', 'daftarMapel', 'aktif'])

{{-- Tabs of the report input screen: one per subject, then the homeroom
     teacher's growth and attendance tab. --}}
<nav class="flex shrink-0 flex-wrap items-end gap-x-8 border-b border-hairline bg-surface px-8" aria-label="Bagian rapor">
    @foreach ($daftarMapel as $item)
        @php $terpilih = $item->getKey() === $aktif; @endphp
        <a
            href="{{ route('rapor.input', ['rapor' => $rapor->getKey(), 'mapel' => $item->getKey()]) }}"
            wire:navigate
            @class([
                'flex h-[50px] shrink-0 items-center border-b-[3px] text-[15px] whitespace-nowrap transition-colors',
                'border-accent font-medium text-accent-deep' => $terpilih,
                'border-transparent text-muted hover:text-label' => ! $terpilih,
            ])
            @if ($terpilih) aria-current="page" @endif
        >{{ $item->Nama_Mapel }}</a>
    @endforeach

    @php $terpilih = $aktif === 'data-fisik'; @endphp
    <a
        href="{{ route('rapor.data-fisik', $rapor) }}"
        wire:navigate
        @class([
            'flex h-[50px] shrink-0 items-center border-b-[3px] text-[15px] whitespace-nowrap transition-colors',
            'border-accent font-medium text-accent-deep' => $terpilih,
            'border-transparent text-muted hover:text-label' => ! $terpilih,
        ])
        @if ($terpilih) aria-current="page" @endif
    >Data Fisik &amp; Kehadiran</a>
</nav>
