<?php

use App\Enums\JenisMataPelajaran;
use App\Enums\RaporStatus;
use App\Enums\StatusNarasi;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use Illuminate\Support\Collection;
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

    /**
     * Whether the signed-in teacher owns this class as its homeroom teacher.
     *
     * A homeroom teacher holds one class and produces the whole report book; a
     * subject teacher spans all classes and produces only their own subject page.
     */
    #[Computed]
    public function waliKelas(): bool
    {
        $user = auth()->user();

        return $user !== null && $this->rapor->kelas->User_ID === $user->getKey();
    }

    /**
     * The subjects this report should show.
     *
     * The homeroom teacher gets every subject; a subject teacher gets only the
     * subjects they are assigned to teach in this class.
     *
     * @return Collection<int, array{mapel: MataPelajaran, narasi: ?Narasi, pengampu: ?\App\Models\User}>
     */
    #[Computed]
    public function bagian(): Collection
    {
        $narasi = $this->rapor->narasi()
            ->with(['nilaiSiswa', 'dokumentasi', 'mataPelajaran'])
            ->get()
            ->keyBy('Mapel_ID');

        $pengampu = Pembelajaran::query()
            ->where('Kelas_ID', $this->rapor->Kelas_ID)
            ->with('guru')
            ->get()
            ->keyBy('Mapel_ID');

        return MataPelajaran::query()
            ->whereIn('Mapel_ID', $pengampu->keys())
            ->where('Tampilkan_Indikator', true)
            ->when(! $this->waliKelas, fn ($query) => $query->whereIn('Mapel_ID', $this->mapelDiajarIds()))
            ->orderBy('Nama_Mapel')
            ->get()
            ->map(fn (MataPelajaran $mapel): array => [
                'mapel' => $mapel,
                'narasi' => $narasi->get($mapel->getKey()),
                'pengampu' => $pengampu->get($mapel->getKey())?->guru,
            ]);
    }

    /**
     * The subjects the signed-in teacher is assigned in this class.
     *
     * @return array<int, string>
     */
    private function mapelDiajarIds(): array
    {
        return Pembelajaran::query()
            ->where('Kelas_ID', $this->rapor->Kelas_ID)
            ->where('User_ID', auth()->user()?->getKey())
            ->pluck('Mapel_ID')
            ->all();
    }

    /** @return Collection<int, array{mapel: MataPelajaran, narasi: ?Narasi, pengampu: ?\App\Models\User}> */
    public function seksiA(): Collection
    {
        return $this->bagian->filter(fn (array $b): bool => $b['mapel']->Jenis_Mapel === JenisMataPelajaran::Intrakurikuler)->values();
    }

    public function seksiB(): Collection
    {
        return $this->bagian->filter(fn (array $b): bool => $b['mapel']->Jenis_Mapel === JenisMataPelajaran::KoKurikuler)->values();
    }

    public function halamanTabel(): Collection
    {
        return $this->bagian->filter(fn (array $b): bool => $b['mapel']->Jenis_Mapel === JenisMataPelajaran::MuatanLokal)->values();
    }

    /**
     * The report is only meaningful once narratives are final, but a draft can be
     * previewed so the teacher can see how it will read.
     */
    #[Computed]
    public function siapCetak(): bool
    {
        return $this->rapor->Status === RaporStatus::Disetujui;
    }
};

?>

<div>
    <x-app.header
        title="Pratinjau Rapor"
        :subtitle="$rapor->siswa->Nama.' - '.$rapor->kelas->Nama_Kelas.' - Semester '.$rapor->Semester->label().' '.$rapor->kelas->tahunAjaran->Tahun_Ajaran"
    >
        <x-slot:actions>
            <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
            <x-ui.button type="button" variant="secondary" wire:click="cetak">Cetak</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="exportPdf">Export PDF</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if (! $this->siapCetak)
        <div class="flex items-center gap-3 border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0" aria-hidden="true">
                <circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.2M10 13.4h.01" stroke-linecap="round"/>
            </svg>
            Rapor belum disetujui Kepala Sekolah, file PDF belum bisa diunduh (kondisi alternatif)
        </div>
    @endif

    @if (session('galat'))
        <div class="border-b border-hairline bg-rose-50 px-8 py-2.5 text-[14px] text-rose-800">{{ session('galat') }}</div>
    @endif

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        {{-- Rendered at A4 proportions so the preview matches the exported file. --}}
        <article class="rapor-dokumen mx-auto w-[794px] bg-white px-[56px] py-[56px] text-[13px] leading-relaxed text-[#111] shadow-sm ring-1 ring-hairline">
            <x-rapor.gaya />

            @if ($this->waliKelas)
                {{-- The homeroom teacher owns the whole book, as in contoh-rapor.pdf. --}}
                @include('components.rapor.identitas', ['rapor' => $rapor])

                {{-- A. INTRAKURIKULER --}}
                @if ($this->seksiA()->isNotEmpty())
                    <h2 class="mt-6 mb-3 font-bold">A. INTRAKURIKULER</h2>
                    @foreach ($this->seksiA() as $item)
                        <x-rapor.seksi-prosa :narasi="$item['narasi']" :mapel="$item['mapel']" />
                    @endforeach
                @endif

                {{-- B. KOKURIKULER --}}
                @if ($this->seksiB()->isNotEmpty())
                    <h2 class="mt-8 mb-3 font-bold">B. KOKURIKULER</h2>
                    @foreach ($this->seksiB() as $item)
                        <x-rapor.seksi-prosa
                            :narasi="$item['narasi']"
                            :mapel="$item['mapel']"
                            :judul="'Deskripsi Pengembangan Proyek ('.$item['mapel']->Nama_Mapel.')'"
                        />
                    @endforeach
                @endif

                <x-rapor.penutup :rapor="$rapor" />
            @endif

            {{-- Table-template subjects, one page each. A subject teacher sees only
                 the subject they are assigned to in this class. --}}
            @foreach ($this->halamanTabel() as $item)
                <div class="{{ $loop->first && ! $this->waliKelas ? '' : 'mt-12 border-t-2 border-dashed border-hairline pt-10' }}">
                    <x-rapor.seksi-tabel
                        :narasi="$item['narasi']"
                        :mapel="$item['mapel']"
                        :rapor="$rapor"
                        :pengampu="$item['pengampu']"
                    />
                </div>
            @endforeach
        </article>
    </div>
</div>
