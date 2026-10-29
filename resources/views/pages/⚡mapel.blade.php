<?php

use App\Enums\JenisMataPelajaran;
use App\Models\MataPelajaran;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tata Usaha: the subjects a report can carry, wireframe TU-06.
 *
 * Jenis_Mapel decides where a subject lands in the report book and how its
 * narrative is written, so the form explains that choice rather than leaving it
 * as a bare dropdown. Programmes and indicators are the teacher's job, on the
 * Indikator Penilaian screen.
 */
new #[Layout('layouts::app', ['active' => 'mapel'])]
class extends Component
{
    #[Url(as: 'q')]
    public string $cari = '';

    #[Url(as: 'jenis')]
    public string $filterJenis = '';

    public ?string $sedangDiubah = null;

    public string $nama = '';

    public string $jenis = 'intrakurikuler';

    public bool $tampilkanIndikator = true;

    /** @return Collection<int, MataPelajaran> */
    #[Computed]
    public function daftar(): Collection
    {
        return MataPelajaran::query()
            ->withCount(['programPengembangan', 'pembelajaran', 'narasi'])
            ->when($this->cari !== '', fn ($query) => $query->where('Nama_Mapel', 'like', '%'.$this->cari.'%'))
            ->when($this->filterJenis !== '', fn ($query) => $query->where('Jenis_Mapel', $this->filterJenis))
            ->orderBy('Jenis_Mapel')
            ->orderBy('Nama_Mapel')
            ->get();
    }

    #[Computed]
    public function total(): int
    {
        return MataPelajaran::query()->count();
    }

    public function ubah(string $id): void
    {
        $mapel = MataPelajaran::query()->findOrFail($id);

        $this->sedangDiubah = $mapel->getKey();
        $this->nama = $mapel->Nama_Mapel;
        $this->jenis = $mapel->Jenis_Mapel->value;
        $this->tampilkanIndikator = $mapel->Tampilkan_Indikator;
        $this->resetValidation();
    }

    public function batal(): void
    {
        $this->reset('sedangDiubah', 'nama', 'jenis', 'tampilkanIndikator');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => [
                'required', 'string', 'max:80',
                Rule::unique('mata_pelajarans', 'Nama_Mapel')->ignore($this->sedangDiubah, 'Mapel_ID'),
            ],
            'jenis' => ['required', Rule::enum(JenisMataPelajaran::class)],
            'tampilkanIndikator' => ['boolean'],
        ], [
            'nama.unique' => 'Data sudah terdaftar: mata pelajaran ini sudah ada.',
        ], [
            'nama' => 'nama mata pelajaran',
        ]);

        $mapel = $this->sedangDiubah !== null
            ? MataPelajaran::query()->findOrFail($this->sedangDiubah)
            : new MataPelajaran;

        $mapel->fill([
            'Nama_Mapel' => $data['nama'],
            'Jenis_Mapel' => $data['jenis'],
            'Tampilkan_Indikator' => $data['tampilkanIndikator'],
        ])->save();

        session()->flash('sukses', 'Data berhasil disimpan.');
        $this->batal();
        unset($this->daftar);
    }

    public function hapus(string $id): void
    {
        $mapel = MataPelajaran::query()->withCount(['pembelajaran', 'narasi'])->findOrFail($id);

        if ($mapel->pembelajaran_count > 0 || $mapel->narasi_count > 0) {
            session()->flash('galat', $mapel->Nama_Mapel.' sudah dipakai pada penugasan mengajar atau rapor, sehingga tidak dapat dihapus.');

            return;
        }

        $mapel->programPengembangan()->each(function ($program): void {
            $program->indikatorCapaian()->delete();
            $program->delete();
        });
        $mapel->delete();

        session()->flash('sukses', $mapel->Nama_Mapel.' dihapus.');
        unset($this->daftar);
    }
};

?>

