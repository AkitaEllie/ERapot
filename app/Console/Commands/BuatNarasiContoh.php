<?php

namespace App\Console\Commands;

use App\Exceptions\NarasiTidakTersedia;
use App\Models\Narasi;
use App\Services\GeminiApi;
use Database\Seeders\DataContohRapor;
use Illuminate\Console\Command;

/**
 * Generates the demo narratives once and stores them, so seeding stays offline.
 *
 * Needs a seeded database, since prompts are built from its rows. Narratives
 * already in the file are kept, so an interrupted run can simply be started again.
 */
class BuatNarasiContoh extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rapor:contoh-narasi
        {--semua : Buat ulang narasi yang sudah ada}
        {--berkas= : Berkas tujuan, bawaannya database/seeders/data/narasi-contoh.json}';

    /**
     * @var string
     */
    protected $description = 'Buat narasi data contoh dengan layanan AI dan simpan untuk seeder';

    public function handle(GeminiApi $ai): int
    {
        $berkas = $this->option('berkas') ?: DataContohRapor::BERKAS_NARASI;
        $hasil = is_file($berkas) && ! $this->option('semua')
            ? json_decode((string) file_get_contents($berkas), true, flags: JSON_THROW_ON_ERROR)
            : [];
        $gagal = 0;

        foreach (DataContohRapor::butuhNarasi() as [$nisn, $kunci]) {
            if (isset($hasil[$nisn][$kunci])) {
                continue;
            }

            $narasi = Narasi::query()
                ->whereRelation('rapor.siswa', 'NISN', $nisn)
                ->whereRelation('mataPelajaran', 'Nama_Mapel', DataContohRapor::MAPEL[$kunci])
                ->first();

            if ($narasi === null) {
                $this->warn("{$nisn} {$kunci}: belum ada di database, jalankan seeder dulu.");
                $gagal++;

                continue;
            }

            try {
                $hasil[$nisn][$kunci] = $ai->kirimPrompt($narasi->susunPrompt());
                $this->line("{$nisn} {$kunci}: ".str_word_count($hasil[$nisn][$kunci]).' kata');
                // Written after every answer, so a failure loses nothing already paid for.
                file_put_contents($berkas, json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
            } catch (NarasiTidakTersedia $e) {
                $this->warn("{$nisn} {$kunci}: {$e->getMessage()}");
                $gagal++;
            }
        }

        if ($gagal > 0) {
            $this->error("{$gagal} narasi gagal dibuat. Jalankan lagi untuk melanjutkan.");

            return self::FAILURE;
        }

        $this->info('Narasi contoh tersimpan.');

        return self::SUCCESS;
    }
}
