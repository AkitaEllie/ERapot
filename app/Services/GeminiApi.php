<?php

namespace App\Services;

use App\Contracts\PenyediaNarasi;
use App\Exceptions\NarasiTidakTersedia;

/**
 * Narration generation service, «service» in the class diagram.
 *
 * The diagram ties this to Gemini by name and gives it an API_Key and a Model_AI.
 * Those remain, but they now come from config/ai.php and the vendor sits behind
 * the PenyediaNarasi contract, so the application is not coded to one endpoint.
 * The class keeps its diagram name so nothing calling it has to change.
 */
class GeminiApi
{
    public function __construct(
        protected PenyediaNarasi $penyedia,
    ) {
        //
    }

    /**
     * Send a prompt to the configured provider and return its reply.
     *
     * @throws NarasiTidakTersedia when no provider is configured or the request fails.
     */
    public function kirimPrompt(string $prompt): string
    {
        return $this->penyedia->kirimPrompt($prompt);
    }

    /**
     * The provider in use, for messages and diagnostics.
     */
    public function penyedia(): string
    {
        return $this->penyedia->nama();
    }
}
