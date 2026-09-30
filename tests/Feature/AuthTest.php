<?php

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->roleGuru = Role::factory()->bawaan('GURU')->create();
});

test('the root route sends guests to the login screen', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});

test('guests can see the login screen', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Website E-Rapor')
        ->assertSee('TK Cinta Kasih Tzu Chi')
        ->assertSee('Masuk');
});

test('guests cannot see the dashboard', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a teacher can sign in', function (): void {
    $user = User::factory()->create([
        'Role_ID' => $this->roleGuru->getKey(),
        'Email' => 'sari@tkcktc.sch.id',
        'Password' => 'rahasia-kuat',
    ]);

    Livewire::test('pages::login')
        ->set('Email', 'sari@tkcktc.sch.id')
        ->set('Password', 'rahasia-kuat')
        ->call('masuk')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('signing in fails when the email is unknown', function (): void {
    Livewire::test('pages::login')
        ->set('Email', 'tidak@ada.sch.id')
        ->set('Password', 'rahasia-kuat')
        ->call('masuk')
        ->assertHasErrors(['Email' => 'Email atau kata sandi salah']);

    $this->assertGuest();
});

test('signing in fails with the wrong password', function (): void {
    User::factory()->create([
        'Role_ID' => $this->roleGuru->getKey(),
        'Email' => 'sari@tkcktc.sch.id',
        'Password' => 'rahasia-kuat',
    ]);

    Livewire::test('pages::login')
        ->set('Email', 'sari@tkcktc.sch.id')
        ->set('Password', 'salah')
        ->call('masuk')
        ->assertHasErrors(['Email' => 'Email atau kata sandi salah']);

    $this->assertGuest();
});

test('a signed in teacher sees the dashboard with their classes', function (): void {
    $tahunAjaran = TahunAjaran::factory()->ganjil()->create(['Is_Active' => true]);
    $guru = User::factory()->create(['Role_ID' => $this->roleGuru->getKey()]);
    $kelas = Kelas::factory()->create([
        'TahunAjaran_ID' => $tahunAjaran->getKey(),
        'User_ID' => $guru->getKey(),
        'Nama_Kelas' => 'TK-A Respect',
    ]);
    Siswa::factory()->count(2)->create();

    $this->actingAs($guru)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kelas yang Diampu')
        ->assertSee('TK-A Respect')
        ->assertSee($guru->Nama);
});
