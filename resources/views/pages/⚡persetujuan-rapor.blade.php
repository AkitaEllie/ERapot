<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kepala Sekolah: the decisions already taken on this semester's reports,
 * wireframe K-02. Reviewing the ones still waiting happens on Review Draf Rapor.
 *
 * The ERD keeps only a report's current state, so a report the teacher has
 * resubmitted leaves this list until it is decided again.
 */
new #[Layout('layouts::app', ['active' => 'persetujuan'])]
class extends Component
{
    use WithPagination;

    /** "", "disetujui" or "perlu_revisi". */
    #[Url(as: 'keputusan')]
    public string $tab = '';

    #[Url(as: 'kelas')]
    public string $filterKelas = '';

    #[Url(as: 'q')]
    public string $cari = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function pilihTab(string $tab): void
    {
        $this->tab = in_array($tab, ['disetujui', 'perlu_revisi'], true) ? $tab : '';
        $this->resetPage();
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
            ->orderBy('Nama_Kelas')
            ->get();
    }

    /** @return Builder<Rapor> */
    private function dasar(): Builder
    {
        return Rapor::query()
            ->whereIn('Status', [RaporStatus::Disetujui, RaporStatus::PerluRevisi])
            ->when($this->periode, fn ($query) => $query
                ->where('Semester', $this->periode->Semester_Aktif)
                ->whereIn('Kelas_ID', $this->daftarKelas->pluck('Kelas_ID')));
    }

    /** @return array<string, int> */
    #[Computed]
    public function jumlah(): array
    {
        $per = $this->dasar()->selectRaw('Status, count(*) as jumlah')->groupBy('Status')->pluck('jumlah', 'Status');

        return [
            'disetujui' => (int) ($per[RaporStatus::Disetujui->value] ?? 0),
            'perlu_revisi' => (int) ($per[RaporStatus::PerluRevisi->value] ?? 0),
        ];
    }

    /** @return LengthAwarePaginator<int, Rapor> */
    #[Computed]
    public function keputusan(): LengthAwarePaginator
    {
        return $this->dasar()
            ->with(['siswa', 'kelas.waliKelas', 'disetujuiOleh'])
            ->when($this->tab !== '', fn ($query) => $query->where('Status', $this->tab))
            ->when($this->filterKelas !== '', fn ($query) => $query->where('Kelas_ID', $this->filterKelas))
            ->when($this->cari !== '', fn ($query) => $query->whereHas('siswa', fn ($q) => $q->where('Nama', 'like', '%'.$this->cari.'%')))
            ->orderByDesc('updated_at')
            ->paginate(10);
    }
};

?>

<div>
    <x-app.header
        title="Persetujuan Rapor"
        :subtitle="'Riwayat keputusan persetujuan - Semester '.($this->periode?->Semester_Aktif?->label() ?? '-').' '.($this->periode?->Tahun_Ajaran ?? '')"
    />

    <nav class="flex h-[52px] shrink-0 items-end gap-8 border-b border-hairline bg-surface px-8" aria-label="Keputusan">
        @foreach (['' => 'Semua ('.array_sum($this->jumlah).')', 'disetujui' => 'Disetujui ('.$this->jumlah['disetujui'].')', 'perlu_revisi' => 'Perlu Revisi ('.$this->jumlah['perlu_revisi'].')'] as $kunci => $label)
            <button type="button" wire:click="pilihTab('{{ $kunci }}')"
                @class([
                    'flex h-full items-center border-b-[3px] text-[15px] transition-colors',
                    'border-accent font-medium text-accent-deep' => $tab === $kunci,
                    'border-transparent text-muted hover:text-label' => $tab !== $kunci,
                ])
                @if ($tab === $kunci) aria-current="page" @endif
            >{{ $label }}</button>
        @endforeach
    </nav>

    <div class="flex shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8 py-4">
        <div class="w-[220px]">
            <x-ui.select wire:model.live="filterKelas" aria-label="Kelas">
                <option value="">Semua kelas</option>
                @foreach ($this->daftarKelas as $kelas)
                    <option value="{{ $kelas->getKey() }}">{{ $kelas->Nama_Kelas }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-[320px]"><x-ui.input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama siswa..." aria-label="Cari siswa" /></div>
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                        <th class="w-56 px-4 py-3 text-left font-medium">Siswa</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Diajukan oleh</th>
                        <th class="w-36 px-4 py-3 text-left font-medium">Keputusan</th>
                        <th class="w-32 px-4 py-3 text-left font-medium">Tanggal</th>
                        <th class="px-4 py-3 text-left font-medium">Catatan Revisi</th>
                        <th class="w-32 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->keputusan as $rapor)
                        <tr wire:key="{{ $rapor->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $this->keputusan->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 leading-tight">
                                <p class="text-[15px] text-heading">{{ $rapor->siswa->Nama }}</p>
                                <p class="text-[13px] text-muted">{{ $rapor->kelas->Nama_Kelas }}</p>
                            </td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $rapor->kelas->waliKelas?->Nama ?? '-' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ ($rapor->Tanggal_Persetujuan ?? $rapor->updated_at)?->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3 text-[14px] text-muted">{{ $rapor->Status === RaporStatus::PerluRevisi ? $rapor->Catatan_Revisi : '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.button-link size="sm" :href="route('rapor.pratinjau', $rapor)">Lihat Rapor</x-ui.button-link>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[15px] text-muted">Belum ada keputusan persetujuan pada semester ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-ui.table-footer :paginator="$this->keputusan" satuan="keputusan" />
        </div>
    </div>
</div>
