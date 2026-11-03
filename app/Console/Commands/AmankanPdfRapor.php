<?php

namespace App\Console\Commands;

use App\Models\Rapor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Takes report PDFs off the public disk, where anyone could fetch them by URL.
 *
 * A one-off step for installs that exported before PDFs moved to private storage.
 * It is a command rather than a migration because migrations also run against an
 * empty database (the test suite, migrate:fresh), where every stored file would
 * look unclaimed and be deleted.
 */
class AmankanPdfRapor extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rapor:amankan-pdf';

    /**
     * @var string
     */
    protected $description = 'Pindahkan PDF rapor dari penyimpanan publik ke penyimpanan privat';

    public function handle(): int
    {
        $publik = Storage::disk('public');
        $dipindah = 0;
        $dihapus = 0;

        foreach ($publik->files('rapor') as $berkas) {
            // A file no report claims may belong to another child: the download
            // route rebuilds it on demand, so it is removed rather than kept.
            if (Rapor::query()->where('File_PDF', $berkas)->exists()) {
                Storage::disk(Rapor::DISK_PDF)->writeStream($berkas, $publik->readStream($berkas));
                $dipindah++;
            } else {
                $dihapus++;
            }

            $publik->delete($berkas);
        }

        $this->info("{$dipindah} PDF dipindahkan ke penyimpanan privat, {$dihapus} PDF tanpa pemilik dihapus.");

        return self::SUCCESS;
    }
}
