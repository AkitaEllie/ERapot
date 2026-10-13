<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }
});

/**
 * Screens the sidebar names but the wireframe never designed.
 *
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function layarPlaceholder(): array
{
    return [
        'ekspor rapor' => ['/ekspor-rapor', 'ekspor', 'GURU'],
        'tahun ajaran' => ['/tahun-ajaran', 'tahun-ajaran', 'TATAUSA'],
        'data pengguna' => ['/pengguna', 'pengguna', 'TATAUSA'],
        'data kelas' => ['/kelas', 'kelas', 'TATAUSA'],
        'mata pelajaran' => ['/mapel', 'mapel', 'TATAUSA'],
        'penugasan mengajar' => ['/penugasan-mengajar', 'penugasan', 'TATAUSA'],
    ];
}

it('menampilkan halaman placeholder kepada pemiliknya', function (string $path, string $hak, string $peran): void {
    $this->actingAs(User::factory()->create(['Role_ID' => $peran]))
        ->get($path)
        ->assertOk()
        ->assertSee('Layar ini belum dibuat');
})->with(layarPlaceholder());

it('menandai halaman placeholder sebagai belum dibuat, bukan layar kosong', function (string $path, string $hak, string $peran): void {
    $response = $this->actingAs(User::factory()->create(['Role_ID' => $peran]))->get($path);

    $response->assertOk()
        ->assertSee('belum dirancang dan belum diimplementasikan')
        ->assertSee('sengaja tidak memiliki fungsi apa pun');
})->with(layarPlaceholder());

it('menolak peran lain membuka halaman placeholder', function (string $path, string $hak, string $peran): void {
    foreach (array_diff(['GURU', 'TATAUSA', 'KEPSEKOL'], [$peran]) as $lain) {
        $this->actingAs(User::factory()->create(['Role_ID' => $lain]))
            ->get($path)
            ->assertForbidden("{$lain} seharusnya tidak bisa membuka {$path}");
    }
})->with(layarPlaceholder());

it('menyembunyikan halaman placeholder dari tamu', function (string $path): void {
    $this->get($path)->assertRedirect(route('login'));
})->with(array_map(fn (array $l): array => [$l[0]], array_values(layarPlaceholder())));

it('tautan sidebar mengarah ke halaman placeholder, bukan ke #', function (): void {
    $response = $this->actingAs(User::factory()->create(['Role_ID' => 'TATAUSA']))
        ->get('/dashboard/tata-usaha');

    $response->assertOk()
        ->assertSee(route('tahun-ajaran'), escape: false)
        ->assertSee(route('pengguna'), escape: false)
        ->assertSee(route('kelas'), escape: false)
        ->assertSee(route('mapel'), escape: false)
        ->assertSee(route('penugasan-mengajar'), escape: false);

    // No menu entry should fall back to a dead anchor any more.
    expect(substr_count($response->getContent(), 'href="#"'))->toBe(0);
});

it('mengarahkan menu Guru yang belum dibuat ke placeholder', function (): void {
    $this->actingAs(User::factory()->create(['Role_ID' => 'GURU']))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(route('ekspor-rapor'), escape: false);
});
