<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;

/**
 * Narration generation service, «service» in the class diagram.
 */
class GeminiApi
{
    public function __construct(
        protected PendingRequest $client,
    ) {
        //
    }

    /**
     * Send a prompt to the model and return its reply.
     *
     * @todo Implement per the class diagram.
     */
    public function kirimPrompt(string $prompt): string
    {
        return '';
    }
}
