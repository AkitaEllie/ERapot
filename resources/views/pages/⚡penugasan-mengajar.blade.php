<?php

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tata Usaha: who teaches which subject in which class, wireframe TU-07.
 *
 * A teacher may only fill in a report for a subject and class assigned here.
 * Replacing a teacher who already holds a slot is a deliberate step, so the
 * screen asks before it overwrites one (the alternative flow of the use case).
 */
new #[Layout('layouts::app', ['active' => 'penugasan'])]
class extends Component
{
    #[Url(as: 'kelas')]
    public string $kelasDipilih = '';

    /** @var array<string, string> Mapel_ID => User_ID, '' when unassigned */
    public array $guru = [];

    /** @var array<int, array{mapel: string, lama: string, baru: string}> Replacements awaiting confirmation */
    public array $konflik = [];

    public function mount(): void
    {
        $this->muatPenugasan();
    }

    public function pilihKelas(string $kelasId): void
    {
        $this->kelasDipilih = $kelasId;
        $this->konflik = [];
        unset($this->kelas);
        $this->muatPenugasan();
    }

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->active()->first();
    }

    /** @return Collection<int, Kelas> */
    #[Computed]
    public function daftarKelas(): Collection
    {
        return Kelas::query()
            ->when($this->periode, fn ($query) => $query->where('TahunAjaran_ID', $this->periode->getKey()))
            ->with('waliKelas')
            ->withCount(['pembelajaran as mapel_terisi' => fn ($query) => $query->whereNotNull('User_ID')])
            ->orderBy('Jenjang')
            ->orderBy('Nama_Kelas')
            ->get();
    }

    #[Computed]
    public function kelas(): ?Kelas
    {
        return $this->daftarKelas->firstWhere('Kelas_ID', $this->kelasDipilih) ?? $this->daftarKelas->first();
    }

    /** @return Collection<int, MataPelajaran> */
    #[Computed]
    public function daftarMapel(): Collection
    {
        return MataPelajaran::query()->orderBy('Jenis_Mapel')->orderBy('Nama_Mapel')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function daftarGuru(): Collection
    {
        return User::query()->active()->whereIn('Role_ID', ['GURU', 'GURUWALI'])->orderBy('Nama')->get();
    }

    /** @return Collection<string, Pembelajaran> Mapel_ID => current assignment */
    #[Computed]
    public function penugasanTersimpan(): Collection
    {
        $kelas = $this->kelas;

        if ($kelas === null) {
            return collect();
        }

        return Pembelajaran::query()->where('Kelas_ID', $kelas->getKey())->with('guru')->get()->keyBy('Mapel_ID');
    }

    public function simpan(bool $gantiDisetujui = false): void
    {
        $kelas = $this->kelas;

        if ($kelas === null) {
            return;
        }

        $tersimpan = $this->penugasanTersimpan;
        $guruValid = $this->daftarGuru->pluck('User_ID')->all();
        $perubahan = [];
        $konflik = [];

        foreach ($this->daftarMapel as $mapel) {
            $lama = (string) $tersimpan->get($mapel->getKey())?->User_ID;
            $baru = (string) ($this->guru[$mapel->getKey()] ?? '');

            if ($lama === $baru) {
                continue;
            }

            if ($baru !== '' && ! in_array($baru, $guruValid, true)) {
                $this->addError('guru.'.$mapel->getKey(), 'Pilih guru pengampu yang aktif.');

                return;
            }

            $perubahan[$mapel->getKey()] = $baru;

            if ($lama !== '' && $baru !== '') {
                $konflik[] = ['mapel' => $mapel->getKey(), 'lama' => $lama, 'baru' => $baru];
            }
        }

        if ($perubahan === []) {
            session()->flash('galat', 'Tidak ada perubahan penugasan untuk disimpan.');

            return;
        }

        if ($konflik !== [] && ! $gantiDisetujui) {
            $this->konflik = $konflik;

            return;
        }

        foreach ($perubahan as $mapelId => $userId) {
            $mapel = $this->daftarMapel->firstWhere('Mapel_ID', $mapelId);

            // Clearing a slot nobody held leaves nothing to record.
            if ($userId === '' && ! $tersimpan->has($mapelId)) {
                continue;
            }

            Pembelajaran::tugaskan($kelas, $mapel, $userId === '' ? null : $this->daftarGuru->firstWhere('User_ID', $userId));
        }

        $this->konflik = [];
        session()->flash('sukses', 'Penugasan berhasil disimpan.');
        unset($this->penugasanTersimpan, $this->daftarKelas, $this->kelas);
        $this->muatPenugasan();
    }

    public function batalGanti(): void
    {
        foreach ($this->konflik as $item) {
            $this->guru[$item['mapel']] = $item['lama'];
        }

        $this->konflik = [];
    }

    private function muatPenugasan(): void
    {
        $tersimpan = $this->penugasanTersimpan;

        $this->guru = $this->daftarMapel
            ->mapWithKeys(fn (MataPelajaran $mapel): array => [$mapel->getKey() => (string) $tersimpan->get($mapel->getKey())?->User_ID])
            ->all();
    }
};

?>

