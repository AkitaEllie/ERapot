<?php

namespace App\Console\Commands;

use App\Models\Dokumentasi;
use App\Models\Rapor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Takes report PDFs and student photos off the public disk, where anyone could
 * fetch them by URL.
 *
 * A one-off step for installs from before both moved to private storage. It is a
 * command rather than a migration because migrations also run against an empty
 * database (the test suite, migrate:fresh), where every stored file would look
 * unclaimed and be deleted.
 */
class AmankanBerkasRapor extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rapor:amankan-berkas';

    /**
     * @var string
     */
    protected $description = 'Pindahkan PDF rapor dan foto dokumentasi dari penyimpanan publik ke penyimpanan privat';

    public function handle(): int
    {
        $publik = Storage::disk('public');
        $dipindah = 0;
        $dihapus = 0;

        // A file no record claims serves no one and may show another child, so it
        // leaves the public disk either way. PDFs are rebuilt on demand.
        foreach ($publik->files('rapor') as $berkas) {
            $milikRapor = Rapor::query()->where('File_PDF', $berkas)->exists();
            $milikRapor ? $dipindah++ : $dihapus++;

            $this->keluarkan($berkas, $milikRapor ? Rapor::DISK_PDF : null);
        }

        foreach ($publik->files('dokumentasi') as $berkas) {
            $milikDokumentasi = Dokumentasi::query()->where('Foto', $berkas)->exists();
            $milikDokumentasi ? $dipindah++ : $dihapus++;

            $this->keluarkan($berkas, $milikDokumentasi ? Dokumentasi::DISK : null);
        }

        $this->info("{$dipindah} berkas dipindahkan ke penyimpanan privat, {$dihapus} berkas tanpa pemilik dihapus.");

        return self::SUCCESS;
    }

    /**
     * Remove a file from the public disk, copying it to the given disk first.
     */
    private function keluarkan(string $berkas, ?string $tujuan): void
    {
        $publik = Storage::disk('public');

        if ($tujuan !== null) {
            Storage::disk($tujuan)->writeStream($berkas, $publik->readStream($berkas));
        }

        $publik->delete($berkas);
    }
}
