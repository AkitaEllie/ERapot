<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

new #[Layout('layouts::app', ['active' => 'penempatan'])]
class extends \Livewire\Component
{
    #[Url(as: 'kelas')]
    public string $kelasTujuan = '';

    public string $cari = '';

    /** @var array<int, string> Siswa_ID */
    public array $dipilih = [];

    /**
     * Staged placement changes, applied when the operator saves.
     *
     * @var array<string, array{aksi: string, kelas: ?string}> Siswa_ID => operation
     */
    public array $pennettakan = [];

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->first();
    }

    #[Computed]
    public function semester(): string
    {
        return $this->periode?->Semester_Aktif?->value ?? 'ganjil';
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

    public function kelasTujuan(): ?Kelas
    {
        return $this->daftarKelas->firstWhere('getKey', $this->kelasTujuan) ?? $this->daftarKelas->first();
    }

    /** @return Collection<int, Siswa> */
    #[Computed]
    public function belumDitempatkan(): Collection
    {
        $semester = $this->semester;

        return Siswa::belumDitempatkan(Semester::from($semester))
            ->when($this->cari !== '', fn ($query) => $query
                ->where(fn ($q) => $q->where('Nama', 'like', '%'.$this->cari.'%')
                    ->orWhere('NISN', 'like', '%'.$this->cari.'%')))
            ->orderBy('Nama')
            ->get();
    }

    /** @return Collection<int, Siswa> */
    #[Computed]
    public function siswaDiKelas(): Collection
    {
        $kelas = $this->kelasTujuan();

        if ($kelas === null) {
            return collect();
        }

        $semester = $this->semester;

        return Rapor::query()
            ->where('Kelas_ID', $kelas->getKey())
            ->where('Semester', $semester)
            ->with('siswa')
            ->orderBy('Rapor_ID')
            ->get()
            ->pluck('siswa')
            ->filter()
            ->values();
    }

    public function jumlahSiswa(Kelas $kelas): int
    {
        return Rapor::query()
            ->where('Kelas_ID', $kelas->getKey())
            ->where('Semester', $this->semester)
            ->count();
    }

    public function toggle(string $siswaId): void
    {
        $this->dipilih = in_array($siswaId, $this->dipilih, true)
            ? array_values(array_diff($this->dipilih, [$siswaId]))
            : [...$this->dipilih, $siswaId];
    }

    public function pindahkan(): void
    {
        $kelas = $this->kelasTujuan();

        if ($kelas === null || $this->dipilih === []) {
            return;
        }

        foreach ($this->dipilih as $siswaId) {
            $this->pennettakan[$siswaId] = ['aksi' => 'masuk', 'kelas' => $kelas->getKey()];
        }

        $this->reset('dipilih');
    }

    public function keluarkan(string $siswaId): void
    {
        $this->pennettakan[$siswaId] = ['aksi' => 'keluar', 'kelas' => null];
    }

    public function batalkan(): void
    {
        $this->reset('pennettakan', 'dipilih');
    }

    public function simpan(): void
    {
        $semester = $this->semester;

        foreach ($this->pennettakan as $siswaId => $operasi) {
            if ($operasi['aksi'] === 'masuk' && $operasi['kelas'] !== null) {
                // The design states that saving a placement creates an empty report draft
                // for the running semester, which is exactly Rapor::pempatkan().
                Rapor::pempatkan(
                    Siswa::query()->findOrFail($siswaId),
                    Kelas::query()->findOrFail($operasi['kelas']),
                    Semester::from($semester),
                );
            } else {
                Rapor::query()
                    ->where('Siswa_ID', $siswaId)
                    ->where('Semester', $semester)
                    ->delete();
            }
        }

        $jumlah = count($this->pennettakan);
        $this->reset('pennettakan', 'dipilih');

        session()->flash('sukses', $jumlah.' penempatan disimpan. Draf rapor dibuat untuk semester berjalan.');
    }
};

?>

