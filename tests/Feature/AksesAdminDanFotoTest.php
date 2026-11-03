<?php

use App\Enums\RaporStatus;
use App\Models\Dokumentasi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    Storage::fake(Rapor::DISK_PDF);
    Storage::fake('public');

    $periode = TahunAjaran::factory()->create(['Is_Active' => true]);

    $this->admin = User::factory()->create(['Role_ID' => 'ADMIN']);
    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $this->guruMapel = User::factory()->create(['Role_ID' => 'GURU']);
    $this->guruLain = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $this->kepsek = User::factory()->create(['Role_ID' => 'KEPSEKOL']);
    $this->kelas = Kelas::factory()->create(['User_ID' => $this->wali->getKey(), 'TahunAjaran_ID' => $periode->getKey()]);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::Disetujui,
    ]);

    // MP-1 sorts first and belongs to the homeroom teacher, as in the 403 report.
    $this->mapelWali = MataPelajaran::factory()->create(['Mapel_ID' => 'MP-0000001']);
    $this->mapelGuru = MataPelajaran::factory()->create(['Mapel_ID' => 'MP-0000002']);
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapelWali->getKey(), 'User_ID' => $this->wali->getKey()]);
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapelGuru->getKey(), 'User_ID' => $this->guruMapel->getKey()]);
});

describe('administrator', function (): void {
    it('mendapat menu ke seluruh layar', function (string $tujuan): void {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route($tujuan), false);
    })->with([
        'dashboard.tata-usaha', 'dashboard.kepsekol', 'pengguna', 'siswa.index', 'kelas', 'mapel',
        'penempatan', 'penugasan-mengajar', 'tahun-ajaran', 'indikator', 'rapor.index',
        'ekspor-rapor', 'review-rapor', 'persetujuan-rapor',
    ]);

    it('melihat seluruh kelas dan seluruh mata pelajarannya', function (): void {
        $this->actingAs($this->admin);

        expect($this->admin->kelasDiajar()->pluck('Kelas_ID')->all())->toContain($this->kelas->getKey());

        Livewire::test('pages::rapor-list')
            ->set('filterKelas', $this->kelas->getKey())
            ->assertSee($this->mapelWali->Nama_Mapel)
            ->assertSee($this->mapelGuru->Nama_Mapel);
    });

    it('melihat seluruh kelas pada daftar ekspor', function (): void {
        $this->actingAs($this->admin);

        Livewire::test('pages::ekspor-rapor')->assertSee($this->kelas->Nama_Kelas);
    });
});

describe('tombol Buka Rapor', function (): void {
    beforeEach(function (): void {
        $this->rapor->forceFill(['Status' => RaporStatus::PerluRevisi, 'Catatan_Revisi' => 'Perbaiki.'])->save();
    });

    it('membawa guru mata pelajaran ke mata pelajarannya sendiri', function (): void {
        $tujuan = route('rapor.input', ['rapor' => $this->rapor->getKey(), 'mapel' => $this->mapelGuru->getKey()]);

        $this->actingAs($this->guruMapel);
        Livewire::test('pages::dashboard')->assertSeeHtml($tujuan);
        $this->get($tujuan)->assertOk();
    });

    it('membawa wali kelas ke mata pelajaran pertama', function (): void {
        $this->actingAs($this->wali);

        Livewire::test('pages::dashboard')
            ->assertSeeHtml(route('rapor.input', ['rapor' => $this->rapor->getKey(), 'mapel' => $this->mapelWali->getKey()]));
    });
});

describe('pratinjau dan ekspor kelas lain', function (): void {
    it('menolak guru yang tidak mengajar di kelas itu', function (): void {
        $this->actingAs($this->guruLain)->get(route('rapor.pratinjau', $this->rapor))->assertForbidden();
    });

    it('mengizinkan orang yang berhak membaca', function (string $peran): void {
        $this->actingAs($this->{$peran})->get(route('rapor.pratinjau', $this->rapor))->assertOk();
    })->with(['wali', 'guruMapel', 'kepsek', 'admin']);

    it('tidak membiarkan kepala sekolah menulis berkas lewat aksi ekspor', function (): void {
        $this->actingAs($this->kepsek);

        Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
            ->call('exportPdf')
            ->assertForbidden();

        Storage::disk(Rapor::DISK_PDF)->assertMissing($this->rapor->berkasPdf());
    });
});

