<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Enums\StatusNarasi;
use App\Models\Dokumentasi;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates prefixed year and counter keys', function (): void {
    $keys = [
        User::factory()->create()->getKey(),
        TahunAjaran::factory()->create()->getKey(),
        Kelas::factory()->create()->getKey(),
        Siswa::factory()->create()->getKey(),
        MataPelajaran::factory()->create()->getKey(),
        Pembelajaran::factory()->create()->getKey(),
        ProgramPengembangan::factory()->create()->getKey(),
        IndikatorCapaian::factory()->create()->getKey(),
        Rapor::factory()->create()->getKey(),
        Narasi::factory()->create()->getKey(),
        NilaiSiswa::factory()->create()->getKey(),
        Dokumentasi::factory()->create()->getKey(),
    ];

    $prefixes = ['U-', 'TA-', 'K-', 'S-', 'MP-', 'PM-', 'PP-', 'IC-', 'R-', 'N-', 'NS-', 'D-'];
    $year = now()->format('y');

    foreach ($keys as $index => $key) {
        $prefix = $prefixes[$index];

        expect($key)->toStartWith($prefix.$year)
            ->and($key)->toMatch('/^'.preg_quote($prefix, '/').'\d{2}\d{5}$/')
            ->and(strlen($key))->toBe(strlen($prefix) + 7);
    }
});

it('counts up within a year and never repeats a key', function (): void {
    $pertama = Rapor::factory()->create(['Kelas_ID' => Kelas::factory()])->getKey();
    $kedua = Rapor::factory()->create(['Kelas_ID' => Kelas::factory()])->getKey();
    $ketiga = Rapor::factory()->create(['Kelas_ID' => Kelas::factory()])->getKey();

    $suffix = fn (string $key): int => (int) substr($key, -5);

    expect($suffix($pertama))->toBe(1)
        ->and($suffix($kedua))->toBe(2)
        ->and($suffix($ketiga))->toBe(3);
});

it('keeps a caller supplied key instead of generating one', function (): void {
    $siswa = Siswa::factory()->create(['Siswa_ID' => 'S-2000001']);

    expect($siswa->getKey())->toBe('S-2000001');
});

it('resolves route bindings for prefixed keys', function (): void {
    $rapor = Rapor::factory()->create();

    expect((new Rapor)->resolveRouteBinding($rapor->getKey())?->is($rapor))->toBeTrue();
});

it('rejects a key that is not one of ours during route binding', function (): void {
    Rapor::factory()->create();

    (new Rapor)->resolveRouteBinding('K-2600001');
})->throws(ModelNotFoundException::class);

it('casts stored values to their php types', function (): void {
    $rapor = Rapor::factory()->create([
        'Semester' => Semester::Genap,
        'Status' => RaporStatus::Draft,
        'Tinggi_Badan' => 150,
    ]);

    expect($rapor->Semester)->toBe(Semester::Genap)
        ->and($rapor->Status)->toBe(RaporStatus::Draft)
        ->and($rapor->Tinggi_Badan)->toBe('150.0');
});

