<?php

use App\Enums\JenisKelamin;
use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Layout('layouts::app', ['active' => 'siswa'])]
class extends \Livewire\Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cari = '';

    #[Url(as: 'kelas')]
    public string $filterKelas = '';

    #[Url(as: 'jk')]
    public string $filterJenisKelamin = '';

    public string $perHalaman = '7';

    public function updated(string $property): void
    {
        if ($property !== 'perHalaman') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->first();
    }

    #[Computed]
    public function totalSiswa(): int
    {
        return Siswa::query()->count();
    }

    /** @return Collection<int, Kelas> */
    #[Computed]
    public function daftarKelas(): Collection
    {
        return Kelas::query()
            ->when($this->periode !== null, fn ($query) => $query->where('TahunAjaran_ID', $this->periode->getKey()))
            ->orderBy('Nama_Kelas')
            ->get();
    }

    /** @return LengthAwarePaginator<int, Siswa> */
    #[Computed]
    public function siswa(): LengthAwarePaginator
    {
        $semester = $this->periode?->Semester_Aktif?->value;

        return Siswa::query()
            ->when($this->cari !== '', fn ($query) => $query
                ->where(fn ($q) => $q->where('Nama', 'like', '%'.$this->cari.'%')
                    ->orWhere('NISN', 'like', '%'.$this->cari.'%')))
            ->when($this->filterJenisKelamin !== '', fn ($query) => $query->where('Jenis_Kelamin', $this->filterJenisKelamin))
            ->when($this->filterKelas !== '', fn ($query) => $query->whereHas(
                'rapor',
                fn ($q) => $q->where('Kelas_ID', $this->filterKelas)->where('Semester', $semester),
            ))
            ->orderBy('Nama')
            ->paginate((int) $this->perHalaman);
    }

    #[Computed]
    public function kelasSaatIni(Siswa $siswa): ?Kelas
    {
        $semester = $this->periode?->Semester_Aktif?->value;

        $kelasId = $siswa->rapor()
            ->where('Semester', $semester)
            ->orderByDesc('Rapor_ID')
            ->value('Kelas_ID');

        return $kelasId === null ? null : Kelas::query()->find($kelasId);
    }

    #[Computed]
    public function statusRapor(Siswa $siswa): RaporStatus
    {
        return $siswa->rapor()
            ->where('Semester', $this->periode?->Semester_Aktif?->value)
            ->orderByDesc('Rapor_ID')
            ->value('Status') ?? RaporStatus::BelumDiisi;
    }

    public function importExcel(): void
    {
        // Not on ERD/CD: bulk Excel import is not part of the class diagram.
        session()->flash('galat', 'Import Excel belum diimplementasikan.');
    }

    public function tambahSiswa(): void
    {
        // Not on ERD/CD: the create/edit student form is a later screen.
        session()->flash('galat', 'Form tambah siswa belum diimplementasikan.');
    }
};

?>

<div>
    <x-app.header
        title="Data Siswa"
        :subtitle="$this->totalSiswa.' siswa terdaftar'"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" wire:click="importExcel">Import Excel</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="tambahSiswa">Tambah Siswa</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if (session('galat'))
        <div class="border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">{{ session('galat') }}</div>
    @endif

    <div class="flex shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8 py-4">
        <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama atau NISN..."
            class="h-10 w-[420px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">

        <select wire:model.live="filterKelas" class="h-10 w-[200px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Semua kelas</option>
            @foreach ($this->daftarKelas as $kelas)
                <option value="{{ $kelas->getKey() }}">{{ $kelas->Nama_Kelas }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterJenisKelamin" class="h-10 w-[200px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Semua jenis kelamin</option>
            @foreach (JenisKelamin::cases() as $jk)
                <option value="{{ $jk->value }}">{{ $jk->value }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-16 px-4 py-3 text-right font-medium">No</th>
                        <th class="w-32 px-4 py-3 text-left font-medium">NISN</th>
                        <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="w-16 px-4 py-3 text-left font-medium">L/P</th>
                        <th class="w-44 px-4 py-3 text-left font-medium">Kelas Saat Ini</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Status</th>
                        <th class="w-32 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->siswa as $siswa)
                        @php $kelas = $this->kelasSaatIni($siswa); @endphp
                        <tr wire:key="{{ $siswa->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $this->siswa->firstItem() + $loop->iteration - 1 }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $siswa->NISN }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $siswa->Nama }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $siswa->Jenis_Kelamin?->value }}</td>
                            <td class="px-4 py-3 text-[15px]">
                                @if ($kelas)
                                    <span class="text-body">{{ $kelas->Nama_Kelas }}</span>
                                @else
                                    <span class="text-muted">Belum ditempatkan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-ui.badge :status="$this->statusRapor($siswa)">{{ $this->statusRapor($siswa)->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.button type="button" variant="secondary" size="sm">Detail / Ubah</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[15px] text-muted">Tidak ada siswa yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($this->siswa->total() > 0)
                <div class="flex items-center justify-between border-t border-hairline px-4 py-3 text-[14px] text-muted">
                    <p>Menampilkan {{ $this->siswa->firstItem() }} dari {{ $this->siswa->total() }} siswa</p>

                    <div class="flex items-center gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="previousPage" :disabled="$this->siswa->onFirstPage()">Sebelumnya</x-ui.button>
                        @foreach ($this->siswa->getUrlRange(1, min(2, $this->siswa->lastPage())) as $page => $url)
                            <x-ui.button type="button" :variant="$page === $this->siswa->currentPage() ? 'primary' : 'secondary'" size="sm" wire:click="gotoPage({{ $page }})">Halaman {{ $page }}</x-ui.button>
                        @endforeach
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="nextPage" :disabled="$this->siswa->onLastPage()">Berikutnya</x-ui.button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
