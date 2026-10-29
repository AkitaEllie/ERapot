<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Guru: every report of a class with its approval state, and the way out to
 * PDF, wireframe G-04.
 *
 * Export stays locked until the principal approves a report, which is the
 * alternative flow of the "Meng-export Rapor" use case.
 */
new #[Layout('layouts::app', ['active' => 'ekspor'])]
class extends Component
{
    use WithPagination;

    #[Url(as: 'kelas')]
    public string $filterKelas = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'q')]
    public string $cari = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->active()->first();
    }

    /**
     * Classes the teacher holds as homeroom teacher, then the ones they teach a
     * subject in.
     *
     * @return Collection<int, Kelas>
     */
    #[Computed]
    public function daftarKelas(): Collection
    {
        $userId = auth()->id();

        $mengajar = Pembelajaran::query()->where('User_ID', $userId)->select('Kelas_ID');

        return Kelas::query()
            ->where(fn ($query) => $query->where('User_ID', $userId)->orWhereIn('Kelas_ID', $mengajar))
            ->when($this->periode, fn ($query) => $query->where('TahunAjaran_ID', $this->periode->getKey()))
            ->orderByRaw('CASE WHEN User_ID = ? THEN 0 ELSE 1 END', [$userId])
            ->orderBy('Nama_Kelas')
            ->get();
    }

    #[Computed]
    public function kelas(): ?Kelas
    {
        return $this->daftarKelas->firstWhere('Kelas_ID', $this->filterKelas) ?? $this->daftarKelas->first();
    }

    /** @return array<string, int> */
    #[Computed]
    public function ringkasan(): array
    {
        $jumlah = Rapor::query()
            ->where('Kelas_ID', $this->kelas?->getKey())
            ->when($this->periode, fn ($query) => $query->where('Semester', $this->periode->Semester_Aktif))
            ->selectRaw('Status, count(*) as jumlah')
            ->groupBy('Status')
            ->pluck('jumlah', 'Status');

        $hitung = fn (RaporStatus ...$status): int => (int) collect($status)->sum(fn (RaporStatus $s): int => (int) ($jumlah[$s->value] ?? 0));

        return [
            'disetujui' => $hitung(RaporStatus::Disetujui),
            'menunggu' => $hitung(RaporStatus::Menunggu),
            'perlu_revisi' => $hitung(RaporStatus::PerluRevisi),
            'draft' => $hitung(RaporStatus::Draft, RaporStatus::BelumDiisi),
        ];
    }

    /** @return LengthAwarePaginator<int, Rapor> */
    #[Computed]
    public function rapors(): LengthAwarePaginator
    {
        $kelas = $this->kelas;

        if ($kelas === null) {
            return new LengthAwarePaginator([], 0, 10);
        }

        return Rapor::query()
            ->where('Kelas_ID', $kelas->getKey())
            ->when($this->periode, fn ($query) => $query->where('Semester', $this->periode->Semester_Aktif))
            ->with('siswa')
            ->when($this->filterStatus !== '', fn ($query) => $query->where('Status', $this->filterStatus))
            ->when($this->cari !== '', fn ($query) => $query->whereHas('siswa', fn ($q) => $q
                ->where('Nama', 'like', '%'.$this->cari.'%')
                ->orWhere('NISN', 'like', '%'.$this->cari.'%')))
            ->orderBy(
                \App\Models\Siswa::query()->select('Nama')->whereColumn('siswas.Siswa_ID', 'rapors.Siswa_ID')->limit(1)
            )
            ->paginate(10);
    }
};

?>

<div>
    <x-app.header
        title="Daftar Rapor & Export"
        :subtitle="$this->kelas ? $this->kelas->Nama_Kelas.' - Semester '.($this->periode?->Semester_Aktif?->label() ?? '-').' '.($this->periode?->Tahun_Ajaran ?? '') : 'Belum ada kelas yang diampu'"
    />

    <x-ui.flash />

    <div class="flex-1 space-y-5 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-4 gap-5">
            <x-ui.card label="Disetujui" :value="$this->ringkasan['disetujui']" />
            <x-ui.card label="Menunggu persetujuan" :value="$this->ringkasan['menunggu']" />
            <x-ui.card label="Perlu revisi" :value="$this->ringkasan['perlu_revisi']" />
            <x-ui.card label="Draft / belum diisi" :value="$this->ringkasan['draft']" />
        </div>

        <div class="flex items-center gap-4">
            <div class="w-[220px]">
                <x-ui.select wire:model.live="filterKelas" aria-label="Kelas">
                    @foreach ($this->daftarKelas as $item)
                        <option value="{{ $item->getKey() }}">{{ $item->Nama_Kelas }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="w-[200px]">
                <x-ui.select wire:model.live="filterStatus" aria-label="Status rapor">
                    <option value="">Semua status</option>
                    @foreach (RaporStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="w-[320px]"><x-ui.input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama siswa..." aria-label="Cari siswa" /></div>
        </div>

        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                        <th class="w-36 px-4 py-3 text-left font-medium">NISN</th>
                        <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Status Rapor</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Disetujui pada</th>
                        <th class="w-56 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rapors as $rapor)
                        @php $siap = $rapor->Status === RaporStatus::Disetujui; @endphp
                        <tr wire:key="{{ $rapor->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $this->rapors->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->NISN }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->Nama }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->Tanggal_Persetujuan?->translatedFormat('j M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button-link size="sm" :href="route('rapor.pratinjau', $rapor)">Pratinjau</x-ui.button-link>
                                    <x-ui.button-link size="sm" variant="primary" :href="route('rapor.unduh', $rapor)" :navigate="false" :disabled="! $siap"
                                        title="{{ $siap ? 'Unduh rapor dalam format PDF' : 'Rapor belum disetujui Kepala Sekolah' }}">Export PDF</x-ui.button-link>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-[15px] text-muted">Belum ada rapor yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-ui.table-footer :paginator="$this->rapors" satuan="siswa" />
        </div>

        <div class="flex items-center gap-3 rounded border border-hairline-strong bg-notice-bg px-4 py-3 text-[14px] text-label">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.2M10 13.4h.01" stroke-linecap="round"/></svg>
            Tombol Export PDF hanya aktif untuk rapor yang sudah disetujui Kepala Sekolah.
        </div>
    </div>
</div>
