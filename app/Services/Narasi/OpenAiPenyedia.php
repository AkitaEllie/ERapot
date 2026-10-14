<?php

namespace App\Services\Narasi;

use App\Exceptions\NarasiTidakTersedia;

/**
 * The OpenAI chat-completions shape.
 *
 * Several hosts speak this, so the base URL is configuration rather than a
 * constant: OpenAI itself, Groq, Together, or a local Ollama or vLLM. Pointing
 * base_url at any of them is enough to switch.
 */
class OpenAiPenyedia extends PenyediaHttp
{
    public function nama(): string
    {
        return 'openai';
    }

    public function kirimPrompt(string $prompt): string
    {
        $balasan = $this->minta(fn () => $this->klien()
            ->withToken($this->kunci())
            ->post($this->baseUrl().'/chat/completions', [
                'model' => $this->model(),
                'messages' => [
                    // The same instructions on every call, so the prefix stays
                    // stable and a provider can serve it from its prompt cache.
                    ['role' => 'system', 'content' => InstruksiNarasi::SISTEM],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]));

        $teks = $balasan->json('choices.0.message.content');

        if (! is_string($teks)) {
            throw NarasiTidakTersedia::balasanKosong($this->nama());
        }

        $this->catatPemakaian($balasan);

        return $this->teksWajib($teks);
    }
}
