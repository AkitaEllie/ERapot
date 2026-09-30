<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app', ['active' => 'dashboard'])]
class extends \Livewire\Component
{
    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->first();
    }

    #[Computed]
    public function semester(): string
    {
        return $this->periode?->Semester_Aktif?->label() ?? '-';
    }

    #[Computed]
    public function totalRapor(): int
    {
        return Rapor::query()->where('Semester', $this->periode?->Semester_Aktif?->value)->count();
    }

    /** @return array<string, int> */
    #[Computed]
    public function ringkasan(): array
    {
        $semester = $this->periode?->Semester_Aktif?->value;

        return [
            'disetujui' => Rapor::query()->where('Semester', $semester)->where('Status', RaporStatus::Disetujui)->count(),
            'menunggu' => Rapor::query()->where('Semester', $semester)->where('Status', RaporStatus::Menunggu)->count(),
            'perlu_revisi' => Rapor::query()->where('Semester', $semester)->where('Status', RaporStatus::PerluRevisi)->count(),
            'belum_diisi' => Rapor::query()->where('Semester', $semester)->where('Status', RaporStatus::BelumDiisi)->count(),
        ];
    }

    /** @return Collection<int, Kelas> */
    #[Computed]
    public function kelasDenganProgres(): Collection
    {
        return Kelas::denganProgres($this->periode?->Semester_Aktif ?? Semester::Ganjil);
    }

    #[Computed]
    public function persen(Kelas $kelas): int
    {
        $total = (int) $kelas->total_rapor;

        if ($total === 0) {
            return 0;
        }

        return (int) round((int) $kelas->rapor_disetujui / $total * 100);
    }

    public ?string $kelasDipilih = null;

    public function lihatDetail(string $kelasId): void
    {
        $this->kelasDipilih = $this->kelasDipilih === $kelasId ? null : $kelasId;
    }

    #[Computed]
    public function raporKelasDipilih(): Collection
    {
        if ($this->kelasDipilih === null) {
            return collect();
        }

        return Rapor::query()
            ->where('Kelas_ID', $this->kelasDipilih)
            ->where('Semester', $this->periode?->Semester_Aktif?->value)
            ->with('siswa')
            ->orderBy('Siswa_ID')
            ->get();
    }
};

?>

<div>
    <x-app.header
        title="Dashboard Progres Rapor"
        :subtitle="'Semester '.$this->semester.' '.($this->periode?->Tahun_Ajaran ?? '')"
    />

    <div class="flex-1 space-y-6 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-4 gap-5">
            <x-ui.card
                label="Rapor selesai"
                :value="$this->ringkasan['disetujui'].' / '.$this->totalRapor"
            />
            <x-ui.card label="Menunggu persetujuan" :value="$this->ringkasan['menunggu']" />
            <x-ui.card label="Perlu revisi" :value="$this->ringkasan['perlu_revisi']" />
            <x-ui.card label="Belum diisi" :value="$this->ringkasan['belum_diisi']" />
        </div>

        <section>
            <h2 class="text-[16px] font-semibold text-heading">Progres per Kelas</h2>
            <p class="mt-1 text-[15px] text-muted">Klik kartu kelas untuk melihat status rapor tiap siswa</p>

            <div class="mt-4 grid grid-cols-3 gap-5">
                @foreach ($this->kelasDenganProgres as $kelas)
                    @php $total = (int) $kelas->total_rapor; $selesai = (int) $kelas->rapor_disetujui; @endphp
                    <div class="rounded border border-hairline bg-surface p-5">
                        <h3 class="text-[16px] font-semibold text-heading">{{ $kelas->Nama_Kelas }}</h3>
                        <p class="mt-0.5 text-[14px] text-muted">Wali kelas: {{ $kelas->waliKelas?->Nama ?? '-' }}</p>

                        <p class="mt-3 text-[15px] text-body">{{ $selesai }} / {{ $total }} rapor selesai</p>

                        <x-ui.progress :value="$this->persen($kelas)" class="mt-3" width="w-full" />

                        <x-ui.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="mt-4 w-[132px] justify-center"
                            wire:click="lihatDetail('{{ $kelas->getKey() }}')"
                        >Lihat Detail</x-ui.button>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($kelasDipilih !== null)
            <section>
                <h2 class="text-[16px] font-semibold text-heading">
                    Status rapor tiap siswa - {{ $this->kelasDenganProgres->firstWhere('getKey', $kelasDipilih)?->Nama_Kelas }}
                </h2>

                <div class="mt-4 overflow-hidden rounded border border-hairline bg-surface">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                                <th class="w-16 px-4 py-3 text-right font-medium">No</th>
                                <th class="w-32 px-4 py-3 text-left font-medium">NISN</th>
                                <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                                <th class="w-48 px-4 py-3 text-left font-medium">Status Rapor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->raporKelasDipilih as $rapor)
                                <tr wire:key="{{ $rapor->getKey() }}" class="border-b border-hairline last:border-b-0">
                                    <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->NISN }}</td>
                                    <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->Nama }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-[15px] text-muted">Belum ada rapor untuk kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
