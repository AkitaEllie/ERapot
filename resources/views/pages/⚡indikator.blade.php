<?php

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\MataPelajaran;
use App\Models\ProgramPengembangan;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
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

    /** The subject the indicator is filed under; it limits the program choice. */
    public string $formMapel = '';

    public ?string $formProgram = null;

    /** Name for a program created on the spot, used when no program is chosen. */
    public string $formProgramBaru = '';

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

    /**
     * The subjects whose indicators this teacher may change. Everyone may read
     * every subject's indicators, but only its own teachers maintain them.
     *
     * @return Collection<int, MataPelajaran>
     */
    #[Computed]
    public function mapelDikelola(): Collection
    {
        $user = auth()->user();

        return MataPelajaran::query()
            ->unless($user->Role_ID === 'ADMIN', fn ($query) => $query->whereIn('Mapel_ID', $user->pembelajaran()->select('Mapel_ID')))
            ->orderBy('Nama_Mapel')
            ->get();
    }

    public function bolehKelola(string $mapelId): bool
    {
        return $this->mapelDikelola->contains('Mapel_ID', $mapelId);
    }

    public function tambah(): void
    {
        $this->reset('formIndikator', 'formDeskripsi', 'formTipe', 'formProgramBaru');
        $this->formMapel = $this->bolehKelola($this->filterMapel) ? $this->filterMapel : '';
        $this->formProgram = $this->programForm->first()?->getKey();
        $this->formTerbuka = true;
    }

    public function ubah(string $indikatorId): void
    {
        $indikator = IndikatorCapaian::query()->with('program')->findOrFail($indikatorId);

        abort_unless($this->bolehKelola($indikator->program->Mapel_ID), 403);

        $this->formIndikator = $indikator->getKey();
        $this->formMapel = $indikator->program->Mapel_ID;
        $this->formProgram = $indikator->Program_ID;
        $this->formProgramBaru = '';
        $this->formDeskripsi = $indikator->Deskripsi;
        $this->formTipe = $indikator->Tipe->value;
        $this->formTerbuka = true;
    }

    /**
     * A program belongs to one subject, so switching the subject offers that
     * subject's programs instead of keeping a program from another one.
     */
    public function updatedFormMapel(): void
    {
        $this->formProgram = $this->programForm->first()?->getKey();
        $this->formProgramBaru = '';
    }

    /**
     * The programs of the subject chosen in the form.
     *
     * @return Collection<int, ProgramPengembangan>
     */
    #[Computed]
    public function programForm(): Collection
    {
        if ($this->formMapel === '') {
            return collect();
        }

        return ProgramPengembangan::query()
            ->where('Mapel_ID', $this->formMapel)
            ->orderBy('Nama_Program')
            ->get();
    }

    public function tutupForm(): void
    {
        $this->reset('formTerbuka', 'formIndikator', 'formMapel', 'formProgram', 'formProgramBaru', 'formDeskripsi', 'formTipe');
    }

    public function simpan(): void
    {
        $baru = $this->formIndikator === null;

        $this->validate([
            'formMapel' => ['required', Rule::in($this->mapelDikelola->pluck('Mapel_ID'))],
            'formProgram' => [
                'nullable',
                'required_without:formProgramBaru',
                Rule::exists('program_pengembangans', 'Program_ID')->where('Mapel_ID', $this->formMapel),
            ],
            'formProgramBaru' => ['nullable', 'required_without:formProgram', 'string', 'max:255'],
            'formDeskripsi' => ['required', 'string', 'max:255'],
            'formTipe' => ['required', Rule::enum(TipeIndikator::class)],
            // A new indicator takes its level and semester from the filters; there
            // is no safe default, so an unset filter must be chosen first.
            'filterJenjang' => [Rule::requiredIf($baru)],
            'filterSemester' => [Rule::requiredIf($baru)],
        ], [
            'formMapel.required' => 'Pilih mata pelajaran.',
            'formMapel.in' => 'Anda tidak ditugaskan mengampu mata pelajaran ini.',
            'formProgram.required_without' => 'Pilih program pengembangan atau tulis nama program baru.',
            'formProgram.exists' => 'Program tidak termasuk mata pelajaran yang dipilih.',
            'formProgramBaru.required_without' => 'Pilih program pengembangan atau tulis nama program baru.',
            'formDeskripsi.required' => 'Deskripsi indikator wajib diisi.',
            'formTipe.required' => 'Pilih tipe indikator.',
            'filterJenjang.required' => 'Pilih jenjang pada filter di atas terlebih dahulu.',
            'filterSemester.required' => 'Pilih semester pada filter di atas terlebih dahulu.',
        ]);

        $programId = filled($this->formProgram)
            ? $this->formProgram
            : ProgramPengembangan::create(['Mapel_ID' => $this->formMapel, 'Nama_Program' => trim($this->formProgramBaru)])->getKey();

        $data = [
            'Program_ID' => $programId,
            'Tipe' => $this->formTipe,
            'Deskripsi' => $this->formDeskripsi,
        ];

        if ($baru) {
            IndikatorCapaian::create($data + [
                'TahunAjaran_ID' => $this->periodeAktif?->getKey(),
                'Jenjang' => $this->filterJenjang,
                'Semester' => $this->filterSemester,
            ]);
        } else {
            $indikator = IndikatorCapaian::query()->with('program')->findOrFail($this->formIndikator);

            abort_unless($this->bolehKelola($indikator->program->Mapel_ID), 403);

            $indikator->update($data);
        }

        // Show the subject the indicator was filed under, so it is never saved
        // somewhere the list is not looking.
        $this->filterMapel = $this->formMapel;
        $this->tutupForm();

        session()->flash('sukses', 'Indikator tersimpan.');
    }

    public function hapus(string $indikatorId): void
    {
        $indikator = IndikatorCapaian::query()->with('program')->findOrFail($indikatorId);

        abort_unless($this->bolehKelola($indikator->program->Mapel_ID), 403);

        // The foreign key is restrictOnDelete, so an indicator carrying recorded
        // scores must not be removed; deleting it would discard student grades.
        if ($indikator->nilaiSiswa()->exists()) {
            session()->flash('galat', 'Indikator ini sudah memiliki nilai siswa sehingga tidak dapat dihapus.');

            return;
        }

        $indikator->delete();

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
            // Only subjects this teacher maintains, narrowed further by the filter.
            ->whereIn('Program_ID', ProgramPengembangan::query()
                ->whereIn('Mapel_ID', $this->mapelDikelola->pluck('Mapel_ID'))
                ->when($this->filterMapel !== '', fn ($query) => $query->where('Mapel_ID', $this->filterMapel))
                ->select('Program_ID'))
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

                <div class="mt-3 grid grid-cols-2 gap-3">
                    <select wire:model.live="formMapel" aria-label="Mata pelajaran indikator" class="h-9 rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                        <option value="">Pilih mata pelajaran</option>
                        @foreach ($this->mapelDikelola as $mapel)
                            <option value="{{ $mapel->getKey() }}">{{ $mapel->Nama_Mapel }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="formProgram" aria-label="Program pengembangan" @disabled($formMapel === '') class="h-9 rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none disabled:bg-canvas disabled:text-muted">
                        @foreach ($this->programForm as $program)
                            <option value="{{ $program->getKey() }}">{{ $program->Nama_Program }}</option>
                        @endforeach
                        <option value="">+ Program baru</option>
                    </select>
                </div>

                @if ($formMapel !== '' && blank($formProgram))
                    <input type="text" wire:model="formProgramBaru" placeholder="Nama program pengembangan baru" aria-label="Nama program pengembangan baru"
                        class="mt-3 h-9 w-full rounded border border-hairline-strong bg-surface px-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none">
                @endif

                @error('formMapel') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                @error('formProgram') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                @error('formProgramBaru') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror

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
                @error('filterJenjang') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                @error('filterSemester') <p class="mt-2 text-[13px] text-rose-700">{{ $message }}</p> @enderror

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
                                    @if ($this->bolehKelola($program->Mapel_ID))
                                        <div class="flex justify-end gap-2">
                                            <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $indikator->getKey() }}')">Ubah</x-ui.button>
                                            <x-ui.button type="button" variant="danger" size="sm" wire:click="hapus('{{ $indikator->getKey() }}')" wire:confirm="Hapus indikator ini?">Hapus</x-ui.button>
                                        </div>
                                    @endif
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
