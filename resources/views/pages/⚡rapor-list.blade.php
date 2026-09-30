<?php

use App\Enums\RaporStatus;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Layout('layouts::app', ['active' => 'rapor'])]
class extends \Livewire\Component
{
    use WithPagination;

    #[Url(as: 'kelas')]
    public string $filterKelas = '';

    #[Url(as: 'mapel')]
    public string $filterMapel = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'q')]
    public string $cari = '';

    public string $perHalaman = '7';

    protected function queryString(): array
    {
        return ['cari', 'perHalaman'];
    }

    public function updated(string $property): void
    {
        if ($property !== 'perHalaman') {
            $this->resetPage();
        }
    }

    /** @return Collection<int, Kelas> */
    #[Computed]
    public function kelasDiaampu(): Collection
    {
        return Kelas::query()
            ->where('User_ID', auth()->id())
            ->with('tahunAjaran')
            ->orderBy('Nama_Kelas')
            ->get();
    }

    public function kelasTerpilih(): ?Kelas
    {
        return $this->kelasDiaampu->firstWhere('getKey', $this->filterKelas)
            ?? $this->kelasDiaampu->first();
    }

    /** @return Collection<int, MataPelajaran> */
    #[Computed]
    public function mapelKelas(): Collection
    {
        $kelas = $this->kelasTerpilih();

        if ($kelas === null) {
            return collect();
        }

        return Pembelajaran::query()
            ->where('Kelas_ID', $kelas->getKey())
            ->with('mataPelajaran')
            ->get()
            ->pluck('mataPelajaran')
            ->filter()
            ->unique('Mapel_ID')
            ->values();
    }

    public function mapelTerpilih(): ?MataPelajaran
    {
        return $this->mapelKelas->firstWhere('getKey', $this->filterMapel) ?? $this->mapelKelas->first();
    }

    #[Computed]
    public function totalIndikator(): int
    {
        $mapel = $this->mapelTerpilih();

        if ($mapel === null) {
            return 0;
        }

        $programIds = ProgramPengembangan::query()->where('Mapel_ID', $mapel->getKey())->pluck('Program_ID');

        return IndikatorCapaian::query()->whereIn('Program_ID', $programIds)->count();
    }

    /** @return LengthAwarePaginator<int, Rapor> */
    #[Computed]
    public function rapors(): LengthAwarePaginator
    {
        $kelas = $this->kelasTerpilih();
        $mapel = $this->mapelTerpilih();

        if ($kelas === null) {
            return new LengthAwarePaginator([], 0, (int) $this->perHalaman);
        }

        return Rapor::query()
            ->where('Kelas_ID', $kelas->getKey())
            ->with('siswa')
            ->when($this->filterStatus !== '', fn ($query) => $query->where('Status', $this->filterStatus))
            ->when($mapel !== null, fn ($query) => $query->with([
                'narasi' => fn ($q) => $q
                    ->where('Mapel_ID', $mapel->getKey())
                    ->with(['nilaiSiswa', 'dokumentasi']),
            ]))
            ->when($this->cari !== '', function ($query): void {
                $query->whereHas('siswa', fn ($q) => $q
                    ->where('Nama', 'like', '%'.$this->cari.'%')
                    ->orWhere('NISN', 'like', '%'.$this->cari.'%'));
            })
            ->orderBy('Siswa_ID')
            ->paginate((int) $this->perHalaman);
    }

    #[Computed]
    public function nilaiTerisi(Rapor $rapor): int
    {
        $mapel = $this->mapelTerpilih();

        if ($mapel === null) {
            return 0;
        }

        return Narasi::query()
            ->where('Rapor_ID', $rapor->getKey())
            ->where('Mapel_ID', $mapel->getKey())
            ->withCount('nilaiSiswa')
            ->first()
            ?->nilai_siswa_count
            ?? 0;
    }

    public function isiRapor(string $raporId): void
    {
        $mapel = $this->mapelTerpilih();

        if ($mapel === null) {
            return;
        }

        $rapor = Rapor::query()->findOrFail($raporId);

        $this->redirect(route('rapor.input', ['rapor' => $rapor->getKey(), 'mapel' => $mapel->getKey()]), navigate: true);
    }
};

?>

<div>
    <x-app.header
        title="Rapor Siswa"
        :subtitle="$this->kelasTerpilih()?->Nama_Kelas ?? 'Belum ada kelas yang diampu'"
    />

    {{-- Filter panel --}}
    <div class="flex shrink-0 flex-wrap items-end gap-4 border-b border-hairline bg-surface px-8 py-4">
        <label class="flex flex-col gap-1">
            <span class="text-[13px] text-muted">Kelas</span>
            <select wire:model.live="filterKelas" class="h-9 w-[220px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                @foreach ($this->kelasDiaampu as $kelas)
                    <option value="{{ $kelas->getKey() }}">{{ $kelas->Nama_Kelas }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[13px] text-muted">Mata Pelajaran</span>
            <select wire:model.live="filterMapel" class="h-9 w-[260px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                @foreach ($this->mapelKelas as $mapel)
                    <option value="{{ $mapel->getKey() }}">{{ $mapel->Nama_Mapel }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[13px] text-muted">Status</span>
            <select wire:model.live="filterStatus" class="h-9 w-[180px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                <option value="">Semua status</option>
                @foreach (RaporStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-1 flex-col gap-1">
            <span class="text-[13px] text-muted">Cari</span>
            <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama siswa..."
                class="h-9 max-w-[280px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
        </label>
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-16 px-4 py-3 text-right font-medium">No</th>
                        <th class="w-32 px-4 py-3 text-left font-medium">NISN</th>
                        <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="w-32 px-4 py-3 text-left font-medium">Nilai Terisi</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Status Rapor</th>
                        <th class="w-40 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rapors as $rapor)
                        @php $terisi = $this->nilaiTerisi($rapor); $total = $this->totalIndikator; @endphp
                        <tr wire:key="{{ $rapor->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $this->rapors->firstItem() + $loop->iteration - 1 }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->NISN }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->siswa->Nama }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $terisi }} / {{ $total }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.button type="button" variant="secondary" size="sm" wire:click="isiRapor('{{ $rapor->getKey() }}')">Isi Rapor</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-[15px] text-muted">Belum ada siswa pada kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($this->rapors->total() > 0)
                <div class="flex items-center justify-between border-t border-hairline px-4 py-3 text-[14px] text-muted">
                    <p>Menampilkan {{ $this->rapors->firstItem() }} dari {{ $this->rapors->total() }} siswa</p>

                    <div class="flex items-center gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="previousPage" :disabled="$this->rapors->onFirstPage()">Sebelumnya</x-ui.button>
                        @foreach ($this->rapors->getUrlRange(1, min(2, $this->rapors->lastPage())) as $page => $url)
                            <x-ui.button type="button" :variant="$page === $this->rapors->currentPage() ? 'primary' : 'secondary'" size="sm" wire:click="gotoPage({{ $page }})">Halaman {{ $page }}</x-ui.button>
                        @endforeach
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="nextPage" :disabled="$this->rapors->onLastPage()">Berikutnya</x-ui.button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
