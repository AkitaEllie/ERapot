<?php

use App\Livewire\Concerns\MengajukanRapor;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The growth and attendance tab of the report input screen, wireframe G-01.
 *
 * Only the class's homeroom teacher fills it in; a subject teacher sees the
 * same tab with the form switched off and the reason stated.
 */
new #[Layout('layouts::app', ['active' => 'rapor'])]
class extends Component
{
    use MengajukanRapor;

    /** Plausible ranges for a child aged 2 to 7, used to catch typing slips. */
    public const BATAS = [
        'tinggi' => [60, 150],
        'berat' => [8, 45],
        'lingkar' => [35, 60],
    ];

    public Rapor $rapor;

    public string $tinggi = '';

    public string $berat = '';

    public string $lingkar = '';

    public string $sakit = '0';

    public string $izin = '0';

    public string $tanpaKeterangan = '0';

    public function mount(): void
    {
        abort_unless($this->rapor->bolehDibukaOleh(auth()->user()), 403);

        $this->tinggi = (string) $this->rapor->Tinggi_Badan;
        $this->berat = (string) $this->rapor->Berat_Badan;
        $this->lingkar = (string) $this->rapor->Lingkar_Kepala;
        $this->sakit = (string) $this->rapor->Sakit;
        $this->izin = (string) $this->rapor->Izin;
        $this->tanpaKeterangan = (string) $this->rapor->Tanpa_Keterangan;
    }

    #[Computed]
    public function waliKelas(): bool
    {
        return $this->rapor->milikWaliKelas(auth()->user());
    }

    /** @return Collection<int, MataPelajaran> */
    #[Computed]
    public function daftarMapel(): Collection
    {
        return Pembelajaran::query()
            ->where('Kelas_ID', $this->rapor->Kelas_ID)
            ->unless($this->waliKelas, fn ($query) => $query->where('User_ID', auth()->id()))
            ->with('mataPelajaran')
            ->get()
            ->pluck('mataPelajaran')
            ->filter()
            ->unique('Mapel_ID')
            ->values();
    }

    public function simpan(): void
    {
        if (! $this->waliKelas) {
            session()->flash('galat', 'Data fisik dan kehadiran hanya dapat diisi oleh wali kelas.');

            return;
        }

        if (! $this->rapor->bolehDiubah()) {
            session()->flash('galat', 'Rapor sedang menunggu persetujuan atau sudah disetujui, sehingga tidak dapat diubah.');

            return;
        }

        $this->simpanTabIni();

        session()->flash('sukses', 'Data berhasil disimpan.');
    }

    protected function simpanTabIni(): void
    {
        if (! $this->waliKelas) {
            return;
        }

        [$tMin, $tMax] = self::BATAS['tinggi'];
        [$bMin, $bMax] = self::BATAS['berat'];
        [$lMin, $lMax] = self::BATAS['lingkar'];

        // Accept a decimal comma, the way teachers write measurements.
        foreach (['tinggi', 'berat', 'lingkar'] as $kolom) {
            $this->{$kolom} = str_replace(',', '.', trim($this->{$kolom}));
        }

        $data = $this->validate([
            'tinggi' => ['required', 'numeric', "between:{$tMin},{$tMax}"],
            'berat' => ['required', 'numeric', "between:{$bMin},{$bMax}"],
            'lingkar' => ['required', 'numeric', "between:{$lMin},{$lMax}"],
            'sakit' => ['required', 'integer', 'between:0,200'],
            'izin' => ['required', 'integer', 'between:0,200'],
            'tanpaKeterangan' => ['required', 'integer', 'between:0,200'],
        ], [
            'tinggi.between' => "Nilai di luar batas wajar ({$tMin} - {$tMax} cm).",
            'berat.between' => "Nilai di luar batas wajar ({$bMin} - {$bMax} kg).",
            'lingkar.between' => "Nilai di luar batas wajar ({$lMin} - {$lMax} cm).",
        ], [
            'tinggi' => 'tinggi badan',
            'berat' => 'berat badan',
            'lingkar' => 'lingkar kepala',
            'sakit' => 'jumlah hari sakit',
            'izin' => 'jumlah hari izin',
            'tanpaKeterangan' => 'jumlah hari tanpa keterangan',
        ]);

        $this->rapor->isiDataFisik([
            'Tinggi_Badan' => $data['tinggi'],
            'Berat_Badan' => $data['berat'],
            'Lingkar_Kepala' => $data['lingkar'],
            'Sakit' => (int) $data['sakit'],
            'Izin' => (int) $data['izin'],
            'Tanpa_Keterangan' => (int) $data['tanpaKeterangan'],
        ]);
    }
};

?>

