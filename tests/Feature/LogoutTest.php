<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::factory()->bawaan('GURU')->create();
    $this->user = User::factory()->create([
        'Role_ID' => 'GURU',
        'Email' => 'guru@tkcktc.sch.id',
        'Password' => Hash::make('rahasia-kuat'),
    ]);
});

it('mengakhiri sesi saat user logout', function (): void {
    $this->actingAs($this->user);
    expect(Auth::check())->toBeTrue();

    $this->user->logout();

    expect(Auth::check())->toBeFalse();
});

it('membuat id sesi baru sehingga sesi lama tidak bisa dipakai lagi', function (): void {
    $this->actingAs($this->user);
    $sebelum = session()->getId();

    $this->user->logout();

    expect(session()->getId())->not->toBe($sebelum);
});

it('memutar token csrf saat logout', function (): void {
    $this->actingAs($this->user);
    $sebelum = session()->token();

    $this->user->logout();

    expect(session()->token())->not->toBe($sebelum);
});

it('mengosongkan atribut sesi milik user', function (): void {
    $this->actingAs($this->user);
    session()->put('flash_extra', 'sebelum');

    $this->user->logout();

    expect(session()->get('flash_extra'))->toBeNull();
});

it('mengalihkan ke login lewat route logout', function (): void {
    $this->actingAs($this->user);

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('menolak route logout untuk tamu', function (): void {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

it('menyisakan user lain tetap login', function (): void {
    $other = User::factory()->create(['Role_ID' => 'GURU']);

    $this->actingAs($other);
    $other->logout();

    $this->actingAs($this->user);
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($this->user->getKey());
});

it('masuk dengan email dan kata sandi yang benar', function (): void {
    expect($this->user->login('guru@tkcktc.sch.id', 'rahasia-kuat'))->toBeTrue()
        ->and(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($this->user->getKey());
});

it('menolak kata sandi yang salah tanpa membuka sesi', function (): void {
    expect($this->user->login('guru@tkcktc.sch.id', 'salah'))->toBeFalse()
        ->and(Auth::check())->toBeFalse();
});

it('menolak email yang tidak cocok dengan user tersebut', function (): void {
    expect($this->user->login('orang.lain@tkcktc.sch.id', 'rahasia-kuat'))->toBeFalse()
        ->and(Auth::check())->toBeFalse();
});
