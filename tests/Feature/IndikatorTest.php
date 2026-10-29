<?php

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
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
        ->set('formMapel', $mapel->getKey())
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

it('menolak menghapus indikator yang sudah memiliki nilai siswa', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $indikator = IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => TahunAjaran::query()->where('Is_Active', true)->firstOrFail()->getKey(),
    ]);

    $narasi = Narasi::factory()->create([
        'Rapor_ID' => Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create()->getKey(),
            'Kelas_ID' => Kelas::factory()->create()->getKey(),
        ])->getKey(),
    ]);
    NilaiSiswa::factory()->create([
        'Indikator_ID' => $indikator->getKey(),
        'Narasi_ID' => $narasi->getKey(),
    ]);

    Livewire::test('pages::indikator')
        ->call('hapus', $indikator->getKey())
        ->assertHasNoErrors()
        ->assertSee('tidak dapat dihapus');

    expect(IndikatorCapaian::query()->find($indikator->getKey()))->not->toBeNull();
});

it('mempertahankan nilai siswa saat indikator ditolak dihapus', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $indikator = IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => TahunAjaran::query()->where('Is_Active', true)->firstOrFail()->getKey(),
    ]);
    $narasi = Narasi::factory()->create([
        'Rapor_ID' => Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create()->getKey(),
            'Kelas_ID' => Kelas::factory()->create()->getKey(),
        ])->getKey(),
    ]);
    $nilai = NilaiSiswa::factory()->create([
        'Indikator_ID' => $indikator->getKey(),
        'Narasi_ID' => $narasi->getKey(),
    ]);

    Livewire::test('pages::indikator')->call('hapus', $indikator->getKey());

    expect(NilaiSiswa::query()->find($nilai->getKey()))->not->toBeNull();
});

it('menyimpan indikator ke program milik mata pelajaran yang dipilih', function (): void {
    $mapelLain = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true, 'Nama_Mapel' => 'Bahasa Inggris']);
    ProgramPengembangan::factory()->create(['Mapel_ID' => $mapelLain->getKey(), 'Nama_Program' => 'Bahasa']);
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true, 'Nama_Mapel' => 'Jati Diri']);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey(), 'Nama_Program' => 'Mengenal diri']);

    Livewire::test('pages::indikator')
        ->set('filterJenjang', Jenjang::Tka->value)
        ->set('filterSemester', Semester::Ganjil->value)
        ->call('tambah')
        ->set('formMapel', $mapel->getKey())
        ->assertSet('formProgram', $program->getKey())
        ->set('formDeskripsi', 'Percaya diri saat tampil')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('filterMapel', $mapel->getKey())
        ->assertSee('Percaya diri saat tampil');

    expect(IndikatorCapaian::query()->where('Deskripsi', 'Percaya diri saat tampil')->value('Program_ID'))
        ->toBe($program->getKey());
});

it('menolak program dari mata pelajaran lain', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);
    $programLain = ProgramPengembangan::factory()->create();

    Livewire::test('pages::indikator')
        ->set('filterJenjang', Jenjang::Tka->value)
        ->set('filterSemester', Semester::Ganjil->value)
        ->call('tambah')
        ->set('formMapel', $mapel->getKey())
        ->set('formProgram', $programLain->getKey())
        ->set('formDeskripsi', 'Indikator salah tempat')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->call('simpan')
        ->assertHasErrors(['formProgram' => 'exists'])
        ->assertSee('Program tidak termasuk mata pelajaran yang dipilih.');

    expect(IndikatorCapaian::query()->where('Deskripsi', 'Indikator salah tempat')->exists())->toBeFalse();
});

it('membuat program baru untuk mata pelajaran yang belum punya program', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);

    Livewire::test('pages::indikator')
        ->set('filterJenjang', Jenjang::Tka->value)
        ->set('filterSemester', Semester::Ganjil->value)
        ->call('tambah')
        ->set('formMapel', $mapel->getKey())
        ->assertSet('formProgram', null)
        ->set('formProgramBaru', 'Mengenal emosi diri')
        ->set('formDeskripsi', 'Mengungkapkan perasaan')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->call('simpan')
        ->assertHasNoErrors();

    $program = ProgramPengembangan::query()->where('Nama_Program', 'Mengenal emosi diri')->firstOrFail();

    expect($program->Mapel_ID)->toBe($mapel->getKey())
        ->and(IndikatorCapaian::query()->where('Deskripsi', 'Mengungkapkan perasaan')->value('Program_ID'))
        ->toBe($program->getKey());
});

it('meminta program saat mata pelajaran dipilih tanpa program', function (): void {
    $mapel = MataPelajaran::factory()->create(['Tampilkan_Indikator' => true]);

    Livewire::test('pages::indikator')
        ->set('filterJenjang', Jenjang::Tka->value)
        ->set('filterSemester', Semester::Ganjil->value)
        ->call('tambah')
        ->set('formMapel', $mapel->getKey())
        ->set('formDeskripsi', 'Tanpa program')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->call('simpan')
        ->assertHasErrors(['formProgram' => 'required_without'])
        ->assertSee('Pilih program pengembangan atau tulis nama program baru.');
});

it('menolak indikator baru sebelum jenjang dan semester dipilih', function (): void {
    $program = ProgramPengembangan::factory()->create();

    Livewire::test('pages::indikator')
        ->call('tambah')
        ->set('formMapel', $program->Mapel_ID)
        ->set('formProgram', $program->getKey())
        ->set('formDeskripsi', 'Tanpa jenjang')
        ->set('formTipe', TipeIndikator::Capaian->value)
        ->call('simpan')
        ->assertHasErrors(['filterJenjang' => 'required', 'filterSemester' => 'required'])
        ->assertSee('Pilih jenjang pada filter di atas terlebih dahulu.');

    expect(IndikatorCapaian::query()->where('Deskripsi', 'Tanpa jenjang')->exists())->toBeFalse();
});

it('mempertahankan jenjang dan semester saat indikator diubah', function (): void {
    $program = ProgramPengembangan::factory()->create();
    $indikator = IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => TahunAjaran::query()->where('Is_Active', true)->firstOrFail()->getKey(),
        'Jenjang' => Jenjang::Tkb,
        'Semester' => Semester::Genap,
    ]);

    Livewire::test('pages::indikator')
        ->call('ubah', $indikator->getKey())
        ->set('formDeskripsi', 'Deskripsi diperbarui')
        ->call('simpan')
        ->assertHasNoErrors();

    $indikator->refresh();

    expect($indikator->Deskripsi)->toBe('Deskripsi diperbarui')
        ->and($indikator->Jenjang)->toBe(Jenjang::Tkb)
        ->and($indikator->Semester)->toBe(Semester::Genap);
});
