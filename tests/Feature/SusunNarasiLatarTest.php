<?php

use App\Enums\RaporStatus;
use App\Enums\StatusNarasi;
use App\Jobs\SusunNarasi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const HASIL_AI = 'Narasi hasil AI untuk anak ini.';

function balasanAi(): array
{
    return ['choices' => [['message' => ['content' => HASIL_AI]]]];
}

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    config([
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'kunci-uji',
        'ai.providers.openai.model' => 'model-uji',
        'ai.providers.openai.base_url' => 'https://ai.test/v1',
        'ai.providers.openai.timeout' => 180,
    ]);

    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $kelas = Kelas::factory()->create(['User_ID' => $this->wali->getKey()]);
    $this->rapor = Rapor::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Status' => RaporStatus::Draft]);
    $this->mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Mapel_ID' => $this->mapel->getKey(), 'User_ID' => $this->wali->getKey()]);
    $this->narasi = Narasi::factory()->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $this->mapel->getKey(),
        'Catatan_Guru' => 'Anak rajin.',
        'Draft_Narasi' => null,
    ]);

    $this->actingAs($this->wali);
    $this->halaman = fn () => Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel]);
});

describe('saat meminta narasi', function (): void {
    it('mengantrekan pekerjaan tanpa menunggu penyedia AI', function (): void {
        Queue::fake();
        Http::fake();

        ($this->halaman)()
            ->call('generateNarasi')
            ->assertSet('menungguGenerate', true)
            ->assertSee('AI sedang menyusun narasi')
            ->assertSee('Menyusun narasi...');

        $token = $this->narasi->fresh()->Generate_Token;

        expect($token)->not->toBeNull();
        Queue::assertPushed(SusunNarasi::class, fn (SusunNarasi $job): bool => $job->narasiId === $this->narasi->getKey() && $job->token === $token);
        Http::assertNothingSent();
    });

    it('tidak mengantrekan dua kali selama masih menunggu', function (): void {
        Queue::fake();

        ($this->halaman)()->call('generateNarasi')->call('generateNarasi');

        Queue::assertPushedTimes(SusunNarasi::class, 1);
    });

    it('tidak mengantrekan bila data belum lengkap', function (): void {
        Queue::fake();
        $this->narasi->update(['Catatan_Guru' => null]);

        ($this->halaman)()->set('catatan', '')->call('generateNarasi')->assertSet('menungguGenerate', false);

        Queue::assertNothingPushed();
    });

    it('tetap menunggu saat halaman dibuka kembali', function (): void {
        $this->narasi->mulaiGenerate();

        ($this->halaman)()->assertSet('menungguGenerate', true)->assertSee('AI sedang menyusun narasi');
    });
});

describe('batas waktu pekerjaan', function (): void {
    it('memberi penyedia waktu untuk gagal lebih dulu, di bawah retry_after', function (): void {
        $job = new SusunNarasi('N-1', 'token');

        expect($job->timeout)->toBeGreaterThan(180)
            ->and($job->timeout)->toBeLessThan((int) config('queue.connections.database.retry_after'))
            ->and($job->tries)->toBe(1);
    });
});

