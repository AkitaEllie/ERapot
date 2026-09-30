<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Verifies the real HTTP session round trip rather than the in-process component,
 * so a broken session cookie or a lost session write cannot pass unnoticed.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::factory()->bawaan('GURU')->create();
    Role::factory()->bawaan('TATAUSA')->create();
    Role::factory()->bawaan('KEPSEKOL')->create();

    $this->user = User::factory()->create([
        'Role_ID' => 'GURU',
        'Email' => 'guru@tkcktc.sch.id',
        'Password' => Hash::make('rahasia-kuat'),
    ]);

    expect(auth()->check())->toBeFalse();
});

it('menyimpan sesi pengguna di tabel sessions setelah login lewat HTTP', function (): void {
    Livewire::test('pages::login')
        ->set('Email', 'guru@tkcktc.sch.id')
        ->set('Password', 'rahasia-kuat')
        ->call('masuk')
        ->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue();

    // The guard wrote a login key into the session; make sure it is actually persisted.
    expect(session()->all())->toHaveKey('login_web_'.sha1('Illuminate\Auth\SessionGuard'));

    // And it must survive a fresh request, otherwise every login would bounce back to /login.
    $this->get(route('rapor.index'))->assertOk();
});

it('mengabaikan intended URL basi dari origin lain setelah login', function (): void {
    // The auth middleware stores the full current URL when it bounces a guest to the
    // login page. If that value is left over from another host or port, following it
    // would send the user somewhere unexpected, so login must ignore it.
    $this->withSession(['url.intended' => 'http://127.0.0.1:8126/login']);

    Livewire::test('pages::login')
        ->set('Email', 'guru@tkcktc.sch.id')
        ->set('Password', 'rahasia-kuat')
        ->call('masuk')
        ->assertRedirect(route('dashboard'));

    expect(session()->get('url.intended'))->toBeNull();
});

it('menolak email atau kata sandi yang salah', function (): void {
    Livewire::test('pages::login')
        ->set('Email', 'guru@tkcktc.sch.id')
        ->set('Password', 'salah')
        ->call('masuk')
        ->assertHasErrors('Email');

    expect(auth()->check())->toBeFalse();

    expect(session()->all())->not->toHaveKey('login_web_'.sha1('Illuminate\Auth\SessionGuard'));
});

it('menolak login dengan email yang tidak terdaftar', function (): void {
    Livewire::test('pages::login')
        ->set('Email', 'tidak@ada.sch.id')
        ->set('Password', 'rahasia-kuat')
        ->call('masuk')
        ->assertHasErrors('Email');

    expect(auth()->check())->toBeFalse();
});

it('menolak login tanpa data', function (): void {
    Livewire::test('pages::login')
        ->call('masuk')
        ->assertHasErrors(['Email', 'Password']);

    expect(auth()->check())->toBeFalse();
});

it('menjaga halaman dashboard tetap tertutup untuk tamu', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('rapor.index'))->assertRedirect(route('login'));
    $this->get(route('indikator'))->assertRedirect(route('login'));
    $this->get(route('review-rapor'))->assertRedirect(route('login'));
});

it('menampilkan dashboard guru setelah login berhasil', function (): void {
    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk()->assertSee('Rapor Siswa');
});
