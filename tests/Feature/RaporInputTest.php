<?php

use App\Enums\Nilai;
use App\Enums\RaporStatus;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('menampilkan daftar indikator mata pelajaran untuk guru', function (): void {
    $guru = User::factory()->create();
    $kelas = Kelas::factory()->create(['User_ID' => $guru->getKey()]);
    $siswa = Siswa::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Mapel_ID' => $mapel->getKey()]);
    $rapor = Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'Deskripsi' => 'Mempraktikkan doa sebelum dan sesudah kegiatan',
    ]);

    $this->actingAs($guru);

    Livewire::test('pages::rapor-input', ['rapor' => $rapor, 'mapel' => $mapel])
        ->assertOk()
        ->assertSee($mapel->Nama_Mapel)
        ->assertSee('Mempraktikkan doa sebelum dan sesudah kegiatan')
        ->assertSee('Generate Narasi');
});

it('menyimpan nilai indikator sebagai draf', function (): void {
    $guru = User::factory()->create();
    $kelas = Kelas::factory()->create(['User_ID' => $guru->getKey()]);
    $siswa = Siswa::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Mapel_ID' => $mapel->getKey()]);
    $rapor = Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $indikator = IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);

    $this->actingAs($guru);

    Livewire::test('pages::rapor-input', ['rapor' => $rapor, 'mapel' => $mapel])
        ->set("nilai.{$indikator->getKey()}", Nilai::BerkembangSangatBaik->value)
        ->set("untukNarasi.{$indikator->getKey()}", true)
        ->set('catatan', 'Anak mulai berani speak up.')
        ->call('simpanDraft')
        ->assertHasNoErrors();

    $narasi = $rapor->narasi()->where('Mapel_ID', $mapel->getKey())->first();

    expect($narasi)->not->toBeNull()
        ->and($narasi->Catatan_Guru)->toBe('Anak mulai berani speak up.');

    $terisi = $narasi->nilaiSiswa()->where('Indikator_ID', $indikator->getKey())->first();

    expect($terisi)->not->toBeNull()
        ->and($terisi->Nilai)->toBe(Nilai::BerkembangSangatBaik)
        ->and($terisi->Dipilih_Untuk_Narasi)->toBeTrue();
});

it('menolak mengajukan rapor yang indikatornya belum dinilai semua', function (): void {
    $guru = User::factory()->create();
    $kelas = Kelas::factory()->create(['User_ID' => $guru->getKey()]);
    $siswa = Siswa::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Mapel_ID' => $mapel->getKey()]);
    $rapor = Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
        'Status' => RaporStatus::Draft,
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);

    $this->actingAs($guru);

    Livewire::test('pages::rapor-input', ['rapor' => $rapor, 'mapel' => $mapel])
        ->call('ajukan')
        ->assertHasErrors('nilai');

    expect($rapor->fresh()->Status)->toBe(RaporStatus::Draft);
});
