<?php

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    $this->users = collect([
        'admin' => User::factory()->create(['Role_ID' => 'ADMIN']),
        'guru' => User::factory()->create(['Role_ID' => 'GURU']),
        'tataUsaha' => User::factory()->create(['Role_ID' => 'TATAUSA']),
        'kepsekol' => User::factory()->create(['Role_ID' => 'KEPSEKOL']),
    ]);

    $periode = TahunAjaran::factory()->create(['Is_Active' => true]);
    $kelas = Kelas::factory()->create([
        'TahunAjaran_ID' => $periode->getKey(),
        'User_ID' => $this->users['guru']->getKey(),
    ]);
    $this->mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Mapel_ID' => $this->mapel->getKey()]);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => $kelas->getKey(),
    ]);
});

/**
 * Each protected screen, with the single role that owns it.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function layarTerlindungi(): array
{
    return [
        'dashboard tata usaha' => ['/dashboard/tata-usaha', 'tataUsaha'],
        'data siswa' => ['/siswa', 'tataUsaha'],
        'penempatan' => ['/penempatan', 'tataUsaha'],
        'dashboard progres' => ['/dashboard/progres', 'kepsekol'],
        'review rapor' => ['/review-rapor', 'kepsekol'],
        'indikator' => ['/indikator', 'guru'],
        'rapor siswa' => ['/rapor', 'guru'],
    ];
}

/** @return array<int, string> */
function peranLain(string $pemilik): array
{
    return array_values(array_diff(['guru', 'tataUsaha', 'kepsekol'], [$pemilik]));
}

it('menampilkan layar kepada pemiliknya', function (string $path, string $pemilik): void {
    $this->actingAs($this->users[$pemilik])
        ->get($path)
        ->assertOk();
})->with(layarTerlindungi());

it('menolak peran lain membuka layar tersebut', function (string $path, string $pemilik): void {
    foreach (peranLain($pemilik) as $peran) {
        $this->actingAs($this->users[$peran])
            ->get($path)
            ->assertForbidden("{$peran} seharusnya tidak bisa membuka {$path}");
    }
})->with(layarTerlindungi());

it('menyembunyikan layar dari tamu', function (string $path): void {
    $this->get($path)->assertRedirect(route('login'));
})->with(array_map(fn (array $l): array => [$l[0]], array_values(layarTerlindungi())));

it('mengizinkan administrator membuka seluruh layar', function (): void {
    foreach (array_values(layarTerlindungi()) as [$path]) {
        $this->actingAs($this->users['admin'])->get($path)->assertOk("admin gagal membuka {$path}");
    }
});

it('melindungi pratinjau rapor dan input rapor', function (): void {
    $this->actingAs($this->users['guru'])
        ->get(route('rapor.pratinjau', $this->rapor))
        ->assertOk();

    $this->actingAs($this->users['guru'])
        ->get(route('rapor.input', ['rapor' => $this->rapor, 'mapel' => $this->mapel]))
        ->assertOk();

    foreach (['tataUsaha', 'kepsekol'] as $peran) {
        $this->actingAs($this->users[$peran])
            ->get(route('rapor.pratinjau', $this->rapor))
            ->assertForbidden("{$peran} seharusnya tidak bisa membuka pratinjau");

        $this->actingAs($this->users[$peran])
            ->get(route('rapor.input', ['rapor' => $this->rapor, 'mapel' => $this->mapel]))
            ->assertForbidden("{$peran} seharusnya tidak bisa membuka input rapor");
    }
});

it('membuka pratinjau rapor melalui jalur yang benar, bukan lewat {mapel}', function (): void {
    // The {mapel} catch-all must not swallow the pratinjau segment.
    $this->actingAs($this->users['guru'])
        ->get(route('rapor.pratinjau', $this->rapor))
        ->assertOk();
});

it('memetakan capability per role sesuai class diagram', function (): void {
    expect($this->users['guru']->role->getHakAkses())
        ->toContain('rapor', 'indikator', 'ekspor')
        ->not->toContain('siswa', 'review');

    expect($this->users['tataUsaha']->role->getHakAkses())
        ->toContain('siswa', 'penempatan', 'kelas')
        ->not->toContain('rapor', 'review');

    expect($this->users['kepsekol']->role->getHakAkses())
        ->toContain('review', 'persetujuan')
        ->not->toContain('rapor', 'siswa');

    expect($this->users['admin']->role->getHakAkses())->toBe(['*']);
});

it('menyembunyikan tautan menu yang tidak diizinkan', function (): void {
    // The sidebar is role driven already, so a Guru never sees Tata Usaha entries.
    $this->actingAs($this->users['guru'])
        ->get('/rapor')
        ->assertOk()
        ->assertDontSee('Penempatan Siswa')
        ->assertSee('Indikator Penilaian');
});
