<?php

use App\Enums\Semester;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->periode = TahunAjaran::factory()->create(['Semester_Aktif' => Semester::Ganjil]);
    $this->guru = User::factory()->create();
    $this->kelas = Kelas::factory()->create([
        'TahunAjaran_ID' => $this->periode->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);
    $this->mapel = MataPelajaran::factory()->create();
});

it('mengembalikan riwayat rapor siswa dari yang terbaru', function (): void {
    $siswa = Siswa::factory()->create();

    Rapor::factory()->create(['Siswa_ID' => $siswa->getKey(), 'Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Ganjil]);
    Rapor::factory()->create(['Siswa_ID' => $siswa->getKey(), 'Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Genap]);
    Rapor::factory()->create(['Siswa_ID' => Siswa::factory()->create()->getKey(), 'Kelas_ID' => $this->kelas->getKey()]);

    $riwayat = $siswa->getRiwayatRapor();

    expect($riwayat)->toHaveCount(2)
        ->and($riwayat->first()->Semester)->toBe(Semester::Genap)
        ->and($riwayat->first()->kelas)->not->toBeNull();
});

it('mengembalikan daftar rapor sebuah kelas, dapat disaring per semester', function (): void {
    Rapor::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Ganjil]);
    Rapor::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Ganjil]);
    Rapor::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Genap]);

    expect($this->kelas->getDaftarRapor())->toHaveCount(3)
        ->and($this->kelas->getDaftarRapor(Semester::Ganjil))->toHaveCount(2)
        ->and($this->kelas->getDaftarRapor(Semester::Genap))->toHaveCount(1)
        ->and($this->kelas->getDaftarRapor()->first()->siswa)->not->toBeNull();
});

it('mengembalikan program milik sebuah mata pelajaran beserta indikatornya', function (): void {
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);
    MataPelajaran::factory()->count(2)->create();

    $programMilikMapel = $this->mapel->getProgram();

    expect($programMilikMapel)->toHaveCount(1)
        ->and($programMilikMapel->first()->indikatorCapaian)->toHaveCount(1);
});

it('mengembalikan indikator sebuah program urut kode KD', function (): void {
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);

    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey(), 'Kode_KD' => 'KD-0003']);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey(), 'Kode_KD' => 'KD-0001']);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey(), 'Kode_KD' => 'KD-0002']);

    $kode = $program->getIndikator()->pluck('Kode_KD')->all();

    expect($kode)->toBe(['KD-0001', 'KD-0002', 'KD-0003']);
});

it('menyaring indikator program berdasarkan semester', function (): void {
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);

    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey(), 'Semester' => Semester::Ganjil]);
    IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey(), 'Semester' => Semester::Genap]);

    expect($program->getIndikator())->toHaveCount(2)
        ->and($program->getIndikator(Semester::Genap))->toHaveCount(1);
});

it('mengembalikan guru pengampu dari sebuah pembelajaran', function (): void {
    $pembelajaran = Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $this->mapel->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);

    expect($pembelajaran->getGuru())->not->toBeNull()
        ->and($pembelajaran->getGuru()->getKey())->toBe($this->guru->getKey());
});

it('mengembalikan null ketika pembelajaran belum ditugaskan', function (): void {
    $pembelajaran = Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $this->mapel->getKey(),
        'User_ID' => null,
    ]);

    expect($pembelajaran->getGuru())->toBeNull();
});

it('tetap konsisten dengan relasi yang menjadi dasarnya', function (): void {
    $pembelajaran = Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $this->mapel->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);

    expect($pembelajaran->getGuru()->getKey())->toBe($pembelajaran->guru->getKey());
});