<div>
    <x-app.header
        title="Penugasan Mengajar"
        :subtitle="$this->periode ? 'Tahun Ajaran '.$this->periode->Tahun_Ajaran.' - Semester '.$this->periode->Semester_Aktif->label() : 'Belum ada tahun ajaran aktif'"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="simpan">Simpan Penugasan</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="border-b border-hairline bg-notice-bg px-8 py-2.5 text-[14px] text-label">
        Guru hanya dapat mengisi rapor untuk mata pelajaran dan kelas yang ditugaskan kepadanya.
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        @if ($this->daftarKelas->isEmpty())
            <p class="rounded border border-hairline bg-surface px-4 py-16 text-center text-[15px] text-muted">Belum ada kelas pada tahun ajaran aktif. Tambahkan kelas di menu Data Kelas.</p>
        @else
            <div class="grid grid-cols-[280px_1fr] items-start gap-6">
                <section class="overflow-hidden rounded border border-hairline bg-surface">
                    <header class="flex h-[44px] items-center bg-canvas px-4">
                        <h2 class="text-[15px] font-semibold text-heading">Kelas ({{ $this->daftarKelas->count() }})</h2>
                    </header>
                    <ul class="divide-y divide-hairline">
                        @foreach ($this->daftarKelas as $item)
                            @php $aktif = $this->kelas?->getKey() === $item->getKey(); @endphp
                            <li wire:key="kelas-{{ $item->getKey() }}">
                                <button type="button" wire:click="pilihKelas('{{ $item->getKey() }}')"
                                    @class(['flex w-full flex-col items-start gap-0.5 px-4 py-3 text-left transition-colors', 'bg-accent-soft' => $aktif, 'hover:bg-canvas' => ! $aktif])
                                    @if ($aktif) aria-current="true" @endif
                                >
                                    <span class="text-[15px] text-heading">{{ $item->Nama_Kelas }}</span>
                                    <span class="text-[13px] text-muted">{{ $item->mapel_terisi }} / {{ $this->daftarMapel->count() }} mapel terisi</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="min-w-0 space-y-4">
                    <div>
                        <h2 class="text-[18px] font-semibold text-heading">{{ $this->kelas?->Nama_Kelas }}</h2>
                        <p class="text-[14px] text-muted">Wali kelas: {{ $this->kelas?->waliKelas?->Nama ?? 'belum ditentukan' }}</p>
                    </div>

                    <div class="overflow-hidden rounded border border-hairline bg-surface">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                                    <th class="w-12 px-4 py-3 text-right font-medium">No</th>
                                    <th class="px-4 py-3 text-left font-medium">Mata Pelajaran</th>
                                    <th class="w-36 px-4 py-3 text-left font-medium">Jenis</th>
                                    <th class="w-72 px-4 py-3 text-left font-medium">Guru Pengampu</th>
                                    <th class="w-40 px-4 py-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->daftarMapel as $mapel)
                                    @php $terisi = filled($this->penugasanTersimpan->get($mapel->getKey())?->User_ID); @endphp
                                    <tr wire:key="mapel-{{ $mapel->getKey() }}" @class(['border-b border-hairline last:border-b-0', 'bg-canvas' => ! $terisi])>
                                        <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 text-[15px] text-body">{{ $mapel->Nama_Mapel }}</td>
                                        <td class="px-4 py-3"><x-ui.badge>{{ $mapel->Jenis_Mapel->label() }}</x-ui.badge></td>
                                        <td class="px-4 py-2">
                                            <x-ui.select wire:model="guru.{{ $mapel->getKey() }}" aria-label="Guru pengampu {{ $mapel->Nama_Mapel }}" :invalid="$errors->has('guru.'.$mapel->getKey())">
                                                <option value="">Pilih guru pengampu</option>
                                                @foreach ($this->daftarGuru as $g)
                                                    <option value="{{ $g->getKey() }}">{{ $g->Nama }}</option>
                                                @endforeach
                                            </x-ui.select>
                                            @error('guru.'.$mapel->getKey()) <p class="mt-1 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($terisi)
                                                <x-ui.badge tone="positif">Ditugaskan</x-ui.badge>
                                            @else
                                                <x-ui.badge tone="peringatan">Belum ditugaskan</x-ui.badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif
    </div>

    @if ($konflik !== [])
        <x-ui.modal title="Penugasan sudah terdaftar" subtitle="Mata pelajaran berikut sudah memiliki guru pengampu. Ganti guru pengampunya?" close="batalGanti">
            <ul class="divide-y divide-hairline rounded border border-hairline">
                @foreach ($konflik as $item)
                    <li class="px-4 py-3 text-[15px] text-body">
                        <span class="font-medium text-heading">{{ $this->daftarMapel->firstWhere('Mapel_ID', $item['mapel'])?->Nama_Mapel }}</span>
                        pada {{ $this->kelas?->Nama_Kelas }}:
                        {{ $this->daftarGuru->firstWhere('User_ID', $item['lama'])?->Nama ?? 'guru lama' }}
                        &rarr; <span class="font-medium text-heading">{{ $this->daftarGuru->firstWhere('User_ID', $item['baru'])?->Nama }}</span>
                    </li>
                @endforeach
            </ul>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" wire:click="batalGanti">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="simpan(true)">Ganti Guru Pengampu</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