<div>
    <x-app.header
        :title="'Input Rapor - '.$rapor->siswa->Nama"
        :subtitle="sprintf('%s - NISN %s - Semester %s %s', $rapor->kelas->Nama_Kelas, $rapor->siswa->NISN, $rapor->Semester->label(), $rapor->kelas->tahunAjaran->Tahun_Ajaran)"
    >
        <x-slot:actions>
            <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
            @if ($this->waliKelas)
                <x-ui.button type="button" variant="primary" wire:click="periksaPengajuan" :disabled="! $rapor->bolehDiubah()">{{ $rapor->Status === \App\Enums\RaporStatus::PerluRevisi ? 'Ajukan Ulang' : 'Ajukan Rapor' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <x-rapor.tab-input :rapor="$rapor" :daftar-mapel="$this->daftarMapel" aktif="data-fisik" />

    <div class="flex-1 overflow-y-auto bg-canvas">
        <x-rapor.status-banner :rapor="$rapor" />

        <div class="px-8 py-6">
            @if (! $this->waliKelas)
                <div class="mb-5 flex items-center gap-3 rounded border border-hairline-strong bg-notice-bg px-4 py-3 text-[14px] font-medium text-label" role="note">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0" aria-hidden="true"><rect x="5" y="9" width="10" height="7.5" rx="1.5"/><path d="M7 9V6.5a3 3 0 0 1 6 0V9"/></svg>
                    Data fisik dan kehadiran hanya dapat diisi oleh wali kelas ({{ $rapor->kelas->waliKelas?->Nama ?? 'belum ditentukan' }}).
                </div>
            @else
                <div class="mb-5 rounded border border-hairline bg-notice-bg px-4 py-3 text-[14px] text-label">
                    Data ini tampil pada bagian identitas dan ketidakhadiran di dokumen rapor.
                </div>
            @endif

            @php $nonaktif = ! $this->waliKelas || ! $rapor->bolehDiubah(); @endphp

            <div class="grid grid-cols-[1fr_360px] items-start gap-6">
                <form wire:submit="simpan" class="space-y-5">
                    <fieldset @disabled($nonaktif) class="space-y-5">
                        <section class="rounded border border-hairline bg-surface p-6">
                            <h2 class="text-[16px] font-semibold text-heading">Pertumbuhan Fisik</h2>
                            <p class="mt-1 text-[14px] text-muted">Diukur pada akhir semester berjalan.</p>

                            <div class="mt-5 grid grid-cols-3 gap-4">
                                <x-ui.field label="Tinggi Badan (cm)" for="tinggi" error="tinggi">
                                    <x-ui.input id="tinggi" wire:model="tinggi" inputmode="decimal" :invalid="$errors->has('tinggi')" />
                                </x-ui.field>
                                <x-ui.field label="Berat Badan (kg)" for="berat" error="berat">
                                    <x-ui.input id="berat" wire:model="berat" inputmode="decimal" :invalid="$errors->has('berat')" />
                                </x-ui.field>
                                <x-ui.field label="Lingkar Kepala (cm)" for="lingkar" error="lingkar">
                                    <x-ui.input id="lingkar" wire:model="lingkar" inputmode="decimal" :invalid="$errors->has('lingkar')" />
                                </x-ui.field>
                            </div>
                        </section>

                        <section class="rounded border border-hairline bg-surface p-6">
                            <h2 class="text-[16px] font-semibold text-heading">Rekap Ketidakhadiran</h2>
                            <p class="mt-1 text-[14px] text-muted">Jumlah hari dalam satu semester, direkap dari absensi kelas.</p>

                            <div class="mt-5 grid grid-cols-3 gap-4">
                                <x-ui.field label="Sakit (hari)" for="sakit" error="sakit">
                                    <x-ui.input type="number" min="0" id="sakit" wire:model="sakit" :invalid="$errors->has('sakit')" />
                                </x-ui.field>
                                <x-ui.field label="Izin (hari)" for="izin" error="izin">
                                    <x-ui.input type="number" min="0" id="izin" wire:model="izin" :invalid="$errors->has('izin')" />
                                </x-ui.field>
                                <x-ui.field label="Tanpa Keterangan (hari)" for="tanpa-keterangan" error="tanpaKeterangan">
                                    <x-ui.input type="number" min="0" id="tanpa-keterangan" wire:model="tanpaKeterangan" :invalid="$errors->has('tanpaKeterangan')" />
                                </x-ui.field>
                            </div>
                        </section>

                        <div class="flex justify-end gap-3">
                            <x-ui.button type="submit" variant="primary" :disabled="$nonaktif">Simpan</x-ui.button>
                        </div>
                    </fieldset>
                </form>

                <aside class="rounded border border-hairline bg-surface p-5">
                    <h2 class="text-[16px] font-semibold text-heading">Pratinjau di Rapor</h2>
                    <dl class="mt-4 space-y-2.5 text-[14px]">
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-muted">Tinggi / Berat</dt>
                            <dd class="text-body">: {{ $rapor->Tinggi_Badan ?? '-' }} cm / {{ $rapor->Berat_Badan ?? '-' }} kg</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-muted">Lingkar Kepala</dt>
                            <dd class="text-body">: {{ $rapor->Lingkar_Kepala ?? '-' }} cm</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-muted">Ketidakhadiran</dt>
                            <dd class="text-body">: Sakit {{ $rapor->Sakit }} hari, Izin {{ $rapor->Izin }} hari, Tanpa Keterangan {{ $rapor->Tanpa_Keterangan }} hari</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </div>
    </div>

    <x-rapor.modal-ajukan :rapor="$rapor" :modal="$modalAjukan" :kekurangan="$kekurangan" />
</div>
