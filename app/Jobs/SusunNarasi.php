<?php

namespace App\Jobs;

use App\Exceptions\NarasiTidakTersedia;
use App\Models\Narasi;
use App\Services\GeminiApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks the AI provider for a narrative outside the web request.
 *
 * A provider can take well over a minute to answer, which outlives PHP's web
 * execution limit and leaves the teacher waiting. Run here, the teacher can keep
 * working or leave the page, and the result is stored whenever it arrives.
 *
 * Not retried: a repeat call costs money, and the teacher can simply press the
 * button again after seeing the failure.
 */
class SusunNarasi implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    /**
     * Long enough for the provider's own timeout to fire first, so a slow answer
     * fails with a clear cause rather than the worker killing the job. Must stay
     * below retry_after in config/queue.php, or the job could run twice.
     */
    public int $timeout;

    public function __construct(
        public string $narasiId,
        public string $token,
    ) {
        $this->timeout = (int) config('ai.providers.'.config('ai.default').'.timeout', 120) + 30;
    }

    public function handle(): void
    {
        $narasi = Narasi::query()->find($this->narasiId);

        if ($narasi === null || ! $this->hasilMasihDipakai($narasi)) {
            return;
        }

        try {
            $hasil = app(GeminiApi::class)->kirimPrompt($narasi->susunPrompt());
        } catch (NarasiTidakTersedia $e) {
            Log::warning('Narasi gagal dibuat', ['narasi' => $this->narasiId, 'sebab' => $e->getMessage()]);
            $narasi->gagalGenerate($this->token, $e->pesanUntukPengguna());

            return;
        }

        // Much can happen during the wait: read the row again rather than trusting
        // the copy from before the call.
        $narasi = $narasi->fresh();

        if ($this->hasilMasihDipakai($narasi)) {
            $narasi->selesaiGenerate($this->token, $hasil);
            $narasi->rapor->tandaiSedangDiisi();
        }
    }

    /**
     * A report locked in the meantime keeps its submitted text, and whoever is
     * waiting needs releasing. A cancelled or replaced request is left alone: its
     * token no longer matches, so the failure below changes nothing.
     */
    private function hasilMasihDipakai(Narasi $narasi): bool
    {
        if ($narasi->masihDitunggu($this->token)) {
            return true;
        }

        $narasi->gagalGenerate($this->token, 'Hasil AI tidak dipakai karena rapor sudah diajukan.');

        return false;
    }

    /**
     * Anything unexpected, including the worker killing the job, must still release
     * the teacher from the waiting state.
     */
    public function failed(?Throwable $e): void
    {
        Log::error('Narasi gagal dibuat', ['narasi' => $this->narasiId, 'sebab' => $e?->getMessage()]);

        Narasi::query()->find($this->narasiId)?->gagalGenerate(
            $this->token,
            'Narasi belum dapat dibuat. Coba lagi beberapa saat, atau tulis narasinya sendiri.',
        );
    }
}