it('resolves the whole relationship graph from the seeded records', function (): void {
    $tahunAjaran = TahunAjaran::factory()->ganjil()->create();
    $guru = User::factory()->create();
    $kelas = Kelas::factory()->create([
        'TahunAjaran_ID' => $tahunAjaran->getKey(),
        'User_ID' => $guru->getKey(),
    ]);
    $mapel = MataPelajaran::factory()->create();
    $pembelajaran = Pembelajaran::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'User_ID' => $guru->getKey(),
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    $indikator = IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'TahunAjaran_ID' => $tahunAjaran->getKey(),
    ]);

    $siswa = Siswa::factory()->create();
    $rapor = Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Ganjil,
    ]);
    $narasi = Narasi::factory()->create([
        'Rapor_ID' => $rapor->getKey(),
        'Mapel_ID' => $mapel->getKey(),
    ]);
    $nilai = NilaiSiswa::factory()->create([
        'Narasi_ID' => $narasi->getKey(),
        'Indikator_ID' => $indikator->getKey(),
    ]);
    $dokumentasi = Dokumentasi::factory()->untukNarasi($narasi)->create();

    $holds = fn ($collection, $model): bool => $collection->contains(
        fn ($item): bool => $item->is($model)
    );

    expect($holds($tahunAjaran->kelas, $kelas))->toBeTrue()
        ->and($kelas->waliKelas->is($guru))->toBeTrue()
        ->and($holds($kelas->pembelajaran, $pembelajaran))->toBeTrue()
        ->and($pembelajaran->guru->is($guru))->toBeTrue()
        ->and($pembelajaran->mataPelajaran->is($mapel))->toBeTrue()
        ->and($holds($mapel->programPengembangan, $program))->toBeTrue()
        ->and($holds($program->indikatorCapaian, $indikator))->toBeTrue()
        ->and($holds($tahunAjaran->indikatorCapaian, $indikator))->toBeTrue()
        ->and($holds($siswa->rapor, $rapor))->toBeTrue()
        ->and($holds($kelas->rapor, $rapor))->toBeTrue()
        ->and($holds($rapor->narasi, $narasi))->toBeTrue()
        ->and($holds($narasi->nilaiSiswa, $nilai))->toBeTrue()
        ->and($holds($narasi->dokumentasi, $dokumentasi))->toBeTrue()
        ->and($holds($indikator->nilaiSiswa, $nilai))->toBeTrue()
        ->and($nilai->pemilikNilai()?->is($siswa))->toBeTrue();
});

it('moves a report card through the approval workflow', function (): void {
    $kepsek = User::factory()->create();
    $rapor = Rapor::factory()->draft()->create();

    expect($rapor->setujui($kepsek))->toBeFalse()
        ->and($rapor->kembalikanRevisi('Belum lengkap'))->toBeFalse();

    expect($rapor->ajukanPersetujuan())->toBeTrue();
    expect($rapor->fresh()->Status)->toBe(RaporStatus::Menunggu);

    expect($rapor->setujui($kepsek))->toBeTrue();
    expect($rapor->fresh()->Status)->toBe(RaporStatus::Disetujui)
        ->and($rapor->fresh()->Disetujui_Oleh)->toBe($kepsek->getKey())
        ->and($rapor->fresh()->Tanggal_Persetujuan)->not->toBeNull();
});

it('counts only approved report cards towards a class progress', function (): void {
    $kepsek = User::factory()->create();
    $kelas = Kelas::factory()->create();

    Rapor::factory()->create(['Kelas_ID' => $kelas->getKey()]);
    Rapor::factory()->menunggu()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Genap,
    ]);
    Rapor::factory()->perluRevisi()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Ganjil,
    ]);
    Rapor::factory()->disetujui($kepsek)->create([
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => Semester::Genap,
    ]);

    expect($kelas->hitungProgres())->toBe(25);
});

it('reports zero progress for a class with no approved report cards', function (): void {
    $kelas = Kelas::factory()->create();
    Rapor::factory()->menunggu()->create(['Kelas_ID' => $kelas->getKey()]);

    expect($kelas->hitungProgres())->toBe(0);
});

it('resolves the subjects a report card covers through its class', function (): void {
    $mapel = MataPelajaran::factory()->create();
    $kelas = Kelas::factory()->create();
    Pembelajaran::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Mapel_ID' => $mapel->getKey(),
    ]);
    $rapor = Rapor::factory()->create(['Kelas_ID' => $kelas->getKey()]);

    expect($rapor->mataPelajaranRapor()->pluck('Mapel_ID')->all())->toBe([$mapel->getKey()]);
});

it('moves a narrative through draft, edited and final', function (): void {
    $narasi = Narasi::factory()->create();

    $narasi->simpanDraft('Hasil generate.');
    expect($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Draft)
        ->and($narasi->fresh()->Tanggal_Generate)->not->toBeNull();

    $narasi->editNarasi('Hasil generate, sudah disunting guru.');
    expect($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Diedit)
        ->and($narasi->fresh()->Tanggal_Edit)->not->toBeNull();

    $narasi->simpanNarasiFinal();
    expect($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Final)
        ->and($narasi->fresh()->Narasi_Final)->toBe('Hasil generate, sudah disunting guru.')
        ->and($narasi->fresh()->teks())->toBe('Hasil generate, sudah disunting guru.');
});
