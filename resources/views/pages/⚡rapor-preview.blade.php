<?php

use App\Enums\JenisMataPelajaran;
use App\Enums\RaporStatus;
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

    /**
     * Export the report and hand the file to the browser.
     *
     * The file is produced first so a failure surfaces here rather than as a
     * broken download, then the browser is sent to the route that streams it.
     */
    public function exportPdf(): void
    {
        if ($this->rapor->Status !== RaporStatus::Disetujui) {
            session()->flash('galat', 'Rapor belum disetujui Kepala Sekolah, file PDF belum bisa diunduh.');

            return;
        }

        $this->rapor->eksporPDF();

        $this->redirect(route('rapor.unduh', $this->rapor), navigate: false);
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
     * The report's subject groups, narrowed to what this viewer may see.
     *
     * @return array{intrakurikuler: Collection<int, array{mapel: \App\Models\MataPelajaran, narasi: ?\App\Models\Narasi, pengampu: ?\App\Models\User}>, kokurikuler: Collection<int, array{mapel: \App\Models\MataPelajaran, narasi: ?\App\Models\Narasi, pengampu: ?\App\Models\User}>, tabel: Collection<int, array{mapel: \App\Models\MataPelajaran, narasi: ?\App\Models\Narasi, pengampu: ?\App\Models\User}>}
     */
    #[Computed]
    public function seksi(): array
    {
        $seksi = $this->rapor->seksi();

        if ($this->waliKelas) {
            return $seksi;
        }

        $diampu = $this->mapelDiajarIds();

        $hanya = fn (Collection $items): Collection => $items
            ->filter(fn (array $b): bool => in_array($b['mapel']->getKey(), $diampu, true))
            ->values();

        return [
            'intrakurikuler' => $hanya($seksi['intrakurikuler']),
            'kokurikuler' => $hanya($seksi['kokurikuler']),
            'tabel' => $hanya($seksi['tabel']),
        ];
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

    /**
     * Whether the viewer sees anything at all. A subject teacher with no
     * assignment in this class gets an empty document rather than a misleading one.
     */
    #[Computed]
    public function adaIsi(): bool
    {
        $seksi = $this->seksi;

        return $this->waliKelas
            || $seksi['intrakurikuler']->isNotEmpty()
            || $seksi['kokurikuler']->isNotEmpty()
            || $seksi['tabel']->isNotEmpty();
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
        @if (! $this->adaIsi)
            <p class="mx-auto w-[794px] bg-white px-[56px] py-[56px] text-center text-[14px] text-muted shadow-sm ring-1 ring-hairline">
                Tidak ada mata pelajaran yang bisa Anda lihat pada rapor ini.
            </p>
        @else
            {{-- Rendered at A4 proportions so the preview matches the exported file. --}}
            <article class="mx-auto w-[794px] bg-white px-[56px] py-[56px] text-[13px] leading-relaxed text-[#111] shadow-sm ring-1 ring-hairline">
                <x-rapor.dokumen
                    :rapor="$rapor"
                    :identitas="$this->waliKelas"
                    :intrakurikuler="$this->seksi['intrakurikuler']"
                    :kokurikuler="$this->seksi['kokurikuler']"
                    :tabel="$this->seksi['tabel']"
                />
            </article>
        @endif
    </div>
</div>
