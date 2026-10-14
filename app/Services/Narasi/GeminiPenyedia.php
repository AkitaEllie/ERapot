<?php

namespace App\Services\Narasi;

use App\Exceptions\NarasiTidakTersedia;

/**
 * Google's Gemini generateContent endpoint.
 *
 * Despite the response walking into nested arrays, the shape is stable and
 * documented: candidates[0].content.parts[0].text.
 */
class GeminiPenyedia extends PenyediaHttp
{
    public function nama(): string
    {
        return 'gemini';
    }

    public function kirimPrompt(string $prompt): string
    {
        $balasan = $this->minta(fn () => $this->klien()->post(
            $this->alamat(),
            [
                // Gemini carries the instructions in its own field rather than as
                // a message. Keeping them identical on every call lets the API
                // serve them from its cache.
                'systemInstruction' => [
                    'parts' => [['text' => InstruksiNarasi::SISTEM]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
            ],
        ));

        $teks = $balasan->json('candidates.0.content.parts.0.text');

        if (! is_string($teks)) {
            throw NarasiTidakTersedia::balasanKosong($this->nama());
        }

        $this->catatPemakaian($balasan, 'usageMetadata');

        return $this->teksWajib($teks);
    }

    /**
     * The key travels as a query parameter here, not a header, as the API expects.
     */
    private function alamat(): string
    {
        return sprintf(
            '%s/v1beta/models/%s:generateContent?key=%s',
            $this->baseUrl(),
            $this->model(),
            urlencode($this->kunci()),
        );
    }
}
