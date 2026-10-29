<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kepala Sekolah: one class's report progress, student by student and subject
 * by subject, wireframe K-01. Reached from "Lihat Detail" on the dashboard.
 */
new #[Layout('layouts::app', ['active' => 'dashboard'])]
class extends Component
{
    public Kelas $kelas;

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->active()->first();
    }

    /** @return Collection<int, Rapor> */
    #[Computed]
    public function rapor(): Collection
    {
        return Rapor::query()
            ->where('Kelas_ID', $this->kelas->getKey())
            ->when($this->periode, fn ($query) => $query->where('Semester', $this->periode->Semester_Aktif))
            ->with(['siswa', 'narasi'])
            ->get()
            ->sortBy(fn (Rapor $rapor): string => $rapor->siswa->Nama)
            ->values();
    }

    /** @return Collection<int, Pembelajaran> */
    #[Computed]
    public function pembelajaran(): Collection
    {
        return Pembelajaran::query()
            ->where('Kelas_ID', $this->kelas->getKey())
            ->with(['mataPelajaran', 'guru'])
            ->get()
            ->filter(fn (Pembelajaran $p): bool => $p->mataPelajaran !== null)
            ->sortBy(fn (Pembelajaran $p): string => $p->mataPelajaran->Nama_Mapel)
            ->values();
    }

    /** @return array<string, int> */
    #[Computed]
    public function ringkasan(): array
    {
        $per = $this->rapor->countBy(fn (Rapor $rapor): string => $rapor->Status->value);

        return [
            'total' => $this->rapor->count(),
            'disetujui' => (int) ($per[RaporStatus::Disetujui->value] ?? 0),
            'menunggu' => (int) ($per[RaporStatus::Menunggu->value] ?? 0),
            'perlu_revisi' => (int) ($per[RaporStatus::PerluRevisi->value] ?? 0),
            'draft' => (int) ($per[RaporStatus::Draft->value] ?? 0) + (int) ($per[RaporStatus::BelumDiisi->value] ?? 0),
        ];
    }

    /**
     * How many students already have a narrative for each subject.
     *
     * @return array<string, int> Mapel_ID => students with a written narrative
     */
    #[Computed]
    public function narasiPerMapel(): array
    {
        return $this->rapor
            ->flatMap(fn (Rapor $rapor): Collection => $rapor->narasi->filter(fn (Narasi $n): bool => filled($n->teks())))
            ->countBy('Mapel_ID')
            ->all();
    }

    public function narasiTerisi(Rapor $rapor): int
    {
        $mapelIds = $this->pembelajaran->pluck('Mapel_ID');

        return $rapor->narasi
            ->filter(fn (Narasi $n): bool => $mapelIds->contains($n->Mapel_ID) && filled($n->teks()))
            ->count();
    }
};

?>

<div>
    <x-app.header
        :title="'Progres Rapor - '.$kelas->Nama_Kelas"
        :subtitle="'Dashboard Progres / '.$kelas->Nama_Kelas.' - Wali kelas: '.($kelas->waliKelas?->Nama ?? 'belum ditentukan')"
    >
        <x-slot:actions>
            <x-ui.button-link :href="route('dashboard.kepsekol')">Kembali</x-ui.button-link>
        </x-slot:actions>
    </x-app.header>

    <div class="flex-1 space-y-6 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-4 gap-5">
            <x-ui.card label="Rapor selesai (disetujui)" :value="$this->ringkasan['disetujui'].' / '.$this->ringkasan['total']" />
            <x-ui.card label="Menunggu persetujuan" :value="$this->ringkasan['menunggu']" />
            <x-ui.card label="Perlu revisi" :value="$this->ringkasan['perlu_revisi']" />
            <x-ui.card label="Draft / belum diisi" :value="$this->ringkasan['draft']" />
        </div>

        @if ($this->rapor->isEmpty())
            <p class="rounded border border-hairline bg-surface px-4 py-16 text-center text-[15px] text-muted">Data rapor belum tersedia untuk kelas ini.</p>
        @else
            <section class="rounded border border-hairline bg-surface p-6">
                <h2 class="text-[16px] font-semibold text-heading">Progres Pengisian per Mata Pelajaran</h2>
                <p class="mt-1 text-[14px] text-muted">Jumlah siswa yang narasinya sudah ditulis untuk tiap mata pelajaran.</p>

                <div class="mt-5 space-y-4">
                    @forelse ($this->pembelajaran as $p)
                        @php
                            $terisi = $this->narasiPerMapel[$p->Mapel_ID] ?? 0;
                            $total = $this->ringkasan['total'];
                        @endphp
                        <div class="grid grid-cols-[280px_1fr_120px] items-center gap-5">
                            <div class="leading-tight">
                                <p class="text-[15px] text-heading">{{ $p->mataPelajaran->Nama_Mapel }}</p>
                                <p class="text-[13px] text-muted">{{ $p->guru?->Nama ?? 'Belum ada guru pengampu' }}</p>
                            </div>
                            <x-ui.progress :value="$total > 0 ? round($terisi / $total * 100) : 0" width="w-full" />
                            <p class="text-right text-[14px] text-muted">{{ $terisi }} / {{ $total }} siswa</p>
                        </div>
                    @empty
                        <p class="text-[15px] text-muted">Kelas ini belum memiliki mata pelajaran yang ditugaskan.</p>
                    @endforelse
                </div>
            </section>

            <div class="overflow-hidden rounded border border-hairline bg-surface">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                            <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                            <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                            <th class="w-36 px-4 py-3 text-left font-medium">NISN</th>
                            <th class="w-36 px-4 py-3 text-left font-medium">Narasi Terisi</th>
                            <th class="w-40 px-4 py-3 text-left font-medium">Status Rapor</th>
                            <th class="w-44 px-4 py-3 text-left font-medium">Terakhir diperbarui</th>
                            <th class="w-28 px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->rapor as $rapor)
                            <tr wire:key="{{ $rapor->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                                <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->Nama }}</td>
                                <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->NISN }}</td>
                                <td class="px-4 py-3 text-[15px] text-body">{{ $this->narasiTerisi($rapor) }} / {{ $this->pembelajaran->count() }} mapel</td>
                                <td class="px-4 py-3"><x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->updated_at?->translatedFormat('j M Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($rapor->Status === RaporStatus::Menunggu)
                                        <x-ui.button-link size="sm" variant="primary" :href="route('review-rapor', ['rapor' => $rapor->getKey()])">Review</x-ui.button-link>
                                    @else
                                        <x-ui.button-link size="sm" :href="route('rapor.pratinjau', $rapor)">Lihat</x-ui.button-link>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="border-t border-hairline px-4 py-3 text-[14px] text-muted">Menampilkan {{ $this->rapor->count() }} dari {{ $this->rapor->count() }} siswa</p>
            </div>
        @endif
    </div>
</div>