describe('galat layanan AI', function (): void {
    beforeEach(function (): void {
        $this->narasi = Narasi::factory()->create([
            'Rapor_ID' => $this->rapor->getKey(),
            'Mapel_ID' => $this->mapelWali->getKey(),
            'Catatan_Guru' => 'Anak rajin.',
        ]);
        $this->rapor->forceFill(['Status' => RaporStatus::Draft])->save();
    });

    it('menampilkan pesan yang jelas dan mencatat sebab teknisnya tanpa kunci', function (): void {
        config([
            'ai.default' => 'gemini',
            'ai.providers.gemini.key' => 'kunci-rahasia',
            'ai.providers.gemini.model' => 'model-uji',
        ]);
        Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://ai.test/?key=kunci-rahasia'));
        Log::spy();

        $this->actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelWali])
            ->call('generateNarasi')
            ->assertSee('Coba lagi beberapa saat')
            ->assertDontSee('cURL')
            ->assertDontSee('kunci-rahasia');

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $pesan, array $konteks): bool => str_contains($konteks['sebab'], 'cURL error 28')
                && ! str_contains($konteks['sebab'], 'kunci-rahasia'),
        );
    });

    it('meminta menghubungi administrator bila layanan belum disiapkan', function (): void {
        config(['ai.default' => 'gemini', 'ai.providers.gemini.key' => '']);

        $this->actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelWali])
            ->call('generateNarasi')
            ->assertSee('Hubungi administrator')
            ->assertDontSee('.env');
    });
});

describe('akun yang dinonaktifkan', function (): void {
    it('mengakhiri sesi yang masih terbuka', function (): void {
        $this->actingAs($this->wali);
        $this->wali->forceFill(['Is_Active' => false])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    });

    it('menjelaskan alasannya pada halaman masuk', function (): void {
        $this->actingAs($this->wali);
        $this->wali->forceFill(['Is_Active' => false])->save();

        $this->followingRedirects()
            ->get(route('dashboard'))
            ->assertSee('Akun Anda telah dinonaktifkan');
    });

    it('membiarkan akun aktif', function (): void {
        $this->actingAs($this->wali)->get(route('dashboard'))->assertOk();
    });
});

describe('foto dokumentasi', function (): void {
    beforeEach(function (): void {
        Storage::fake(Dokumentasi::DISK);

        $narasi = Narasi::factory()->create(['Rapor_ID' => $this->rapor->getKey(), 'Mapel_ID' => $this->mapelWali->getKey()]);
        Storage::disk(Dokumentasi::DISK)->put('dokumentasi/anak.jpg', 'isi-foto');
        $this->foto = Dokumentasi::factory()->create(['Narasi_ID' => $narasi->getKey(), 'Foto' => 'dokumentasi/anak.jpg']);
    });

    it('tidak dapat dibuka tanpa login', function (): void {
        $this->get($this->foto->url())->assertRedirect(route('login'));
        $this->get('/storage/dokumentasi/anak.jpg')->assertForbidden();
    });

    it('dapat dibuka oleh yang berhak membaca rapor', function (string $peran): void {
        $this->actingAs($this->{$peran})->get($this->foto->url())->assertOk();
    })->with(['wali', 'guruMapel', 'kepsek', 'admin']);

    it('tidak boleh disimpan di cache peramban', function (): void {
        $tanggapan = $this->actingAs($this->wali)->get($this->foto->url());

        expect($tanggapan->headers->get('Cache-Control'))->toContain('no-store');
    });

    it('ditolak bagi guru kelas lain dan tata usaha', function (): void {
        $this->actingAs($this->guruLain)->get($this->foto->url())->assertForbidden();

        $tataUsaha = User::factory()->create(['Role_ID' => 'TATAUSA']);
        $this->actingAs($tataUsaha)->get($this->foto->url())->assertForbidden();
    });

    it('menjawab 404 bila berkasnya hilang', function (): void {
        Storage::disk(Dokumentasi::DISK)->delete($this->foto->Foto);

        $this->actingAs($this->wali)->get($this->foto->url())->assertNotFound();
    });

    it('dipindahkan dari penyimpanan publik oleh perintah pengamanan', function (): void {
        $publik = Storage::disk('public');
        $publik->put('dokumentasi/anak.jpg', 'foto-publik');
        $publik->put('dokumentasi/tanpa-pemilik.jpg', 'sisa');
        $publik->put('lainnya/logo.png', 'logo');

        $this->artisan('rapor:amankan-berkas')
            ->expectsOutputToContain('1 berkas dipindahkan ke penyimpanan privat, 1 berkas tanpa pemilik dihapus.')
            ->assertSuccessful();

        expect(Storage::disk(Dokumentasi::DISK)->get('dokumentasi/anak.jpg'))->toBe('foto-publik');
        Storage::disk(Dokumentasi::DISK)->assertMissing('dokumentasi/tanpa-pemilik.jpg');
        $publik->assertMissing('dokumentasi/anak.jpg');
        $publik->assertMissing('dokumentasi/tanpa-pemilik.jpg');
        $publik->assertExists('lainnya/logo.png');
    });
});
