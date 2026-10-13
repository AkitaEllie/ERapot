<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::factory()->bawaan('GURU')->create();
    $this->user = User::factory()->create([
        'Role_ID' => 'GURU',
        'Email' => 'guru@tkcktc.sch.id',
        'Password' => 'rahasia-kuat',
    ]);
});

it('menyimpan kata sandi baru dalam bentuk hash', function (): void {
    $this->user->ubahPassword('kata-sandi-baru');

    $tersimpan = $this->user->fresh()->Password;

    expect($tersimpan)->not->toBe('kata-sandi-baru')
        ->and(Hash::check('kata-sandi-baru', $tersimpan))->toBeTrue()
        ->and(Hash::check('rahasia-kuat', $tersimpan))->toBeFalse();
});

it('mengizinkan login dengan kata sandi baru lewat method login', function (): void {
    $this->user->ubahPassword('kata-sandi-baru');

    expect($this->user->fresh()->login('guru@tkcktc.sch.id', 'kata-sandi-baru'))->toBeTrue();
});

it('tidak menimpa kata sandi pengguna lain', function (): void {
    $lain = User::factory()->create(['Role_ID' => 'GURU', 'Password' => 'milik-orang-lain']);

    $this->user->ubahPassword('kata-sandi-baru');

    expect(Hash::check('milik-orang-lain', $lain->fresh()->Password))->toBeTrue();
});

it('memperbarui kata sandi lewat layar profil', function (): void {
    $this->actingAs($this->user);

    Livewire::test('pages::profil')
        ->set('Password_sekarang', 'rahasia-kuat')
        ->set('Password', 'kata-sandi-baru')
        ->set('Password_confirmation', 'kata-sandi-baru')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSee('Kata sandi berhasil diperbarui');

    expect(Hash::check('kata-sandi-baru', $this->user->fresh()->Password))->toBeTrue();
});

it('menolak kata sandi saat ini yang salah', function (): void {
    $this->actingAs($this->user);

    Livewire::test('pages::profil')
        ->set('Password_sekarang', 'bukan-sandi-saya')
        ->set('Password', 'kata-sandi-baru')
        ->set('Password_confirmation', 'kata-sandi-baru')
        ->call('simpan')
        ->assertHasErrors('Password_sekarang');

    expect(Hash::check('rahasia-kuat', $this->user->fresh()->Password))->toBeTrue();
});

it('menolak konfirmasi kata sandi yang tidak cocok', function (): void {
    $this->actingAs($this->user);

    Livewire::test('pages::profil')
        ->set('Password_sekarang', 'rahasia-kuat')
        ->set('Password', 'kata-sandi-baru')
        ->set('Password_confirmation', 'beda-sama-sekali')
        ->call('simpan')
        ->assertHasErrors('Password');

    expect(Hash::check('rahasia-kuat', $this->user->fresh()->Password))->toBeTrue();
});

it('menolak kata sandi baru yang terlalu pendek', function (): void {
    $this->actingAs($this->user);

    Livewire::test('pages::profil')
        ->set('Password_sekarang', 'rahasia-kuat')
        ->set('Password', 'pendek')
        ->set('Password_confirmation', 'pendek')
        ->call('simpan')
        ->assertHasErrors('Password');
});

it('menolak kata sandi baru yang sama dengan yang lama', function (): void {
    $this->actingAs($this->user);

    Livewire::test('pages::profil')
        ->set('Password_sekarang', 'rahasia-kuat')
        ->set('Password', 'rahasia-kuat')
        ->set('Password_confirmation', 'rahasia-kuat')
        ->call('simpan')
        ->assertHasErrors('Password');
});

it('menutup layar profil untuk tamu', function (): void {
    $this->get(route('profil'))->assertRedirect(route('login'));
});

it('membuka layar profil untuk setiap peran', function (string $kode): void {
    $role = Role::query()->find($kode) ?? Role::factory()->bawaan($kode)->create();

    $this->actingAs(User::factory()->create(['Role_ID' => $role->getKey()]))
        ->get(route('profil'))
        ->assertOk()
        ->assertSee('Ubah Kata Sandi');
})->with(['ADMIN', 'GURU', 'TATAUSA', 'KEPSEKOL']);
