<?php

use App\Enums\RaporStatus;
use App\Models\MataPelajaran;
use App\Models\Rapor;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app', ['active' => 'ekspor'])]
class extends \Livewire\Component
{
    public Rapor $rapor;

    public function cetak(): void
    {
        session()->flash('galat', 'Cetak belum diimplementasikan.');
    }

    public function exportPdf(): void
    {
        if ($this->rapor->Status !== RaporStatus::Disetujui) {
            session()->flash('galat', 'Rapor belum disetujui Kepala Sekolah, file PDF belum bisa diunduh.');

            return;
        }

        // Not on ERD/CD: PDF generation is a stub until Rapor::eksporPDF() is implemented.
        session()->flash('galat', 'Export PDF belum diimplementasikan.');
    }

    #[Computed]
    public function narasiFinal(): array
    {
        return $this->rapor->narasi()
            ->whereNotNull('Narasi_Final')
            ->with('mataPelajaran')
            ->get()
            ->mapWithKeys(fn ($narasi): array => [$narasi->Mapel_ID => $narasi])
            ->all();
    }

    #[Computed]
    public function fotoDokumentasi(): array
    {
        return $this->rapor->narasi()
            ->with(['dokumentasi', 'mataPelajaran'])
            ->get()
            ->flatMap(fn ($narasi) => $narasi->dokumentasi->map(fn ($foto) => [
                'foto' => $foto,
                'mapel' => $narasi->mataPelajaran?->Nama_Mapel,
            ]))
            ->take(2)
            ->values()
            ->all();
    }
};

?>

<div>
    <x-app.header
        :title="'Pratinjau Rapor'"
        :subtitle="$rapor->siswa->Nama.' - '.$rapor->kelas->Nama_Kelas.' - Semester '.$rapor->Semester->label().' '.$rapor->kelas->tahunAjaran->Tahun_Ajaran"
    >
        <x-slot:actions>
            <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
            <x-ui.button type="button" variant="secondary" wire:click="cetak">Cetak</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="exportPdf">Export PDF</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if ($rapor->Status !== RaporStatus::Disetujui)
        <div class="flex items-center gap-3 border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0" aria-hidden="true">
                <circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.2M10 13.4h.01" stroke-linecap="round"/>
            </svg>
            Rapor belum disetujui Kepala Sekolah, file PDF belum bisa diunduh (kondisi alternatif)
        </div>
    @endif

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <article class="mx-auto w-[864px] bg-surface px-8 py-8 shadow-sm ring-1 ring-hairline">
            <header class="border-b border-hairline pb-6 text-center">
                <h2 class="text-[18px] font-semibold text-heading">LAPORAN PERKEMBANGAN PESERTA DIDIK</h2>
                <p class="mt-1 text-[15px] text-body">TK Cinta Kasih Tzu Chi - Semester {{ $rapor->Semester->label() }} {{ $rapor->kelas->tahunAjaran->Tahun_Ajaran }}</p>
            </header>

            <dl class="mt-6 grid grid-cols-2 gap-x-8 gap-y-3 text-[15px]">
                <div class="flex gap-2"><dt class="w-28 text-muted">Nama</dt><dd class="text-body">: {{ $rapor->siswa->Nama }}</dd></div>
                <div class="flex gap-2"><dt class="w-28 text-muted">NISN</dt><dd class="text-body">: {{ $rapor->siswa->NISN }}</dd></div>
                <div class="flex gap-2"><dt class="w-28 text-muted">Kelas / Fase</dt><dd class="text-body">: {{ $rapor->kelas->Nama_Kelas }} / {{ $rapor->kelas->Jenjang->value }}</dd></div>
                <div class="flex gap-2"><dt class="w-28 text-muted">Tinggi / Berat</dt><dd class="text-body">: {{ $rapor->Tinggi_Badan }} cm / {{ $rapor->Berat_Badan }} kg</dd></div>
            </dl>

            <div class="mt-8 space-y-6">
                @foreach ($this->narasiFinal as $narasi)
                    @if ($narasi->mataPelajaran instanceof MataPelajaran)
                        <section>
                            <h3 class="text-[15px] font-semibold text-heading">Nilai-nilai {{ $narasi->mataPelajaran->Nama_Mapel }}</h3>
                            <p class="mt-1.5 text-[15px] leading-relaxed text-body">{{ $narasi->Narasi_Final }}</p>
                        </section>
                    @endif
                @endforeach
            </div>

            @if (count($this->fotoDokumentasi) > 0)
                <section class="mt-8 flex gap-6">
                    @foreach ($this->fotoDokumentasi as $item)
                        <figure class="w-[180px]">
                            <img src="{{ Storage::url($item['foto']->Foto) }}" alt="{{ $item['foto']->Keterangan }}" class="h-[120px] w-[180px] rounded border border-hairline object-cover">
                            <figcaption class="mt-1 truncate text-[13px] text-muted">{{ $item['mapel'] }}</figcaption>
                        </figure>
                    @endforeach
                </section>
            @endif

            <p class="mt-8 border-t border-hairline pt-4 text-[15px] text-body">
                Ketidakhadiran: Sakit {{ $rapor->Sakit }} hari, Izin {{ $rapor->Izin }} hari, Tanpa Keterangan {{ $rapor->Tanpa_Keterangan }} hari
            </p>

            <div class="mt-10 flex justify-between gap-16 text-center text-[15px]">
                <div>
                    <p class="text-muted">Wali Kelas</p>
                    <p class="mt-20 font-medium text-heading">{{ $rapor->kelas->waliKelas?->Nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted">Kepala Sekolah</p>
                    <p class="mt-20 font-medium text-heading">{{ $rapor->disetujuiOleh?->Nama ?? '-' }}</p>
                </div>
            </div>
        </article>
    </div>
</div>
