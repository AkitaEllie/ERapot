<?php

use App\Services\Narasi\GeminiPenyedia;
use App\Services\Narasi\OpenAiPenyedia;

return [

    /*
    |--------------------------------------------------------------------------
    | Narration Provider
    |--------------------------------------------------------------------------
    |
    | Which provider generates report narratives. The key here selects one of
    | the entries below. The application talks to it through the PenyediaNarasi
    | contract, so switching providers is a change to this value alone.
    |
    | Adding a provider means one driver class implementing the contract plus an
    | entry below.
    |
    */

    'default' => env('AI_PROVIDER', 'gemini'),

    'providers' => [

        /*
         | Google Gemini. The key is sent as a query parameter, as the API expects.
         | Example model: gemini-2.0-flash
         */
        'gemini' => [
            'driver' => GeminiPenyedia::class,
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        ],

        /*
         | Anything speaking the OpenAI chat-completions shape: OpenAI itself,
         | Groq, Together, or a local Ollama or vLLM. Point base_url at the host
         | and set the model it serves.
         */
        'openai' => [
            'driver' => OpenAiPenyedia::class,
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        ],

    ],

];
