<?php

use App\Enums\Jenjang;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tata Usaha: the classes of the active school year and their homeroom
 * teachers, wireframe TU-05.
 *
 * Assigning a homeroom teacher here is what gives a teacher the whole report
 * book of a class, so the teacher's role follows: a subject teacher who gets a
 * class becomes a homeroom teacher, and goes back once they no longer hold one.
 */
new #[Layout('layouts::app', ['active' => 'kelas'])]
class extends Component
{
    #[Url(as: 'q')]
    public string $cari = '';

    #[Url(as: 'jenjang')]
    public string $filterJenjang = '';

    public ?string $sedangDiubah = null;

    public string $namaKelas = '';

    public string $jenjang = 'TK-A';

    public string $fase = 'Pondasi';

    public string $waliKelas = '';

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->active()->first();
    }

    /** @return Collection<int, Kelas> */
    #[Computed]
    public function daftar(): Collection
    {
        $semester = $this->periode?->Semester_Aktif;

        return Kelas::query()
            ->when($this->periode, fn ($query) => $query->where('TahunAjaran_ID', $this->periode->getKey()))
            ->with('waliKelas')
            ->withCount(['rapor as jumlah_siswa' => fn ($query) => $query->when($semester, fn ($q) => $q->where('Semester', $semester))])
            ->withCount('rapor')
            ->when($this->cari !== '', fn ($query) => $query->where('Nama_Kelas', 'like', '%'.$this->cari.'%'))
            ->when($this->filterJenjang !== '', fn ($query) => $query->where('Jenjang', $this->filterJenjang))
            ->orderBy('Jenjang')
            ->orderBy('Nama_Kelas')
            ->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function daftarGuru(): Collection
    {
        return User::query()
            ->active()
            ->whereIn('Role_ID', ['GURU', 'GURUWALI'])
            ->with(['kelasDiampu' => fn ($query) => $query->when($this->periode, fn ($q) => $q->where('TahunAjaran_ID', $this->periode->getKey()))])
            ->orderBy('Nama')
            ->get();
    }

    public function ubah(string $id): void
    {
        $kelas = Kelas::query()->findOrFail($id);

        $this->sedangDiubah = $kelas->getKey();
        $this->namaKelas = $kelas->Nama_Kelas;
        $this->jenjang = $kelas->Jenjang->value;
        $this->fase = $kelas->Fase;
        $this->waliKelas = (string) $kelas->User_ID;
        $this->resetValidation();
    }

    public function batal(): void
    {
        $this->reset('sedangDiubah', 'namaKelas', 'jenjang', 'fase', 'waliKelas');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        $periode = $this->periode;

        if ($periode === null) {
            session()->flash('galat', 'Belum ada tahun ajaran aktif. Aktifkan satu periode di menu Tahun Ajaran terlebih dahulu.');

            return;
        }

        $data = $this->validate([
            'namaKelas' => [
                'required', 'string', 'max:30',
                Rule::unique('kelas', 'Nama_Kelas')
                    ->where('TahunAjaran_ID', $periode->getKey())
                    ->ignore($this->sedangDiubah, 'Kelas_ID'),
            ],
            'jenjang' => ['required', Rule::enum(Jenjang::class)],
            'fase' => ['required', 'string', 'max:20'],
            'waliKelas' => [
                'nullable',
                Rule::exists('users', 'User_ID')->whereIn('Role_ID', ['GURU', 'GURUWALI']),
                Rule::unique('kelas', 'User_ID')
                    ->where('TahunAjaran_ID', $periode->getKey())
                    ->ignore($this->sedangDiubah, 'Kelas_ID'),
            ],
        ], [
            'namaKelas.unique' => 'Data sudah terdaftar: nama kelas ini sudah dipakai pada tahun ajaran aktif.',
            'waliKelas.unique' => 'Guru ini sudah menjadi wali kelas lain pada tahun ajaran aktif.',
        ], [
            'namaKelas' => 'nama kelas',
            'waliKelas' => 'wali kelas',
        ]);

        $kelas = $this->sedangDiubah !== null
            ? Kelas::query()->findOrFail($this->sedangDiubah)
            : new Kelas(['TahunAjaran_ID' => $periode->getKey()]);

        $waliLama = $kelas->User_ID;

        $kelas->fill([
            'Nama_Kelas' => $data['namaKelas'],
            'Jenjang' => $data['jenjang'],
            'Fase' => $data['fase'],
            'User_ID' => $data['waliKelas'] ?: null,
        ])->save();

        $this->selaraskanPeran($kelas->User_ID);
        if ($waliLama !== $kelas->User_ID) {
            $this->selaraskanPeran($waliLama);
        }

        session()->flash('sukses', 'Data berhasil disimpan.');
        $this->batal();
        unset($this->daftar, $this->daftarGuru);
    }

    public function hapus(string $id): void
    {
        $kelas = Kelas::query()->withCount('rapor')->findOrFail($id);

        if ($kelas->rapor_count > 0) {
            session()->flash('galat', 'Kelas '.$kelas->Nama_Kelas.' sudah memiliki rapor siswa, sehingga tidak dapat dihapus.');

            return;
        }

        $wali = $kelas->User_ID;
        $kelas->delete();
        $this->selaraskanPeran($wali);

        session()->flash('sukses', 'Kelas '.$kelas->Nama_Kelas.' dihapus.');
        unset($this->daftar, $this->daftarGuru);
    }

    /**
     * Keep a teacher's role in step with whether they hold a class.
     */
    private function selaraskanPeran(?string $userId): void
    {
        if ($userId === null) {
            return;
        }

        $guru = User::query()->find($userId);

        if ($guru === null || ! in_array($guru->Role_ID, ['GURU', 'GURUWALI'], true)) {
            return;
        }

        $guru->forceFill(['Role_ID' => $guru->kelasDiampu()->exists() ? 'GURUWALI' : 'GURU'])->save();
    }
};

