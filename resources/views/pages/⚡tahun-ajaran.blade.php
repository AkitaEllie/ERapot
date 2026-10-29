<?php

use App\Enums\Semester;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tata Usaha: keeps the list of school years and decides which one is active.
 *
 * Wireframe TU-01. One row is one school year; its semester moves from Ganjil to
 * Genap through the "Ganti Semester" action on the dashboard, so the form only
 * sets the semester a year starts in.
 */
new #[Layout('layouts::app', ['active' => 'tahun-ajaran'])]
class extends Component
{
    /** The row being edited, or null while the form adds a new one. */
    public ?string $sedangDiubah = null;

    public string $tahunAjaran = '';

    public string $semester = 'ganjil';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public bool $jadikanAktif = false;

    /** @return Collection<int, TahunAjaran> */
    #[Computed]
    public function daftar(): Collection
    {
        return TahunAjaran::query()
            ->withCount('kelas')
            ->orderByDesc('Tanggal_Mulai')
            ->get();
    }

    public function ubah(string $id): void
    {
        $periode = TahunAjaran::query()->findOrFail($id);

        $this->sedangDiubah = $periode->getKey();
        $this->tahunAjaran = $periode->Tahun_Ajaran;
        $this->semester = $periode->Semester_Aktif->value;
        $this->tanggalMulai = $periode->Tanggal_Mulai->format('Y-m-d');
        $this->tanggalSelesai = $periode->Tanggal_Selesai->format('Y-m-d');
        $this->jadikanAktif = $periode->Is_Active;
        $this->resetValidation();
    }

    public function batal(): void
    {
        $this->reset('sedangDiubah', 'tahunAjaran', 'semester', 'tanggalMulai', 'tanggalSelesai', 'jadikanAktif');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'tahunAjaran' => [
                'required',
                'regex:/^\d{4}\/\d{4}$/',
                function (string $atribut, string $nilai, \Closure $gagal): void {
                    [$awal, $akhir] = array_map('intval', explode('/', $nilai));

                    if ($akhir !== $awal + 1) {
                        $gagal('Tahun kedua harus satu tahun setelah tahun pertama, misalnya 2026/2027.');
                    }
                },
                Rule::unique('tahun_ajarans', 'Tahun_Ajaran')->ignore($this->sedangDiubah, 'TahunAjaran_ID'),
            ],
            'semester' => ['required', Rule::enum(Semester::class)],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after:tanggalMulai'],
            'jadikanAktif' => ['boolean'],
        ], [
            'tahunAjaran.regex' => 'Gunakan format TTTT/TTTT, misalnya 2026/2027.',
            'tahunAjaran.unique' => 'Data sudah terdaftar: tahun ajaran ini sudah ada.',
            'tanggalSelesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ], [
            'tahunAjaran' => 'tahun ajaran',
            'tanggalMulai' => 'tanggal mulai',
            'tanggalSelesai' => 'tanggal selesai',
        ]);

        $periode = $this->sedangDiubah !== null
            ? TahunAjaran::query()->findOrFail($this->sedangDiubah)
            : new TahunAjaran(['Is_Active' => false]);

        $periode->fill([
            'Tahun_Ajaran' => $data['tahunAjaran'],
            'Semester_Aktif' => $data['semester'],
            'Tanggal_Mulai' => $data['tanggalMulai'],
            'Tanggal_Selesai' => $data['tanggalSelesai'],
        ]);

        // A brand-new row must not silently become a second active period.
        if (! $periode->exists) {
            $periode->Is_Active = false;
        }

        $periode->save();

        if ($this->jadikanAktif && ! $periode->Is_Active) {
            $periode->aktifkan();
        }

        session()->flash('sukses', 'Data berhasil disimpan.');
        $this->batal();
        unset($this->daftar);
    }

    public function aktifkan(string $id): void
    {
        $periode = TahunAjaran::query()->findOrFail($id);
        $periode->aktifkan();

        session()->flash('sukses', 'Tahun ajaran '.$periode->Tahun_Ajaran.' sekarang menjadi periode aktif.');
        unset($this->daftar);
    }
};

?>

