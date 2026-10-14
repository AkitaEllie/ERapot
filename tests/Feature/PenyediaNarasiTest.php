<?php

use App\Contracts\PenyediaNarasi;
use App\Exceptions\NarasiTidakTersedia;
use App\Services\GeminiApi;
use App\Services\Narasi\GeminiPenyedia;
use App\Services\Narasi\OpenAiPenyedia;
use Illuminate\Support\Facades\Http;

/**
 * Point the config at a provider without touching the real environment.
 *
 * @param  array<string, mixed>  $ubah
 */
function pakaiPenyedia(string $nama, array $ubah = []): void
{
    config(['ai.default' => $nama]);

    config(["ai.providers.{$nama}" => array_merge(
        config("ai.providers.{$nama}"),
        ['key' => 'kunci-uji', 'model' => 'model-uji'],
        $ubah,
    )]);
}

it('menyelesaikan penyedia dari konfigurasi', function (): void {
    config(['ai.default' => 'gemini']);
    expect(app(PenyediaNarasi::class))->toBeInstanceOf(GeminiPenyedia::class);

    config(['ai.default' => 'openai']);
    expect(app(PenyediaNarasi::class))->toBeInstanceOf(OpenAiPenyedia::class);
});

it('menolak penyedia yang tidak dikenal', function (): void {
    config(['ai.default' => 'penyedia-yang-tidak-ada']);

    expect(fn () => app(PenyediaNarasi::class))->toThrow(NarasiTidakTersedia::class);
});

it('mengirim prompt ke Gemini dan mengembalikan teks', function (): void {
    pakaiPenyedia('gemini', ['base_url' => 'https://contoh.test']);

    Http::fake([
        'contoh.test/*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => '  Andini berkembang baik.  ']]]],
            ],
        ]),
    ]);

    $hasil = app(GeminiApi::class)->kirimPrompt('Ceritakan Andini');

    expect($hasil)->toBe('Andini berkembang baik.');

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), '/v1beta/models/model-uji:generateContent')
            && str_contains($request->url(), 'key=kunci-uji')
            && $request->data() === ['contents' => [['parts' => [['text' => 'Ceritakan Andini']]]]];
    });
});

it('mengirim prompt ke penyedia bergaya OpenAI dan mengembalikan teks', function (): void {
    pakaiPenyedia('openai', ['base_url' => 'https://api.contoh.test/v1']);

    Http::fake([
        'api.contoh.test/*' => Http::response([
            'choices' => [['message' => ['content' => 'Andini berkembang baik.']]],
        ]),
    ]);

    $hasil = app(GeminiApi::class)->kirimPrompt('Ceritakan Andini');

    expect($hasil)->toBe('Andini berkembang baik.');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.contoh.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer kunci-uji')
            && $request->data() === [
                'model' => 'model-uji',
                'messages' => [['role' => 'user', 'content' => 'Ceritakan Andini']],
            ];
    });
});

it('mendukung host lokal yang bicara gaya OpenAI', function (): void {
    // Ollama and vLLM are reached by changing the base URL alone.
    pakaiPenyedia('openai', ['base_url' => 'http://localhost:11434/v1', 'key' => 'bebas']);

    Http::fake([
        'localhost:11434/*' => Http::response([
            'choices' => [['message' => ['content' => 'Baik sekali.']]],
        ]),
    ]);

    expect(app(GeminiApi::class)->kirimPrompt('halo'))->toBe('Baik sekali.');

    Http::assertSent(fn ($request): bool => $request->url() === 'http://localhost:11434/v1/chat/completions');
});

it('melempar galat saat kunci API belum diisi', function (): void {
    pakaiPenyedia('gemini', ['key' => '']);

    Http::fake();

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class);

    Http::assertNothingSent();
});

it('melempar galat saat model belum diisi', function (): void {
    pakaiPenyedia('gemini', ['model' => '']);

    Http::fake();

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class);

    Http::assertNothingSent();
});

it('melempar galat beserta pesan penyedia saat permintaan ditolak', function (): void {
    pakaiPenyedia('openai', ['base_url' => 'https://api.contoh.test/v1']);

    Http::fake([
        'api.contoh.test/*' => Http::response(
            ['error' => ['message' => 'Kunci API tidak berlaku.']],
            401,
        ),
    ]);

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class, 'Kunci API tidak berlaku.');
});

it('melempar galat saat penyedia tidak mengembalikan teks', function (): void {
    pakaiPenyedia('gemini', ['base_url' => 'https://contoh.test']);

    Http::fake([
        'contoh.test/*' => Http::response(['candidates' => []]),
    ]);

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class);
});

it('melempar galat saat penyedia mengembalikan teks kosong', function (): void {
    pakaiPenyedia('gemini', ['base_url' => 'https://contoh.test']);

    Http::fake([
        'contoh.test/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '   ']]]]],
        ]),
    ]);

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class);
});

it('melempar galat saat koneksi gagal', function (): void {
    pakaiPenyedia('gemini', ['base_url' => 'https://contoh.test']);

    Http::fake([
        'contoh.test/*' => Http::failedConnection('Connection refused'),
    ]);

    expect(fn () => app(GeminiApi::class)->kirimPrompt('halo'))
        ->toThrow(NarasiTidakTersedia::class);
});

it('menyebut nama penyedia pada pesan galat', function (): void {
    pakaiPenyedia('gemini', ['key' => '']);

    try {
        app(GeminiApi::class)->kirimPrompt('halo');
    } catch (NarasiTidakTersedia $e) {
        expect($e->getMessage())->toContain('gemini');
    }
});
