<?php

namespace App\Contracts;

use App\Exceptions\NarasiTidakTersedia;

/**
 * A provider that can generate narration text from a prompt.
 *
 * The class diagram names one «service» GeminiAPI, tying the
 * application to a single vendor. The behaviour is the same whatever answers the
 * prompt, so it lives behind this contract and the vendor sits in a driver. The
 * diagram's GeminiAPI class remains as the front door the application calls.
 */
interface PenyediaNarasi
{
    /**
     * Send a prompt and return the generated text.
     *
     * @throws NarasiTidakTersedia when the provider is unconfigured or fails.
     */
    public function kirimPrompt(string $prompt): string;

    /**
     * The provider's short name, for messages and diagnostics.
     */
    public function nama(): string;
}
