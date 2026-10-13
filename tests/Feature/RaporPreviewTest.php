<?php

use App\Enums\JenisMataPelajaran;
use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->guru = User::factory()->create();
    $this->kelas = Kelas::factory()->create(['User_ID' => $this->guru->getKey()]);
    $this->siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi', 'NISN' => '0154872301']);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => $this->siswa->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::Menunggu,
    ]);

    $this->actingAs($this->guru);
});

it('menampilkan seluruh buku rapor untuk guru wali kelas', function (): void {
    $mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Agama dan Budi Pekerti']);
    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);
    Narasi::factory()
        ->final('Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan.')
        ->create([
            'Rapor_ID' => $this->rapor->getKey(),
            'Mapel_ID' => $mapel->getKey(),
        ]);

    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('Andini Pratiwi')
        ->assertSee('Agama dan Budi Pekerti')
        ->assertSee('Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan.')
        ->assertSee('Lingkar Kepala')
        ->assertSee('Refleksi orang tua');
});

it('hanya menampilkan mata pelajaran sendiri untuk guru mata pelajaran', function (): void {
    $mapelSaya = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Bahasa Mandarin',
        'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
    ]);
    $mapelLain = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Budaya Humanis',
        'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
    ]);
    $guruLain = User::factory()->create();

    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $mapelSaya->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);
    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $mapelLain->getKey(),
        'User_ID' => $guruLain->getKey(),
    ]);

    // The acting user is not this class's homeroom teacher.
    $this->actingAs($guruLain);

    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('Budaya Humanis')
        ->assertDontSee('Bahasa Mandarin')
        // No book front matter for a subject teacher.
        ->assertDontSee('Refleksi orang tua')
        ->assertDontSee('Lingkar Kepala');
});

it('menampilkan peringatan dan menolak export saat rapor belum disetujui', function (): void {
    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertSee('Rapor belum disetujui Kepala Sekolah')
        ->call('exportPdf')
        ->assertSet('rapor.Status', RaporStatus::Menunggu);
});
