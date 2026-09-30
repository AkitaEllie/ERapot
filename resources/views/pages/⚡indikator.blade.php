<?php

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\MataPelajaran;
use App\Models\ProgramPengembangan;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

new #[Layout('layouts::app', ['active' => 'indikator'])]
class extends \Livewire\Component
{
    #[Url(as: 'mapel')]
    public string $filterMapel = '';

    #[Url(as: 'jenjang')]
    public string $filterJenjang = '';

    #[Url(as: 'semester')]
    public string $filterSemester = '';

    #[Url(as: 'tipe')]
    public string $filterTipe = '';

    public bool $formTerbuka = false;

    /** Indikator_ID being created or edited, or null when the form is closed. */
    public ?string $formIndikator = null;

    public ?string $formProgram = null;

    public string $formDeskripsi = '';

    public string $formTipe = '';

    #[Computed]
    public function periodeAktif(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->first();
    }

    /** @return Collection<int, MataPelajaran> */
    #[Computed]
    public function daftarMapel(): Collection
    {
        return MataPelajaran::query()
            ->where('Tampilkan_Indikator', true)
            ->orderBy('Nama_Mapel')
            ->get();
    }

    /** @return Collection<int, ProgramPengembangan> */
    #[Computed]
    public function program(): Collection
    {
        $tahunAjaran = $this->periodeAktif;

        if ($tahunAjaran === null) {
            return collect();
        }

        return ProgramPengembangan::query()
            ->when($this->filterMapel !== '', fn ($query) => $query->where('Mapel_ID', $this->filterMapel))
            ->whereHas('indikatorCapaian', fn ($query) => $query->where('TahunAjaran_ID', $tahunAjaran->getKey()))
            ->with(['mataPelajaran', 'indikatorCapaian' => function ($query) use ($tahunAjaran): void {
                $query->where('TahunAjaran_ID', $tahunAjaran->getKey())
                    ->when($this->filterJenjang !== '', fn ($q) => $q->where('Jenjang', $this->filterJenjang))
                    ->when($this->filterSemester !== '', fn ($q) => $q->where('Semester', $this->filterSemester))
                    ->when($this->filterTipe !== '', fn ($q) => $q->where('Tipe', $this->filterTipe))
                    ->orderBy('Kode_KD');
            }])
            ->orderBy('Nama_Program')
            ->get()
            ->filter(fn (ProgramPengembangan $program): bool => $program->indikatorCapaian->isNotEmpty())
            ->values();
    }

    public function tambah(): void
    {
        $this->reset('formIndikator', 'formDeskripsi', 'formTipe');
        $this->formProgram = $this->program->first()?->getKey();
        $this->formTerbuka = true;
    }

    public function ubah(string $indikatorId): void
    {
        $indikator = IndikatorCapaian::query()->findOrFail($indikatorId);

        $this->formIndikator = $indikator->getKey();
        $this->formProgram = $indikator->Program_ID;
        $this->formDeskripsi = $indikator->Deskripsi;
        $this->formTipe = $indikator->Tipe->value;
        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->reset('formTerbuka', 'formIndikator', 'formProgram', 'formDeskripsi', 'formTipe');
    }

    public function simpan(): void
    {
        $this->validate([
            'formProgram' => ['required', 'string'],
            'formDeskripsi' => ['required', 'string', 'max:255'],
            'formTipe' => ['required', 'string'],
        ]);

        $tahunAjaran = $this->periodeAktif;

        $data = [
            'Program_ID' => $this->formProgram,
            'TahunAjaran_ID' => $tahunAjaran?->getKey(),
            'Jenjang' => $this->filterJenjang !== '' ? $this->filterJenjang : Jenjang::KBA->value,
            'Semester' => $this->filterSemester !== '' ? $this->filterSemester : Semester::Ganjil->value,
            'Tipe' => $this->formTipe,
            'Deskripsi' => $this->formDeskripsi,
        ];

        if ($this->formIndikator === null) {
            IndikatorCapaian::create($data);
        } else {
            IndikatorCapaian::query()->findOrFail($this->formIndikator)->update($data);
        }

        $this->tutupForm();

        session()->flash('sukses', 'Indikator tersimpan.');
    }

    public function hapus(string $indikatorId): void
    {
        IndikatorCapaian::query()->findOrFail($indikatorId)->delete();

        session()->flash('sukses', 'Indikator dihapus.');
    }