<div>
    <x-app.header title="Tahun Ajaran" subtitle="Kelola tahun ajaran dan semester aktif">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="batal" x-on:click="$nextTick(() => document.getElementById('tahun-ajaran')?.focus())">Tambah Tahun Ajaran</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="flex-1 space-y-5 overflow-y-auto bg-canvas px-8 py-6">
        <div class="flex items-center gap-3 rounded border border-hairline bg-notice-bg px-4 py-3 text-[14px] text-label">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.2M10 13.4h.01" stroke-linecap="round"/></svg>
            Hanya satu periode yang dapat aktif. Periode aktif dipakai sebagai dasar penempatan siswa, penugasan mengajar, dan pembuatan draf rapor.
        </div>

        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                        <th class="px-4 py-3 text-left font-medium">Tahun Ajaran</th>
                        <th class="px-4 py-3 text-left font-medium">Semester Aktif</th>
                        <th class="px-4 py-3 text-left font-medium">Tanggal Mulai</th>
                        <th class="px-4 py-3 text-left font-medium">Tanggal Selesai</th>
                        <th class="px-4 py-3 text-left font-medium">Kelas</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="w-48 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->daftar as $periode)
                        <tr wire:key="{{ $periode->getKey() }}" @class(['border-b border-hairline last:border-b-0', 'bg-accent-soft/40' => $sedangDiubah === $periode->getKey()])>
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-[15px] font-medium text-heading">{{ $periode->Tahun_Ajaran }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $periode->Semester_Aktif->label() }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $periode->Tanggal_Mulai->translatedFormat('j F Y') }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $periode->Tanggal_Selesai->translatedFormat('j F Y') }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $periode->kelas_count }} kelas</td>
                            <td class="px-4 py-3">
                                @if ($periode->Is_Active)
                                    <x-ui.badge tone="positif">Aktif</x-ui.badge>
                                @else
                                    <x-ui.badge>Tidak aktif</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $periode->getKey() }}')">Ubah</x-ui.button>
                                    @unless ($periode->Is_Active)
                                        <x-ui.button type="button" variant="secondary" size="sm"
                                            wire:click="aktifkan('{{ $periode->getKey() }}')"
                                            wire:confirm="Jadikan {{ $periode->Tahun_Ajaran }} periode aktif? Periode yang sedang aktif akan dinonaktifkan."
                                        >Aktifkan</x-ui.button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-[15px] text-muted">Belum ada tahun ajaran. Tambahkan lewat form di bawah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($this->daftar->isNotEmpty())
                <p class="border-t border-hairline px-4 py-3 text-[14px] text-muted">Menampilkan {{ $this->daftar->count() }} dari {{ $this->daftar->count() }} periode</p>
            @endif
        </div>

        <form wire:submit="simpan" class="rounded border border-hairline bg-surface p-6">
            <h2 class="text-[16px] font-semibold text-heading">{{ $sedangDiubah ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran' }}</h2>
            <p class="mt-1 text-[14px] text-muted">Isi seluruh kolom lalu tekan Simpan. Tahun ajaran yang sudah terdaftar akan ditolak.</p>

            <div class="mt-5 grid grid-cols-4 gap-4">
                <x-ui.field label="Tahun Ajaran" for="tahun-ajaran" error="tahunAjaran">
                    <x-ui.input id="tahun-ajaran" wire:model="tahunAjaran" placeholder="2026/2027" :invalid="$errors->has('tahunAjaran')" />
                </x-ui.field>

                <x-ui.field label="Semester" for="semester" error="semester">
                    <x-ui.select id="semester" wire:model="semester">
                        @foreach (Semester::cases() as $pilihan)
                            <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Tanggal Mulai" for="tanggal-mulai" error="tanggalMulai">
                    <x-ui.input type="date" id="tanggal-mulai" wire:model="tanggalMulai" :invalid="$errors->has('tanggalMulai')" />
                </x-ui.field>

                <x-ui.field label="Tanggal Selesai" for="tanggal-selesai" error="tanggalSelesai">
                    <x-ui.input type="date" id="tanggal-selesai" wire:model="tanggalSelesai" :invalid="$errors->has('tanggalSelesai')" />
                </x-ui.field>
            </div>

            <label class="mt-4 flex items-center gap-2.5 text-[15px] text-body">
                <input type="checkbox" wire:model="jadikanAktif" class="size-4 rounded-sm border-sunken-strong text-accent-deep focus:ring-accent">
                Jadikan periode aktif setelah disimpan
            </label>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" wire:click="batal">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </div>
        </form>
    </div>
</div>