?>

<div>
    <x-app.header
        title="Data Kelas"
        :subtitle="($this->periode ? 'Tahun Ajaran '.$this->periode->Tahun_Ajaran : 'Belum ada tahun ajaran aktif').' - '.$this->daftar->count().' kelas'"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="batal" x-on:click="$nextTick(() => document.getElementById('nama-kelas')?.focus())">Tambah Kelas</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="flex shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8 py-4">
        <div class="w-[420px]"><x-ui.input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama kelas..." aria-label="Cari kelas" /></div>
        <div class="w-[200px]">
            <x-ui.select wire:model.live="filterJenjang" aria-label="Filter jenjang">
                <option value="">Semua jenjang</option>
                @foreach (Jenjang::cases() as $pilihan)
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
                        <th class="px-4 py-3 text-left font-medium">Nama Kelas</th>
                        <th class="w-28 px-4 py-3 text-left font-medium">Jenjang</th>
                        <th class="w-28 px-4 py-3 text-left font-medium">Fase</th>
                        <th class="w-56 px-4 py-3 text-left font-medium">Wali Kelas</th>
                        <th class="w-32 px-4 py-3 text-left font-medium">Jumlah Siswa</th>
                        <th class="w-60 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->daftar as $kelas)
                        <tr wire:key="{{ $kelas->getKey() }}" @class(['border-b border-hairline last:border-b-0', 'bg-accent-soft/40' => $sedangDiubah === $kelas->getKey()])>
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-[15px] font-medium text-heading">{{ $kelas->Nama_Kelas }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $kelas->Jenjang->label() }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $kelas->Fase }}</td>
                            <td class="px-4 py-3 text-[15px]">
                                @if ($kelas->waliKelas)
                                    <span class="text-body">{{ $kelas->waliKelas->Nama }}</span>
                                @else
                                    <x-ui.badge tone="peringatan">Belum ada wali kelas</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $kelas->jumlah_siswa }} siswa</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $kelas->getKey() }}')">Ubah</x-ui.button>
                                    <x-ui.button-link :href="route('penempatan', ['kelas' => $kelas->getKey()])" size="sm">Lihat Siswa</x-ui.button-link>
                                    <x-ui.button type="button" variant="secondary" size="sm"
                                        wire:click="hapus('{{ $kelas->getKey() }}')"
                                        wire:confirm="Hapus kelas {{ $kelas->Nama_Kelas }}?"
                                        :disabled="$kelas->rapor_count > 0"
                                        title="{{ $kelas->rapor_count > 0 ? 'Kelas yang sudah memiliki rapor tidak dapat dihapus' : '' }}"
                                    >Hapus</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[15px] text-muted">Belum ada kelas pada tahun ajaran aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($this->daftar->isNotEmpty())
                <p class="border-t border-hairline px-4 py-3 text-[14px] text-muted">Menampilkan {{ $this->daftar->count() }} dari {{ $this->daftar->count() }} kelas</p>
            @endif
        </div>

        <form wire:submit="simpan" class="rounded border border-hairline bg-surface p-6">
            <h2 class="text-[16px] font-semibold text-heading">{{ $sedangDiubah ? 'Ubah Kelas' : 'Tambah Kelas' }}</h2>
            <p class="mt-1 text-[14px] text-muted">Kelas otomatis terhubung ke tahun ajaran aktif. Satu guru hanya dapat menjadi wali satu kelas.</p>

            <div class="mt-5 grid grid-cols-[1fr_160px_160px_1fr] gap-4">
                <x-ui.field label="Nama Kelas" for="nama-kelas" error="namaKelas">
                    <x-ui.input id="nama-kelas" wire:model="namaKelas" placeholder="TK-B Compassion" :invalid="$errors->has('namaKelas')" />
                </x-ui.field>

                <x-ui.field label="Jenjang" for="jenjang" error="jenjang">
                    <x-ui.select id="jenjang" wire:model="jenjang">
                        @foreach (Jenjang::cases() as $pilihan)
                            <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Fase" for="fase" error="fase">
                    <x-ui.input id="fase" wire:model="fase" :invalid="$errors->has('fase')" />
                </x-ui.field>

                <x-ui.field label="Wali Kelas" for="wali-kelas" error="waliKelas">
                    <x-ui.select id="wali-kelas" wire:model="waliKelas" :invalid="$errors->has('waliKelas')">
                        <option value="">Belum ditentukan</option>
                        @foreach ($this->daftarGuru as $guru)
                            @php $kelasLain = $guru->kelasDiampu->reject(fn ($k) => $k->getKey() === $sedangDiubah)->first(); @endphp
                            <option value="{{ $guru->getKey() }}">{{ $guru->Nama }}{{ $kelasLain ? ' (wali '.$kelasLain->Nama_Kelas.')' : '' }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" wire:click="batal">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </div>
        </form>
    </div>
</div>