<div>
    <x-app.header title="Mata Pelajaran" :subtitle="$this->total.' mata pelajaran terdaftar'">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="batal" x-on:click="$nextTick(() => document.getElementById('nama-mapel')?.focus())">Tambah Mata Pelajaran</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="flex shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8 py-4">
        <div class="w-[420px]"><x-ui.input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari mata pelajaran..." aria-label="Cari mata pelajaran" /></div>
        <div class="w-[220px]">
            <x-ui.select wire:model.live="filterJenis" aria-label="Filter jenis">
                <option value="">Semua jenis</option>
                @foreach (JenisMataPelajaran::cases() as $pilihan)
                    <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div class="flex-1 space-y-5 overflow-y-auto bg-canvas px-8 py-6">
        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                        <th class="px-4 py-3 text-left font-medium">Nama Mata Pelajaran</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Jenis</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Tampil di Rapor</th>
                        <th class="w-28 px-4 py-3 text-left font-medium">Program</th>
                        <th class="w-28 px-4 py-3 text-left font-medium">Dipakai</th>
                        <th class="w-44 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->daftar as $mapel)
                        @php $dipakai = $mapel->pembelajaran_count > 0 || $mapel->narasi_count > 0; @endphp
                        <tr wire:key="{{ $mapel->getKey() }}" @class(['border-b border-hairline last:border-b-0', 'bg-accent-soft/40' => $sedangDiubah === $mapel->getKey()])>
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-[15px] font-medium text-heading">{{ $mapel->Nama_Mapel }}</td>
                            <td class="px-4 py-3"><x-ui.badge>{{ $mapel->Jenis_Mapel->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $mapel->Tampilkan_Indikator ? 'Ya' : 'Tidak' }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $mapel->program_pengembangan_count }} program</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $mapel->pembelajaran_count }} kelas</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $mapel->getKey() }}')">Ubah</x-ui.button>
                                    <x-ui.button type="button" variant="secondary" size="sm"
                                        wire:click="hapus('{{ $mapel->getKey() }}')"
                                        wire:confirm="Hapus {{ $mapel->Nama_Mapel }} beserta program dan indikatornya?"
                                        :disabled="$dipakai"
                                        title="{{ $dipakai ? 'Mata pelajaran yang sudah dipakai tidak dapat dihapus' : '' }}"
                                    >Hapus</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[15px] text-muted">Tidak ada mata pelajaran yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($this->daftar->isNotEmpty())
                <p class="border-t border-hairline px-4 py-3 text-[14px] text-muted">Menampilkan {{ $this->daftar->count() }} dari {{ $this->total }} mata pelajaran</p>
            @endif
        </div>

        <form wire:submit="simpan" class="rounded border border-hairline bg-surface p-6">
            <h2 class="text-[16px] font-semibold text-heading">{{ $sedangDiubah ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</h2>
            <p class="mt-1 text-[14px] text-muted">Program pengembangan dan indikator capaian diisi oleh guru pengampu melalui menu Indikator Penilaian.</p>

            <div class="mt-5 grid grid-cols-[1fr_260px] gap-4">
                <x-ui.field label="Nama Mata Pelajaran" for="nama-mapel" error="nama">
                    <x-ui.input id="nama-mapel" wire:model="nama" placeholder="Bahasa Inggris" :invalid="$errors->has('nama')" />
                </x-ui.field>

                <x-ui.field label="Jenis" for="jenis" error="jenis">
                    <x-ui.select id="jenis" wire:model.live="jenis">
                        @foreach (JenisMataPelajaran::cases() as $pilihan)
                            <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
            </div>

            <p class="mt-2 text-[13px] text-muted">
                @switch($jenis)
                    @case('kokurikuler') Masuk Bagian B rapor (projek), narasi dua sampai tiga paragraf. @break
                    @case('muatan_lokal') Mendapat halaman tabel indikator sendiri dengan kotak catatan satu paragraf. @break
                    @default Masuk Bagian A rapor, narasi dua sampai tiga paragraf beserta foto dokumentasi.
                @endswitch
            </p>

            <label class="mt-4 flex items-center gap-2.5 text-[15px] text-body">
                <input type="checkbox" wire:model="tampilkanIndikator" class="size-4 rounded-sm border-sunken-strong text-accent-deep focus:ring-accent">
                Tampilkan mata pelajaran ini pada dokumen rapor
            </label>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" wire:click="batal">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </div>
        </form>
    </div>
</div>
