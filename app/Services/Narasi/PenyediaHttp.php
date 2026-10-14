<?php

namespace App\Services\Narasi;

use App\Contracts\PenyediaNarasi;
use App\Exceptions\NarasiTidakTersedia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared plumbing for HTTP-backed narration providers: the configured key, the
 * model, a base URL, and one place where a failed request turns into
 * NarasiTidakTersedia rather than leaking a transport error to the screen.
 */
abstract class PenyediaHttp implements PenyediaNarasi
{
    /**
     * @param  array{key: ?string, model: ?string, base_url: ?string, timeout?: int}  $konfigurasi
     */
    public function __construct(
        protected array $konfigurasi,
    ) {
        //
    }

    /**
     * The key this provider authenticates with.
     */
    protected function kunci(): string
    {
        $kunci = trim((string) ($this->konfigurasi['key'] ?? ''));

        if ($kunci === '') {
            throw NarasiTidakTersedia::belumDikonfigurasi($this->nama());
        }

        return $kunci;
    }

    /**
     * The model to ask for. A blank value falls back to the provider's default.
     */
    protected function model(): string
    {
        $model = trim((string) ($this->konfigurasi['model'] ?? ''));

        if ($model === '') {
            throw NarasiTidakTersedia::belumDikonfigurasi($this->nama());
        }

        return $model;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->konfigurasi['base_url'] ?? ''), '/');
    }

    /**
     * The request all providers share: JSON in, JSON out, on a timeout.
     */
    protected function klien(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout((int) ($this->konfigurasi['timeout'] ?? 30));
    }

    /**
     * Run a request, turning transport and HTTP failures into one exception type.
     *
     * The provider's own error text is included when it sends any, because it is
     * the most useful thing to show whoever is trying to fix the configuration.
     */
    protected function minta(callable $panggilan): Response
    {
        try {
            $balasan = $panggilan();
        } catch (ConnectionException $e) {
            throw NarasiTidakTersedia::permintaanGagal($this->nama(), $e->getMessage());
        }

        if ($balasan->failed()) {
            throw NarasiTidakTersedia::permintaanGagal($this->nama(), $this->pesanGalat($balasan));
        }

        return $balasan;
    }

    /**
     * Pull a readable message out of an error response, whatever shape it has.
     */
    private function pesanGalat(Response $balasan): string
    {
        $json = $balasan->json();

        $pesan = $json['error']['message']
            ?? $json['error']
            ?? $json['message']
            ?? null;

        if (is_string($pesan) && $pesan !== '') {
            return $pesan;
        }

        return 'HTTP '.$balasan->status();
    }

    /**
     * Reject an empty answer rather than storing it as a draft.
     */
    protected function teksWajib(string $teks): string
    {
        $teks = trim($teks);

        if ($teks === '') {
            throw NarasiTidakTersedia::balasanKosong($this->nama());
        }

        return $teks;
    }

    /**
     * Record what the request cost, including whether the provider served the
     * instructions from cache.
     *
     * Logged rather than stored: it is there to answer "is the cache actually
     * helping?", which cannot be assumed from the prompt alone. The two vendors
     * report it in different shapes, so the caller names the usage field.
     */
    protected function catatPemakaian(Response $balasan, string $kunci = 'usage'): void
    {
        $pemakaian = $balasan->json($kunci);

        if (! is_array($pemakaian)) {
            return;
        }

        Log::debug('Pemakaian penyedia narasi', [
            'penyedia' => $this->nama(),
            'model' => $this->model(),
            'prompt_tokens' => $pemakaian['prompt_tokens'] ?? $pemakaian['promptTokenCount'] ?? null,
            'cached_tokens' => $pemakaian['prompt_tokens_details']['cached_tokens']
                ?? $pemakaian['cachedContentTokenCount']
                ?? null,
            'completion_tokens' => $pemakaian['completion_tokens'] ?? $pemakaian['candidatesTokenCount'] ?? null,
        ]);
    }
}
