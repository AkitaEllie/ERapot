<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app', ['active' => 'review'])]
class extends \Livewire\Component
{
    public ?string $raporDipilih = null;

    public string $catatanRevisi = '';

    #[Computed]
    public function periode(): ?TahunAjaran
    {
        return TahunAjaran::query()->where('Is_Active', true)->first();
    }

    /** @return Collection<int, Rapor> */
    #[Computed]
    public function menunggu(): Collection
    {
        return Rapor::query()
            ->where('Status', RaporStatus::Menunggu)
            ->where('Semester', $this->periode?->Semester_Aktif?->value)
            ->with(['siswa', 'kelas'])
            ->orderBy('Rapor_ID')
            ->get();
    }

    public function pilih(string $raporId): void
    {
        $this->raporDipilih = $raporId;
        $this->catatanRevisi = $this->rapor?->Catatan_Revisi ?? '';
    }

    #[Computed]
    public function rapor(): ?Rapor
    {
        if ($this->raporDipilih === null) {
            return null;
        }

        return Rapor::query()
            ->with(['siswa', 'kelas', 'narasi' => fn ($query) => $query
                ->with(['mataPelajaran', 'dokumentasi'])
                ->orderBy('Mapel_ID')])
            ->find($this->raporDipilih);
    }

    public function setujui(): void
    {
        $rapor = $this->rapor;

        if ($rapor === null) {
            return;
        }

        $rapor->setujui(auth()->user());

        session()->flash('sukses', 'Rapor '.$rapor->siswa->Nama.' disetujui.');
        $this->reset('raporDipilih', 'catatanRevisi');
    }

    public function kirimRevisi(): void
    {
        $rapor = $this->rapor;

        if ($rapor === null) {
            return;
        }

        if (trim($this->catatanRevisi) === '') {
            session()->flash('galat', 'Isi catatan revisi sebelum mengembalikan rapor ke guru.');

            return;
        }

        $rapor->kembalikanRevisi($this->catatanRevisi);

        session()->flash('sukses', 'Rapor '.$rapor->siswa->Nama.' dikembalikan ke guru untuk diperbaiki.');
        $this->reset('raporDipilih', 'catatanRevisi');
    }
};

?>

<div>
    <x-app.header
        title="Review Draf Rapor"
        :subtitle="$this->rapor?->kelas->Nama_Kelas.' - '.$this->rapor?->siswa->Nama"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" wire:click="kirimRevisi">Kirim Revisi</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="setujui">Setujui Rapor</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    @if (session('sukses'))
        <div class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="border-b border-hairline bg-rose-50 px-8 py-2.5 text-[14px] text-rose-800">{{ session('galat') }}</div>
    @endif

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-[340px_764px] items-start gap-6">
            {{-- Queue --}}
            <section class="overflow-hidden rounded border border-hairline bg-surface">
                <header class="flex h-[44px] items-center bg-canvas px-4">
                    <h2 class="text-[15px] font-semibold text-heading">Menunggu Persetujuan ({{ $this->menunggu->count() }})</h2>
                </header>

                <ul class="max-h-[836px] divide-y divide-hairline overflow-y-auto">
                    @forelse ($this->menunggu as $rapor)
                        <li>
                            <button type="button" wire:click="pilih('{{ $rapor->getKey() }}')"
                                @class([
                                    'flex w-full flex-col items-start gap-0.5 px-4 py-3 text-left transition-colors',
                                    'bg-accent-soft' => $raporDipilih === $rapor->getKey(),
                                    'hover:bg-canvas' => $raporDipilih !== $rapor->getKey(),
                                ])
                            >
                                <span class="text-[15px] text-heading">{{ $rapor->siswa->Nama }}</span>
                                <span class="text-[13px] text-muted">{{ $rapor->kelas->Nama_Kelas }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="px-4 py-12 text-center text-[15px] text-muted">Tidak ada rapor menunggu persetujuan.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Detail --}}
            <section class="overflow-hidden rounded border border-hairline bg-surface">
                @php $rapor = $this->rapor; @endphp

                @if ($rapor === null)
                    <p class="px-4 py-24 text-center text-[15px] text-muted">Pilih rapor dari daftar untuk ditinjau.</p>
                @else
                    <header class="flex h-[64px] items-center justify-between bg-canvas px-4">
                        <div class="leading-tight">
                            <h2 class="text-[16px] font-semibold text-heading">{{ $rapor->siswa->Nama }} - NISN {{ $rapor->siswa->NISN }}</h2>
                            <p class="text-[13px] text-muted">
                                {{-- Not on ERD/CD: the ERD has no submission timestamp, so the
                                     report's creation time stands in for the submission date. --}}
                                Diajukan oleh {{ $rapor->kelas->waliKelas?->Nama ?? 'guru' }} - {{ $rapor->created_at?->format('j F Y') }}
                            </p>
                        </div>
                        <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
                    </header>

                    <div class="space-y-4 p-4">
                        @foreach ($rapor->narasi as $narasi)
                            <article class="rounded border border-hairline p-4">
                                <h3 class="text-[15px] font-semibold text-heading">{{ $narasi->mataPelajaran?->Nama_Mapel ?? 'Mata Pelajaran' }}</h3>
                                <p class="mt-1.5 text-[15px] leading-relaxed text-body">
                                    {{ $narasi->Narasi_Final ?: $narasi->Draft_Narasi ?: 'Belum ada narasi.' }}
                                </p>

                                @if ($narasi->dokumentasi->isNotEmpty())
                                    <div class="mt-3 flex gap-3">
                                        @foreach ($narasi->dokumentasi as $foto)
                                            <img src="{{ Storage::url($foto->Foto) }}" alt="{{ $foto->Keterangan }}" class="h-[52px] w-[110px] rounded border border-hairline object-cover">
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="border-t border-hairline p-4">
                        <h3 class="text-[15px] font-semibold text-heading">Catatan Revisi</h3>
                        <p class="mt-1 text-[13px] text-muted">Isi kolom ini bila rapor dikembalikan ke guru untuk diperbaiki</p>

                        <textarea wire:model="catatanRevisi" rows="4"
                            placeholder="Contoh: narasi Jati Diri perlu diperbaiki tata bahasanya"
                            class="mt-2 w-full resize-none rounded border border-hairline bg-surface px-4 py-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none"></textarea>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
