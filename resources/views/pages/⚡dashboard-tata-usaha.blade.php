<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app', ['active' => 'dashboard'])]
class extends \Livewire\Component
{
    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->withCount('kelas')->first();
    }

    #[Computed]
    public function semester(): string
    {
        return $this->periode?->Semester_Aktif?->label() ?? '-';
    }

    #[Computed]
    public function siswaAktif(): int
    {
        return Rapor::query()
            ->where('Kelas_ID', '!=', null)
            ->whereIn('Status', [RaporStatus::BelumDiisi, RaporStatus::Draft, RaporStatus::Menunggu, RaporStatus::PerluRevisi, RaporStatus::Disetujui])
            ->distinct()
            ->count('Siswa_ID');
    }

    #[Computed]
    public function totalSiswa(): int
    {
        return Siswa::query()->count();
    }

    #[Computed]
    public function jumlahKelas(): int
    {
        return Kelas::query()->count();
    }

    #[Computed]
    public function jumlahGuru(): int
    {
        return User::query()->where('Role_ID', 'GURU')->count();
    }

    #[Computed]
    public function jumlahMapel(): int
    {
        return MataPelajaran::query()->count();
    }

    /** @return Collection<int, array{label: string, jumlah: int, target: string}> */
    #[Computed]
    public function perluDikerjakan(): Collection
    {
        $periode = $this->periode;

        if ($periode === null) {
            return collect();
        }

        $semester = $periode->Semester_Aktif?->value;

        $belumDitempatkan = Siswa::belumDitempatkan($periode->Semester_Aktif)->count();

        $kelasTanpaWali = Kelas::query()->whereNull('User_ID')->count();

        $mapelTanpaGuru = Pembelajaran::query()
            ->whereNull('User_ID')
            ->whereIn('Kelas_ID', Kelas::query()->select('Kelas_ID'))
            ->count();

        return collect([
            [
                'label' => $belumDitempatkan.' siswa belum ditempatkan ke kelas',
                'jumlah' => $belumDitempatkan,
                'target' => 'Data Siswa',
            ],
            [
                'label' => $kelasTanpaWali.' kelas belum punya wali kelas',
                'jumlah' => $kelasTanpaWali,
                'target' => 'Data Kelas',
            ],
            [
                'label' => $mapelTanpaGuru.' mapel belum punya guru pengampu',
                'jumlah' => $mapelTanpaGuru,
                'target' => 'Penugasan Mengajar',
            ],
        ])->filter(fn (array $item): bool => $item['jumlah'] > 0)->values();
    }

    public function gantiSemester(): void
    {
        $periode = $this->periode;

        if ($periode === null) {
            return;
        }

        $semesterBaru = $periode->Semester_Aktif === Semester::Ganjil ? Semester::Genap : Semester::Ganjil;
        $sebelum = Rapor::query()->where('Semester', $semesterBaru)->count();

        $periode->gantiSemester();

        // TahunAjaran::gantiSemester() is void per the class diagram, so the count
        // the dashboard reports is measured here instead.
        $dibuat = Rapor::query()->where('Semester', $semesterBaru)->count() - $sebelum;

        session()->flash(
            'sukses',
            'Semester diganti ke '.$periode->Semester_Aktif->label().'. '
            .$dibuat.' draf rapor dibuat untuk siswa yang sudah ditempatkan.',
        );
    }
};

?>

<div>
    <x-app.header
        title="Dashboard"
        :subtitle="($this->periode?->Tahun_Ajaran ?? 'Belum ada tahun ajaran').' - Semester '.$this->semester.' (aktif)'"
    />

    @if (session('sukses'))
        <div class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
    @endif

    <div class="flex-1 space-y-5 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-4 gap-5">
            <x-ui.card :label="'Siswa aktif'" :value="$this->siswaAktif" />
            <x-ui.card label="Kelas" :value="$this->jumlahKelas" />
            <x-ui.card label="Guru" :value="$this->jumlahGuru" />
            <x-ui.card label="Mata Pelajaran" :value="$this->jumlahMapel" />
        </div>

        <div class="grid grid-cols-2 gap-5">
            <section class="rounded border border-hairline bg-surface p-6">
                <h2 class="text-[16px] font-semibold text-heading">Periode Aktif</h2>

                <dl class="mt-4 space-y-3 text-[15px]">
                    <div class="flex gap-3">
                        <dt class="w-32 text-muted">Tahun Ajaran</dt>
                        <dd class="text-body">{{ $this->periode?->Tahun_Ajaran ?? '-' }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-32 text-muted">Semester</dt>
                        <dd class="text-body">{{ $this->semester }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-32 text-muted">Periode</dt>
                        <dd class="text-body">
                            @if ($this->periode?->Tanggal_Mulai)
                                berlangsung {{ $this->periode->Tanggal_Mulai->format('j F Y') }} - {{ $this->periode->Tanggal_Selesai?->format('j F Y') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                </dl>

                <x-ui.button
                    type="button"
                    variant="primary"
                    class="mt-6 w-[240px] justify-center"
                    wire:click="gantiSemester"
                    wire:confirm="Mengganti semester akan membuat draf rapor baru untuk semua siswa. Lanjutkan?"
                >Ganti ke Semester {{ ($this->periode?->Semester_Aktif) === \App\Enums\Semester::Ganjil ? 'Genap' : 'Ganjil' }}</x-ui.button>

                <p class="mt-3 text-[13px] text-muted">Mengganti semester akan membuat draf rapor baru untuk semua siswa</p>
            </section>

            <section class="rounded border border-hairline bg-surface p-6">
                <h2 class="text-[16px] font-semibold text-heading">Perlu Dikerjakan</h2>

                @forelse ($this->perluDikerjakan as $item)
                    <div class="mt-4 flex items-center gap-3">
                        <span class="h-12 w-[8px] shrink-0 rounded-full {{ $item['jumlah'] > 0 ? 'bg-accent' : 'bg-sunken' }}"></span>
                        <p class="flex-1 text-[15px] text-body">{{ $item['label'] }}</p>
                        <x-ui.button type="button" variant="secondary" size="sm">Buka</x-ui.button>
                    </div>
                @empty
                    <p class="mt-6 text-[15px] text-muted">Semua beres. Tidak ada tugas tertunda. Tidak ada tugas tertunda.</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