<div>
    <x-app.header
        title="Penempatan Siswa ke Kelas"
        :subtitle="($this->periode?->Tahun_Ajaran ?? '-').' - Semester '.($this->periode?->Semester_Aktif?->label() ?? '-')"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="simpan" @disabled($pennettakan === [])>Simpan Penempatan</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if (session('sukses'))
        <div class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
    @endif

    <div class="border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">
        Menyimpan penempatan akan otomatis membuat draf rapor kosong untuk setiap siswa pada semester berjalan
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="mb-5 flex items-center gap-4">
            <label class="flex items-center gap-3">
                <span class="text-[15px] text-body">Kelas Tujuan</span>
                <select wire:model.live="kelasTujuan" class="h-10 w-[320px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                    @foreach ($this->daftarKelas as $kelas)
                        <option value="{{ $kelas->getKey() }}">{{ $kelas->Nama_Kelas }} ({{ $this->jumlahSiswa($kelas) }} siswa)</option>
                    @endforeach
                </select>
            </label>

            @if ($pennettakan !== [])
                <span class="text-[14px] text-muted">{{ count($pennettakan) }} perubahan belum disimpan</span>
                <x-ui.button type="button" variant="ghost" size="sm" wire:click="batalkan">Batalkan</x-ui.button>
            @endif
        </div>

        <div class="grid grid-cols-[552px_144px_384px] items-start gap-6">
            {{-- Unplaced students --}}
            <section class="overflow-hidden rounded border border-hairline bg-surface">
                <header class="flex h-[44px] items-center justify-between bg-canvas px-4">
                    <h2 class="text-[15px] font-semibold text-heading">Belum Ditempatkan ({{ $this->belumDitempatkan->count() }})</h2>
                </header>

                <div class="border-b border-hairline p-4">
                    <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama siswa..."
                        class="h-9 w-full rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                </div>

                <ul class="max-h-[480px] divide-y divide-hairline overflow-y-auto">
                    @forelse ($this->belumDitempatkan as $siswa)
                        <li>
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-canvas">
                                <input type="checkbox" class="size-4 rounded-sm border-sunken-strong text-accent-deep focus:ring-accent"
                                    :checked="in_array($siswa->getKey(), $dipilih)"
                                    wire:click="toggle('{{ $siswa->getKey() }}')">
                                <span class="flex-1 leading-tight">
                                    <span class="block text-[15px] text-body">{{ $siswa->Nama }}</span>
                                    <span class="block text-[13px] text-muted">NISN {{ $siswa->NISN }}</span>
                                </span>
                            </label>
                        </li>
                    @empty
                        <li class="px-4 py-12 text-center text-[15px] text-muted">Semua siswa sudah ditempatkan.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Transfer controls --}}
            <div class="flex flex-col items-center gap-4 pt-40">
                <x-ui.button type="button" variant="secondary" class="w-[144px] justify-center" wire:click="pindahkan" @disabled($dipilih === [])>Pindahkan &gt;</x-ui.button>
                <x-ui.button type="button" variant="secondary" class="w-[144px] justify-center" disabled>&lt; Keluarkan</x-ui.button>
            </div>

            {{-- Students in the target class --}}
            <section class="overflow-hidden rounded border border-hairline bg-surface">
                <header class="flex h-[44px] items-center justify-between bg-canvas px-4">
                    <h2 class="text-[15px] font-semibold text-heading">
                        {{ $this->kelasTujuan()?->Nama_Kelas ?? '-' }} ({{ $this->siswaDiKelas->count() }})
                    </h2>
                </header>

                <ul class="max-h-[536px] divide-y divide-hairline overflow-y-auto">
                    @forelse ($this->siswaDiKelas as $siswa)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="w-5 text-right text-[14px] text-muted">{{ $loop->iteration }}.</span>
                            <span class="flex-1 text-[15px] text-body">{{ $siswa->Nama }}</span>
                            <x-ui.button type="button" variant="ghost" size="sm" wire:click="keluarkan('{{ $siswa->getKey() }}')">Hapus</x-ui.button>
                        </li>
                    @empty
                        <li class="px-4 py-12 text-center text-[15px] text-muted">Kelas ini belum memiliki siswa.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</div>
