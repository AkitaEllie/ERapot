<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;

it('places a student by creating an empty report draft', function (): void {
    $siswa = Siswa::factory()->create();
    $kelas = Kelas::factory()->create();

    $rapor = Rapor::pempatkan($siswa, $kelas, Semester::Ganjil);

    expect($rapor->Status)->toBe(RaporStatus::BelumDiisi)
        ->and($rapor->Siswa_ID)->toBe($siswa->getKey())
        ->and($rapor->Kelas_ID)->toBe($kelas->getKey());
});

it('moves an existing report rather than duplicating it on re-placement', function (): void {
    $siswa = Siswa::factory()->create();
    $kelasAwal = Kelas::factory()->create();
    $kelasTujuan = Kelas::factory()->create();

    Rapor::pempatkan($siswa, $kelasAwal, Semester::Ganjil);
    Rapor::pempatkan($siswa, $kelasTujuan, Semester::Ganjil);

    expect(Rapor::where('Siswa_ID', $siswa->getKey())->count())->toBe(1)
        ->and(Rapor::where('Siswa_ID', $siswa->getKey())->first()->Kelas_ID)
        ->toBe($kelasTujuan->getKey());
});

it('keeps the other semester untouched when a student is re-placed', function (): void {
    $siswa = Siswa::factory()->create();
    $kelasAwal = Kelas::factory()->create();
    $kelasTujuan = Kelas::factory()->create();

    Rapor::pempatkan($siswa, $kelasAwal, Semester::Ganjil);
    Rapor::pempatkan($siswa, $kelasAwal, Semester::Genap);
    Rapor::pempatkan($siswa, $kelasTujuan, Semester::Ganjil);

    expect(Rapor::where('Siswa_ID', $siswa->getKey())->where('Semester', Semester::Ganjil)
        ->first()->Kelas_ID)->toBe($kelasTujuan->getKey())
        ->and(Rapor::where('Siswa_ID', $siswa->getKey())->where('Semester', Semester::Genap)
            ->first()->Kelas_ID)->toBe($kelasAwal->getKey());
});

it('lists only students without a report for the given semester', function (): void {
    $terempat = Siswa::factory()->create();
    $belum = Siswa::factory()->create();

    Rapor::pempatkan($terempat, Kelas::factory()->create(), Semester::Ganjil);

    $ids = Siswa::belumDitempatkan(Semester::Ganjil)->pluck('Siswa_ID')->all();

    expect($ids)->toContain($belum->getKey())
        ->and($ids)->not->toContain($terempat->getKey());
});

it('treats a student placed in a different semester as still unplaced', function (): void {
    $siswa = Siswa::factory()->create();
    Rapor::pempatkan($siswa, Kelas::factory()->create(), Semester::Genap);

    expect(Siswa::belumDitempatkan(Semester::Ganjil)->pluck('Siswa_ID')->all())
        ->toContain($siswa->getKey());
});

it('counts report progress per class for a single semester', function (): void {
    $kelas = Kelas::factory()->create();
    $lain = Kelas::factory()->create();

    Rapor::factory()->count(3)->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Ganjil,
        'Status' => RaporStatus::Disetujui,
    ]);
    Rapor::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Ganjil,
        'Status' => RaporStatus::BelumDiisi,
    ]);
    Rapor::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Genap,
        'Status' => RaporStatus::Disetujui,
    ]);

    $hasil = Kelas::denganProgres(Semester::Ganjil)->keyBy(fn (Kelas $item) => $item->getKey());

    expect($hasil[$kelas->getKey()]->total_rapor)->toBe(4)
        ->and($hasil[$kelas->getKey()]->rapor_disetujui)->toBe(3)
        ->and($hasil[$lain->getKey()]->total_rapor)->toBe(0)
        ->and($hasil[$lain->getKey()]->rapor_disetujui)->toBe(0);
});

it('assigns a teacher to a class and subject only once', function (): void {
    $kelas = Kelas::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    $guru = User::factory()->create();

    $pertama = Pembelajaran::tugaskan($kelas, $mapel, $guru);
    $kedua = Pembelajaran::tugaskan($kelas, $mapel, $guru);

    expect($pertama->getKey())->toBe($kedua->getKey())
        ->and(Pembelajaran::where('Kelas_ID', $kelas->getKey())->count())->toBe(1)
        ->and($kedua->guru->getKey())->toBe($guru->getKey());
});

it('leaves the teaching slot unassigned when no teacher is given', function (): void {
    $kelas = Kelas::factory()->create();
    $mapel = MataPelajaran::factory()->create();

    $pembelajaran = Pembelajaran::tugaskan($kelas, $mapel, null);

    expect($pembelajaran->User_ID)->toBeNull()
        ->and($pembelajaran->guru)->toBeNull();
});

it('replaces a previously assigned teacher on reassignment', function (): void {
    $kelas = Kelas::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    $lama = User::factory()->create();
    $baru = User::factory()->create();

    Pembelajaran::tugaskan($kelas, $mapel, $lama);
    $hasil = Pembelajaran::tugaskan($kelas, $mapel, $baru);

    expect($hasil->User_ID)->toBe($baru->getKey())
        ->and(Pembelajaran::where('Kelas_ID', $kelas->getKey())->count())->toBe(1);
});
