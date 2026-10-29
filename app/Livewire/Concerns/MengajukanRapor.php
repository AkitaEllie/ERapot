<?php

namespace App\Livewire\Concerns;

use App\Enums\RaporStatus;
use App\Models\Rapor;

/**
 * The "Ajukan Rapor" flow shared by every tab of the report input screen.
 *
 * Submitting first checks the whole report, not just the open tab: when
 * something is missing the teacher gets the list of gaps (wireframe G-03a),
 * otherwise a confirmation (G-03b) before the report is locked for review.
 *
 * @property Rapor $rapor
 */
trait MengajukanRapor
{
    /** null, "kurang" (list of gaps) or "konfirmasi". */
    public ?string $modalAjukan = null;

    /** @var array<int, array{bagian: string, keterangan: string, mapel: ?string}> */
    public array $kekurangan = [];

    /**
     * Save hook for the tab the teacher is on, so its unsaved edits count
     * towards the completeness check.
     */
    abstract protected function simpanTabIni(): void;

    public function periksaPengajuan(): void
    {
        if (! $this->bolehMengajukan()) {
            return;
        }

        if (! $this->rapor->bolehDiubah()) {
            session()->flash('galat', 'Rapor sedang menunggu persetujuan Kepala Sekolah.');

            return;
        }

        $this->simpanTabIni();

        $this->kekurangan = $this->rapor->fresh()->kekuranganPengajuan()->all();
        $this->modalAjukan = $this->kekurangan === [] ? 'konfirmasi' : 'kurang';
    }

    public function konfirmasiAjukan(): void
    {
        if (! $this->bolehMengajukan()) {
            return;
        }

        $rapor = $this->rapor->fresh();

        // The report may have changed since the dialog opened, so check again.
        $this->kekurangan = $rapor->kekuranganPengajuan()->all();

        if ($this->kekurangan !== []) {
            $this->modalAjukan = 'kurang';

            return;
        }

        if ($rapor->Status === RaporStatus::Menunggu) {
            $this->tutupModalAjukan();
            session()->flash('galat', 'Rapor sedang menunggu persetujuan Kepala Sekolah.');

            return;
        }

        $rapor->tandaiSedangDiisi();
        $rapor->ajukanPersetujuan();
        $this->rapor = $rapor->fresh();
        $this->tutupModalAjukan();

        session()->flash('sukses', 'Rapor berhasil diajukan untuk persetujuan Kepala Sekolah.');
    }

    public function tutupModalAjukan(): void
    {
        $this->modalAjukan = null;
        $this->kekurangan = [];
    }

    /**
     * The whole report book goes to the principal, so only its owner sends it.
     */
    private function bolehMengajukan(): bool
    {
        if ($this->rapor->milikWaliKelas(auth()->user())) {
            return true;
        }

        session()->flash('galat', 'Rapor hanya dapat diajukan oleh wali kelas.');

        return false;
    }
}
