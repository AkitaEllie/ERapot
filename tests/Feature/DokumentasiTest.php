<?php

use App\Models\Dokumentasi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');

    Role::factory()->bawaan('GURU')->create();
    $this->guru = User::factory()->create(['Role_ID' => 'GURU']);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => Kelas::factory()->create(['User_ID' => $this->guru->getKey()])->getKey(),
    ]);
    $this->mapel = MataPelajaran::factory()->create();
    $this->narasi = Narasi::factory()->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $this->mapel->getKey(),
    ]);

    $this->actingAs($this->guru);
});

it('menyimpan foto ke disk publik dan mencatat nama aslinya', function (): void {
    $dokumentasi = Dokumentasi::factory()->create([
        'Narasi_ID' => $this->narasi->getKey(),
        'Foto' => '',
    ]);

    $dokumentasi->unggahFoto(UploadedFile::fake()->image('foto1.jpg'));

    expect($dokumentasi->fresh()->Foto)->not->toBe('')
        ->and($dokumentasi->fresh()->Keterangan)->toBe('foto1.jpg');

    Storage::disk('public')->assertExists($dokumentasi->fresh()->Foto);
});

it('menghapus berkas dan barisnya saat foto dihapus', function (): void {
    $dokumentasi = Dokumentasi::factory()->create(['Narasi_ID' => $this->narasi->getKey(), 'Foto' => '']);
    $dokumentasi->unggahFoto(UploadedFile::fake()->image('foto1.jpg'));

    $path = $dokumentasi->fresh()->Foto;
    Storage::disk('public')->assertExists($path);

    $dokumentasi->hapusFoto();

    expect(Dokumentasi::query()->find($dokumentasi->getKey()))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('membatasi jumlah foto per narasi', function (): void {
    foreach (range(1, Dokumentasi::MAKS_FOTO) as $i) {
        $dok = Dokumentasi::factory()->create(['Narasi_ID' => $this->narasi->getKey(), 'Foto' => '']);
        $dok->unggahFoto(UploadedFile::fake()->image("foto{$i}.jpg"));
    }

    expect($this->narasi->dokumentasi()->count())->toBe(Dokumentasi::MAKS_FOTO);

    $kelebihan = Dokumentasi::factory()->create(['Narasi_ID' => $this->narasi->getKey(), 'Foto' => '']);

    expect(fn () => $kelebihan->unggahFoto(UploadedFile::fake()->image('kelebihan.jpg')))
        ->toThrow(HttpException::class, 'Maksimal '.Dokumentasi::MAKS_FOTO.' foto per narasi.');
});

it('mengunggah foto lewat layar input rapor', function (): void {
    Livewire::test('pages::rapor-input', [
        'rapor' => $this->rapor,
        'mapel' => $this->mapel,
    ])
        ->set('fotoBaru', [UploadedFile::fake()->image('kegiatan.jpg')])
        ->assertHasNoErrors();

    $tersimpan = $this->narasi->dokumentasi()->first();

    expect($tersimpan)->not->toBeNull()
        ->and($tersimpan->Keterangan)->toBe('kegiatan.jpg');

    Storage::disk('public')->assertExists($tersimpan->Foto);
});

it('menampilkan pesan dan tidak menyimpan berkas keempat ketika kuota habis', function (): void {
    foreach (range(1, Dokumentasi::MAKS_FOTO) as $i) {
        $dok = Dokumentasi::factory()->create(['Narasi_ID' => $this->narasi->getKey(), 'Foto' => '']);
        $dok->unggahFoto(UploadedFile::fake()->image("foto{$i}.jpg"));
    }

    Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel])
        ->set('fotoBaru', [UploadedFile::fake()->image('kelebihan.jpg')])
        ->assertSet('fotoBaru', []);

    expect($this->narasi->dokumentasi()->count())->toBe(Dokumentasi::MAKS_FOTO);
});

it('menghapus foto dari layar input rapor', function (): void {
    $dokumentasi = Dokumentasi::factory()->create(['Narasi_ID' => $this->narasi->getKey(), 'Foto' => '']);
    $dokumentasi->unggahFoto(UploadedFile::fake()->image('hapus.jpg'));

    Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel])
        ->call('hapusFoto', $dokumentasi->getKey());

    expect($this->narasi->dokumentasi()->count())->toBe(0);
});