    public function salinSemesterLalu(): void
    {
        $periode = $this->periodeAktif;

        if ($periode === null) {
            session()->flash('galat', 'Belum ada tahun ajaran aktif.');

            return;
        }

        $aktif = $periode->Semester_Aktif;
        $lalu = $aktif === Semester::Ganjil ? Semester::Genap : Semester::Ganjil;

        $sumber = IndikatorCapaian::query()
            ->where('TahunAjaran_ID', $periode->getKey())
            ->where('Semester', $lalu)
            ->when($this->filterMapel !== '', fn ($query) => $query->whereIn(
                'Program_ID',
                ProgramPengembangan::query()->where('Mapel_ID', $this->filterMapel)->select('Program_ID'),
            ))
            ->when($this->filterJenjang !== '', fn ($query) => $query->where('Jenjang', $this->filterJenjang))
            ->when($this->filterTipe !== '', fn ($query) => $query->where('Tipe', $this->filterTipe))
            ->get();

        $sebelum = IndikatorCapaian::query()
            ->where('TahunAjaran_ID', $periode->getKey())
            ->where('Semester', $aktif)
            ->count();

        foreach ($sumber as $indikator) {
            $indikator->salinIndikator($aktif);
        }

        $tersalin = IndikatorCapaian::query()
            ->where('TahunAjaran_ID', $periode->getKey())
            ->where('Semester', $aktif)
            ->count() - $sebelum;

        session()->flash(
            $tersalin > 0 ? 'sukses' : 'galat',
            $tersalin > 0
                ? $tersalin.' indikator disalin dari semester '.$lalu->label().'.'
                : 'Tidak ada indikator baru untuk disalin dari semester '.$lalu->label().'.',
        );
    }

    #[Computed]
    public function mapelTerpilih(): ?MataPelajaran
    {
        return $this->daftarMapel->firstWhere('Mapel_ID', $this->filterMapel);
    }
};

?>

<div>
    <x-app.header
        title="Indikator Penilaian"
        :subtitle="$this->mapelTerpilih?->Nama_Mapel ?? 'Semua mata pelajaran'"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" wire:click="salinSemesterLalu">Salin Semester Lalu</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="tambah">Tambah Indikator</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if (session('sukses'))
        <div class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">{{ session('galat') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex h-[72px] shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8">
        <select wire:model.live="filterMapel" class="h-9 w-[300px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Mata Pelajaran</option>
            @foreach ($this->daftarMapel as $mapel)
                <option value="{{ $mapel->getKey() }}">{{ $mapel->Nama_Mapel }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterJenjang" class="h-9 w-[160px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Jenjang</option>
            @foreach (Jenjang::cases() as $jenjang)
                <option value="{{ $jenjang->value }}">{{ $jenjang->value }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterSemester" class="h-9 w-[160px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Semester</option>
            @foreach (Semester::cases() as $semester)
                <option value="{{ $semester->value }}">{{ $semester->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterTipe" class="h-9 w-[180px] rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
            <option value="">Tipe Indikator</option>
            @foreach (TipeIndikator::cases() as $tipe)
                <option value="{{ $tipe->value }}">{{ $tipe->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 space-y-5 overflow-y-auto bg-canvas px-8 py-6">
        @if ($formTerbuka)
            <form wire:submit="simpan" class="rounded border border-hairline bg-surface p-4">
                <h2 class="text-[15px] font-semibold text-heading">{{ $formIndikator === null ? 'Tambah Indikator' : 'Ubah Indikator' }}</h2>

                <div class="mt-3 grid grid-cols-[1fr_200px] gap-3">
                    <input type="text" wire:model="formDeskripsi" placeholder="Deskripsi indikator"
                        class="h-9 rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                    <select wire:model="formTipe" class="h-9 rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                        <option value="">Tipe</option>
                        @foreach (TipeIndikator::cases() as $tipe)
                            <option value="{{ $tipe->value }}">{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @error('formDeskripsi') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                @error('formTipe') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror

                <div class="mt-3 flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" wire:click="tutupForm">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
                </div>
            </form>
        @endif

        @forelse ($this->program as $program)
            <section class="overflow-hidden rounded border border-hairline bg-surface">
                <header class="flex items-center justify-between bg-canvas px-4 py-3">
                    <h2 class="text-[15px] font-semibold text-heading">{{ $program->Nama_Program }}</h2>
                    <x-ui.button type="button" variant="ghost" size="sm">Ubah</x-ui.button>
                </header>

                <table class="w-full">
                    <tbody>
                        @foreach ($program->indikatorCapaian as $indikator)
                            <tr wire:key="{{ $indikator->getKey() }}" class="border-t border-hairline">
                                <td class="w-12 px-4 py-3 text-right text-[15px] text-muted">{{ $indikator->Kode_KD }}</td>
                                <td class="px-4 py-3 text-[15px] text-body">{{ $indikator->Deskripsi }}</td>
                                <td class="w-[110px] px-4 py-3 text-[14px] text-muted">{{ $indikator->Tipe->label() }}</td>
                                <td class="w-[180px] px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $indikator->getKey() }}')">Ubah</x-ui.button>
                                        <x-ui.button type="button" variant="danger" size="sm" wire:click="hapus('{{ $indikator->getKey() }}')" wire:confirm="Hapus indikator ini?">Hapus</x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <p class="py-16 text-center text-[15px] text-muted">Belum ada indikator untuk filter ini.</p>
        @endforelse
    </div>
</div>
