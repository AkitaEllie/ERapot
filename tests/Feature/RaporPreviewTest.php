<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
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

it('menampilkan pratinjau rapor beserta narasi final', function (): void {
    $mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Agama dan Budi Pekerti']);
    Narasi::factory()->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'Narasi_Final' => 'Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan.',
    ]);

    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('LAPORAN PERKEMBANGAN PESERTA DIDIK')
        ->assertSee('Andini Pratiwi')
        ->assertSee('0154872301')
        ->assertSee('Agama dan Budi Pekerti')
        ->assertSee('Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan.')
        ->assertSee('Ketidakhadiran');
});

it('menampilkan peringatan dan menolak export saat rapor belum disetujui', function (): void {
    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertSee('Rapor belum disetujui Kepala Sekolah')
        ->call('exportPdf')
        ->assertSet('rapor.Status', RaporStatus::Menunggu);
});
