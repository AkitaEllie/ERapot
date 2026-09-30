<?php

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\MataPelajaran;
use App\Models\ProgramPengembangan;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->guru = User::factory()->create();
    $this->actingAs($this->guru);

    TahunAjaran::factory()->create(['Is_Active' => true, 'Tahun_Ajaran' => '2025/2026']);
});

it('menampilkan indikator terkelompok per program', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $tahunAjaran = TahunAjaran::query()->where('Is_Active', true)->firstOrFail();

    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => $tahunAjaran->getKey(),
        'Deskripsi' => 'Mempraktikkan doa sebelum dan sesudah kegiatan',
        'Tipe' => TipeIndikator::Capaian,
    ]);

    Livewire::test('pages::indikator')
        ->assertOk()
        ->assertSee($program->Nama_Program)
        ->assertSee('Mempraktikkan doa sebelum dan sesudah kegiatan');
});

it('memfilter indikator berdasarkan tipe', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $tahunAjaran = TahunAjaran::query()->where('Is_Active', true)->firstOrFail();

    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => $tahunAjaran->getKey(),
        'Deskripsi' => 'Indikator capaian',
        'Tipe' => TipeIndikator::Capaian,
    ]);
    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => TahunAjaran::query()->where('Is_Active', true)->firstOrFail()->getKey(),
        'Deskripsi' => 'Indikator perilaku',
        'Tipe' => TipeIndikator::Perilaku,
    ]);

    Livewire::test('pages::indikator')
        ->set('filterTipe', TipeIndikator::Capaian->value)
        ->assertSee('Indikator capaian')
        ->assertDontSee('Indikator perilaku');
});

it('menambah indikator baru', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $tahunAjaran = TahunAjaran::query()->where('Is_Active', true)->firstOrFail();

    Livewire::test('pages::indikator')
        ->call('tambah')
        ->set('formProgram', $program->getKey())
        ->set('formDeskripsi', 'Indikator baru dari guru')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->set('filterJenjang', Jenjang::cases()[0]->value)
        ->set('filterSemester', Semester::Ganjil->value)
        ->call('simpan')
        ->assertHasNoErrors();

    expect(IndikatorCapaian::query()->where('Deskripsi', 'Indikator baru dari guru')->exists())->toBeTrue()
        ->and(IndikatorCapaian::query()->where('Deskripsi', 'Indikator baru dari guru')->first()->TahunAjaran_ID)
        ->toBe($tahunAjaran->getKey());
});

it('menghapus indikator', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $indikator = IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => TahunAjaran::query()->where('Is_Active', true)->firstOrFail()->getKey(),
    ]);

    Livewire::test('pages::indikator')
        ->call('hapus', $indikator->getKey());

    expect(IndikatorCapaian::query()->find($indikator->getKey()))->toBeNull();
});
