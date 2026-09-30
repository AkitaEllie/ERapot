<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['active' => 'dashboard'])]
#[Title('Dashboard')]
class extends Component
{
    public string $Kelas_ID = '';

    /**
     * The school year the teacher is working in.
     */
    #[Computed]
    public function tahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->active()
            ->orderByDesc('Tanggal_Mulai')
            ->first();
    }

    /**
     * Every class this teacher teaches, with its subject and report progress.
     *
     * @return Collection<int, array{kelas: Kelas, mapel: ?string, siswa: int, disetujui: int, total: int}>
     */
    #[Computed]
    public function kelasDiampu(): Collection
    {
        $user = auth()->user();

        return Kelas::query()
            ->where('User_ID', $user->getKey())
            ->when($this->tahunAjaran, fn ($query) => $query->where('TahunAjaran_ID', $this->tahunAjaran->getKey()))
            ->withCount([
                'rapor as disetujui' => fn ($query) => $query->where('Status', RaporStatus::Disetujui->value),
            ])
            ->withCount('rapor')
            ->with(['pembelajaran.mataPelajaran'])
            ->get()
            ->map(fn (Kelas $kelas): array => [
                'kelas' => $kelas,
                'mapel' => $kelas->pembelajaran->first()?->mataPelajaran?->Nama_Mapel,
                'siswa' => $kelas->rapor()->distinct('Siswa_ID')->count('Siswa_ID'),
                'disetujui' => (int) $kelas->disetujui,
                'total' => (int) $kelas->rapor_count,
            ]);
    }

    /**
     * Report cards sent back for revision that this teacher still owns.
     *
     * @return Collection<int, Rapor>
     */
    #[Computed]
    public function perluDitindaklanjuti(): Collection
    {
        $kelasIds = auth()->user()->kelasDiampu()->pluck('Kelas_ID');

        return Rapor::query()
            ->whereIn('Kelas_ID', $kelasIds)
            ->denganStatus(RaporStatus::PerluRevisi)
            ->with(['siswa', 'kelas', 'narasi.mataPelajaran'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function ringkasan(): array
    {
        $kelasIds = auth()->user()->kelasDiampu()->pluck('Kelas_ID');
        $rapor = Rapor::query()->whereIn('Kelas_ID', $kelasIds);

        return [
            'kelas' => $kelasIds->count(),
            'siswa' => Rapor::query()->whereIn('Kelas_ID', $kelasIds)->distinct()->count('Siswa_ID'),
            'disetujui' => (clone $rapor)->denganStatus(RaporStatus::Disetujui)->count(),
            'menunggu' => (clone $rapor)->denganStatus(RaporStatus::Menunggu)->count(),
        ];
    }

    public function with(): array
    {
        return ['semester' => Semester::Ganjil];
    }
};
?>

<div>
    <x-app.header
            title="Dashboard"
            :subtitle="$this->tahunAjaran?->Tahun_Ajaran.' - Semester '.($this->tahunAjaran?->Semester_Aktif->label() ?? '-')"
        />

        <div class="flex-1 px-8 py-6">
            <div class="grid grid-cols-4 gap-7">
                @foreach ([
                    'Kelas diampu' => $this->ringkasan['kelas'],
                    'Siswa dinilai' => $this->ringkasan['siswa'],
                    'Rapor selesai' => $this->ringkasan['disetujui'],
                    'Menunggu persetujuan' => $this->ringkasan['menunggu'],
                ] as $label => $value)
                    <x-ui.card>
                        <p class="text-[15px] text-muted">{{ $label }}</p>
                        <p class="mt-1 text-[34px] leading-none font-semibold text-heading">{{ $value }}</p>
                    </x-ui.card>
                @endforeach
            </div>

            <h2 class="mt-8 mb-3 text-[19px] font-semibold text-heading">Kelas yang Diampu</h2>

            <x-ui.card :padded="false">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-hairline">
                                <th class="px-4 py-3.5 text-[15px] font-semibold text-label">Kelas</th>
                                <th class="px-4 py-3.5 text-[15px] font-semibold text-label">Mata Pelajaran</th>
                                <th class="px-4 py-3.5 text-[15px] font-semibold text-label">Jumlah Siswa</th>
                                <th class="px-4 py-3.5 text-[15px] font-semibold text-label">Progres Pengisian</th>
                                <th class="px-4 py-3.5 text-[15px] font-semibold text-label">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->kelasDiampu as $row)
                                @php $persen = $row['total'] > 0 ? (int) round($row['disetujui'] / $row['total'] * 100) : 0; @endphp
                                <tr wire:key="{{ $row['kelas']->getKey() }}" class="border-b border-hairline last:border-0">
                                    <td class="px-4 py-4 text-[16px] text-body">{{ $row['kelas']->Nama_Kelas }}</td>
                                    <td class="px-4 py-4 text-[16px] text-body">{{ $row['mapel'] ?? '-' }}</td>
                                    <td class="px-4 py-4 text-[16px] text-body">{{ $row['siswa'] }} siswa</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-ui.progress :value="$persen" />
                                            <span class="text-[13px] whitespace-nowrap text-muted">{{ $row['disetujui'] }} / {{ $row['total'] }} rapor</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-ui.button size="sm" variant="secondary">Isi Rapor</x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-[15px] text-muted">
                                        Belum ada kelas yang diampu.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            @if ($this->perluDitindaklanjuti->isNotEmpty())
                <h2 class="mt-8 mb-3 text-[19px] font-semibold text-heading">Perlu Ditindaklanjuti</h2>

                <x-ui.card>
                    <div class="space-y-6">
                        @foreach ($this->perluDitindaklanjuti as $rapor)
                            <div wire:key="{{ $rapor->getKey() }}" class="flex items-start gap-6">
                                <span class="mt-0.5 h-full min-h-9 w-2 shrink-0 rounded bg-sunken"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[16px] font-semibold text-heading">Rapor dikembalikan Kepala Sekolah</p>
                                    <p class="mt-1 text-[15px] text-muted">
                                        {{ $rapor->siswa?->Nama }} - {{ $rapor->kelas?->Nama_Kelas }}
                                        - {{ $rapor->narasi->first()?->mataPelajaran?->Nama_Mapel ?? 'Narasi' }}
                                        perlu diperbaiki: {{ $rapor->Catatan_Revisi }}
                                    </p>
                                </div>
                                <x-ui.button variant="secondary">Buka Rapor</x-ui.button>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </div>
</div>
