<?php

use App\Enums\JenisKelamin;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tata Usaha: add or edit one student's record, wireframe TU-04.
 *
 * The class is deliberately not editable here: placing a student is what opens
 * their report draft, and that belongs to Penempatan Siswa.
 */
new #[Layout('layouts::app', ['active' => 'siswa'])]
class extends Component
{
    /** Agama options used on the school's enrolment form. */
    public const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    public ?Siswa $siswa = null;

    public string $nisn = '';

    public string $nis = '';

    public string $nama = '';

    public string $jenisKelamin = '';

    public string $agama = '';

    public function mount(?Siswa $siswa = null): void
    {
        if ($siswa?->exists) {
            $this->siswa = $siswa;
            $this->nisn = $siswa->NISN;
            $this->nis = $siswa->NIS;
            $this->nama = $siswa->Nama;
            $this->jenisKelamin = $siswa->Jenis_Kelamin->value;
            $this->agama = $siswa->Agama;
        } else {
            $this->siswa = null;
        }
    }

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->active()->first();
    }

    #[Computed]
    public function raporSaatIni(): ?Rapor
    {
        if ($this->siswa === null || $this->periode === null) {
            return null;
        }

        return $this->siswa->rapor()
            ->where('Semester', $this->periode->Semester_Aktif)
            ->with('kelas')
            ->orderByDesc('Rapor_ID')
            ->first();
    }

    /** @return Collection<int, Rapor> */
    #[Computed]
    public function riwayat(): Collection
    {
        return $this->siswa?->getRiwayatRapor() ?? collect();
    }

    public function simpan(): void
    {
        $id = $this->siswa?->getKey();

        $data = $this->validate([
            'nisn' => ['required', 'digits:10', Rule::unique('siswas', 'NISN')->ignore($id, 'Siswa_ID')],
            'nis' => ['required', 'string', 'max:20', Rule::unique('siswas', 'NIS')->ignore($id, 'Siswa_ID')],
            'nama' => ['required', 'string', 'max:100'],
            'jenisKelamin' => ['required', Rule::enum(JenisKelamin::class)],
            'agama' => ['required', Rule::in(self::AGAMA)],
        ], [
            'nisn.digits' => 'NISN harus terdiri dari 10 digit angka.',
            'nisn.unique' => 'Data sudah terdaftar: NISN ini sudah dipakai siswa lain.',
            'nis.unique' => 'Data sudah terdaftar: NIS ini sudah dipakai siswa lain.',
        ], [
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'nama' => 'nama lengkap',
            'jenisKelamin' => 'jenis kelamin',
        ]);

        $siswa = $this->siswa ?? new Siswa;
        $siswa->fill([
            'NISN' => $data['nisn'],
            'NIS' => $data['nis'],
            'Nama' => $data['nama'],
            'Jenis_Kelamin' => $data['jenisKelamin'],
            'Agama' => $data['agama'],
        ])->save();

        session()->flash('sukses', 'Data siswa '.$siswa->Nama.' berhasil disimpan.');

        $this->redirect(route('siswa.index'), navigate: true);
    }
};

?>

<div>
    <x-app.header
        :title="$siswa ? 'Ubah Data Siswa' : 'Tambah Siswa'"
        :subtitle="'Data Siswa / '.($siswa ? $siswa->Nama : 'Tambah Siswa')"
    >
        <x-slot:actions>
            <x-ui.button-link :href="route('siswa.index')">Batal</x-ui.button-link>
            <x-ui.button type="submit" form="form-siswa" variant="primary">Simpan</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-[1fr_380px] items-start gap-6">
            <form id="form-siswa" wire:submit="simpan" class="rounded border border-hairline bg-surface p-6">
                <h2 class="text-[16px] font-semibold text-heading">Identitas Siswa</h2>
                <p class="mt-1 text-[14px] text-muted">Semua kolom wajib diisi.</p>

                <div class="mt-5 grid grid-cols-2 gap-4">
                    <x-ui.field label="NISN" for="nisn" error="nisn">
                        <x-ui.input id="nisn" wire:model="nisn" inputmode="numeric" maxlength="10" placeholder="10 digit angka" :invalid="$errors->has('nisn')" />
                    </x-ui.field>

                    <x-ui.field label="NIS" for="nis" error="nis">
                        <x-ui.input id="nis" wire:model="nis" :invalid="$errors->has('nis')" />
                    </x-ui.field>

                    <x-ui.field label="Nama Lengkap" for="nama" error="nama" class="col-span-2">
                        <x-ui.input id="nama" wire:model="nama" :invalid="$errors->has('nama')" />
                    </x-ui.field>

                    <x-ui.field label="Jenis Kelamin" for="jenis-kelamin" error="jenisKelamin">
                        <x-ui.select id="jenis-kelamin" wire:model="jenisKelamin" :invalid="$errors->has('jenisKelamin')">
                            <option value="">Pilih jenis kelamin</option>
                            @foreach (JenisKelamin::cases() as $pilihan)
                                <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Agama" for="agama" error="agama">
                        <x-ui.select id="agama" wire:model="agama" :invalid="$errors->has('agama')">
                            <option value="">Pilih agama</option>
                            @foreach ($this::AGAMA as $pilihan)
                                <option value="{{ $pilihan }}">{{ $pilihan }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>
            </form>

            <aside class="space-y-5">
                <section class="rounded border border-hairline bg-surface p-5">
                    <h2 class="text-[16px] font-semibold text-heading">Kelas Saat Ini</h2>
                    <div class="mt-3">
                        @if ($this->raporSaatIni)
                            <x-ui.badge tone="positif">{{ $this->raporSaatIni->kelas->Nama_Kelas }}</x-ui.badge>
                        @else
                            <x-ui.badge>Belum ditempatkan</x-ui.badge>
                        @endif
                    </div>
                    <p class="mt-3 text-[13px] text-muted">Kelas siswa diatur melalui menu Penempatan Siswa{{ $siswa ? '' : ' setelah data siswa tersimpan' }}.</p>
                    @if ($siswa)
                        <x-ui.button-link :href="route('penempatan')" size="sm" class="mt-3">Buka Penempatan Siswa</x-ui.button-link>
                    @endif
                </section>

                <section class="rounded border border-hairline bg-surface p-5">
                    <h2 class="text-[16px] font-semibold text-heading">Riwayat Rapor</h2>
                    @forelse ($this->riwayat as $rapor)
                        <div class="mt-3 flex items-center justify-between gap-3 text-[14px]">
                            <span class="text-body">{{ $rapor->kelas?->Nama_Kelas }} - {{ $rapor->Semester->label() }}</span>
                            <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
                        </div>
                    @empty
                        <p class="mt-3 text-[13px] text-muted">Belum ada rapor untuk siswa ini. Draf rapor dibuat otomatis saat siswa ditempatkan ke kelas pada semester aktif.</p>
                    @endforelse
                </section>
            </aside>
        </div>
    </div>
</div>