describe('saat pekerjaan berjalan', function (): void {
    it('menyimpan draf dan menampilkannya pada pemeriksaan berikutnya', function (): void {
        Queue::fake();
        Http::fake(['ai.test/*' => Http::response(balasanAi())]);

        $halaman = ($this->halaman)()->call('generateNarasi');
        (new SusunNarasi($this->narasi->getKey(), $this->narasi->fresh()->Generate_Token))->handle();

        $narasi = $this->narasi->fresh();
        expect($narasi->Draft_Narasi)->toBe(HASIL_AI)
            ->and($narasi->Status_Narasi)->toBe(StatusNarasi::Draft)
            ->and($narasi->Generate_Token)->toBeNull();

        $halaman->call('periksaGenerate')
            ->assertSet('menungguGenerate', false)
            ->assertSet('draftNarasi', HASIL_AI)
            ->assertSee('Narasi selesai disusun');
    });

    it('menyimpan hasil walau guru sudah meninggalkan halaman', function (): void {
        Http::fake(['ai.test/*' => Http::response(balasanAi())]);

        (new SusunNarasi($this->narasi->getKey(), $this->narasi->mulaiGenerate()))->handle();

        ($this->halaman)()->assertSet('draftNarasi', HASIL_AI)->assertSet('menungguGenerate', false);
    });

    it('menampilkan kegagalan penyedia dan mengizinkan mencoba lagi', function (): void {
        Queue::fake();
        Http::fake(['ai.test/*' => Http::response(['error' => ['message' => 'Rahasia teknis']], 500)]);

        $halaman = ($this->halaman)()->call('generateNarasi');
        (new SusunNarasi($this->narasi->getKey(), $this->narasi->fresh()->Generate_Token))->handle();

        $halaman->call('periksaGenerate')
            ->assertSet('menungguGenerate', false)
            ->assertSee('Coba lagi beberapa saat')
            ->assertDontSee('Rahasia teknis')
            ->call('generateNarasi');

        Queue::assertPushedTimes(SusunNarasi::class, 2);
    });

    it('melepaskan guru bila pekerjaan mati di luar dugaan', function (): void {
        $token = $this->narasi->mulaiGenerate();

        (new SusunNarasi($this->narasi->getKey(), $token))->failed(new RuntimeException('worker mati'));

        expect($this->narasi->fresh())
            ->Generate_Token->toBeNull()
            ->Generate_Galat->toContain('Coba lagi');
    });

    it('membuang hasil bila dibatalkan selagi penyedia bekerja', function (): void {
        $token = $this->narasi->mulaiGenerate();
        Http::fake(function (Request $request) {
            $this->narasi->fresh()->batalkanGenerate();

            return Http::response(balasanAi());
        });

        (new SusunNarasi($this->narasi->getKey(), $token))->handle();

        expect($this->narasi->fresh())
            ->Draft_Narasi->toBeNull()
            ->Generate_Galat->toBeNull();
    });

    it('membuang hasil permintaan lama setelah ada permintaan baru', function (): void {
        $lama = $this->narasi->mulaiGenerate();
        $baru = $this->narasi->mulaiGenerate();
        Http::fake(['ai.test/*' => Http::response(balasanAi())]);

        (new SusunNarasi($this->narasi->getKey(), $lama))->handle();

        expect($this->narasi->fresh())
            ->Draft_Narasi->toBeNull()
            ->Generate_Token->toBe($baru);
    });

    it('tidak mengubah rapor yang terkunci selagi penyedia bekerja', function (): void {
        $this->narasi->update(['Draft_Narasi' => 'Teks yang diajukan']);
        $token = $this->narasi->fresh()->mulaiGenerate();
        Http::fake(function (Request $request) {
            $this->rapor->forceFill(['Status' => RaporStatus::Menunggu])->save();

            return Http::response(balasanAi());
        });

        (new SusunNarasi($this->narasi->getKey(), $token))->handle();

        expect($this->narasi->fresh())
            ->Draft_Narasi->toBe('Teks yang diajukan')
            ->Generate_Token->toBeNull();
    });
});

describe('menjaga teks selama menunggu', function (): void {
    it('tidak menimpa hasil AI dengan teks lama di layar', function (): void {
        Queue::fake();
        Http::fake(['ai.test/*' => Http::response(balasanAi())]);
        $this->narasi->update(['Draft_Narasi' => 'Teks lama']);

        $halaman = ($this->halaman)()->call('generateNarasi');
        (new SusunNarasi($this->narasi->getKey(), $this->narasi->fresh()->Generate_Token))->handle();

        // The job finished, but this screen has not polled yet.
        $halaman->call('simpanDraft');

        expect($this->narasi->fresh()->Draft_Narasi)->toBe(HASIL_AI);
    });

    it('tidak menyimpan narasi manual selagi menunggu', function (): void {
        Queue::fake();
        $this->narasi->update(['Draft_Narasi' => 'Teks lama']);

        ($this->halaman)()
            ->call('generateNarasi')
            ->set('draftNarasi', 'Teks guru')
            ->call('simpanNarasi')
            ->assertSee('Narasi sedang disusun AI');

        expect($this->narasi->fresh()->Draft_Narasi)->toBe('Teks lama');
    });
});

describe('saat tidak ada jawaban', function (): void {
    it('membebaskan layar lewat tombol batal', function (): void {
        Queue::fake();

        ($this->halaman)()
            ->call('generateNarasi')
            ->call('batalkanGenerate')
            ->assertSet('menungguGenerate', false)
            ->assertDontSee('AI sedang menyusun narasi');

        expect($this->narasi->fresh()->Generate_Token)->toBeNull();
    });

    it('menyerah setelah batas waktu agar layar tidak berputar selamanya', function (): void {
        Queue::fake();

        $halaman = ($this->halaman)()->call('generateNarasi');

        $this->travel(Narasi::BATAS_GENERATE_MENIT + 1)->minutes();

        $halaman->call('periksaGenerate')
            ->assertSet('menungguGenerate', false)
            ->assertSee('hubungi administrator')
            ->call('generateNarasi');

        Queue::assertPushedTimes(SusunNarasi::class, 2);
    });
});
