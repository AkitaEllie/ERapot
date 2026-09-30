<?php

use App\Enums\RaporStatus;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->guru = User::factory()->create();
    $this->kelas = Kelas::factory()->create(['User_ID' => $this->guru->getKey()]);
    $this->mapel = MataPelajaran::factory()->create();
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapel->getKey()]);

    $this->actingAs($this->guru);
});

it('menampilkan siswa di kelas yang diampu beserta status rapor', function (): void {
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);
    IndikatorCapaian::factory()->count(12)->create(['Program_ID' => $program->getKey()]);

    $lengkap = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Andini Pratiwi', 'NISN' => '0154872301'])->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::Menunggu,
    ]);
    Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Devan Kurniawan'])->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::BelumDiisi,
    ]);

    // mark all indicators as scored for the first student
    $narasi = Narasi::factory()->create(['Rapor_ID' => $lengkap->getKey(), 'Mapel_ID' => $this->mapel->getKey()]);
    $narasi->nilaiSiswa()->createMany(
        IndikatorCapaian::where('Program_ID', $program->getKey())->pluck('Indikator_ID')
            ->map(fn ($id) => ['Indikator_ID' => $id, 'Nilai' => 'BSH'])->all()
    );

    Livewire::test('pages::rapor-list')
        ->assertOk()
        ->assertSee('Andini Pratiwi')
        ->assertSee('0154872301')
        ->assertSee('12 / 12')
        ->assertSee('Belum Diisi')
        ->assertSee('Isi Rapor');
});

it('memfilter siswa berdasarkan pencarian nama', function (): void {
    Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Andini Pratiwi'])->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
    ]);
    Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Bima Arya Saputra'])->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
    ]);

    Livewire::test('pages::rapor-list')
        ->set('cari', 'Bima')
        ->assertSee('Bima Arya Saputra')
        ->assertDontSee('Andini Pratiwi');
});

it('tidak menampilkan rapor kelas milik guru lain', function (): void {
    $kelasLain = Kelas::factory()->create(['User_ID' => User::factory()->create()->getKey()]);
    Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Siswa Kelas Lain'])->getKey(),
        'Kelas_ID' => $kelasLain->getKey(),
    ]);

    Livewire::test('pages::rapor-list')
        ->assertDontSee('Siswa Kelas Lain');
});

it('membuka layar isi rapor dengan tombol Isi Rapor', function (): void {
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);

    $rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::BelumDiisi,
    ]);

    Livewire::test('pages::rapor-list')
        ->call('isiRapor', $rapor->getKey())
        ->assertRedirect(route('rapor.input', [
            'rapor' => $rapor->getKey(),
            'mapel' => $this->mapel->getKey(),
        ]));
});

it('menolak isi rapor yang tidak ada', function (): void {
    Livewire::test('pages::rapor-list')
        ->call('isiRapor', 'R-0000000')
        ->assertStatus(404);
});
