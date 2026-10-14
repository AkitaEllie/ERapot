<?php

use App\Enums\StatusNarasi;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function narasiDenganCapaian(array $capaian = []): Narasi
{
    $kelas = Kelas::factory()->create();
    $siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi']);
    $mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Jati Diri']);

    Pembelajaran::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Mapel_ID' => $mapel->getKey(),
    ]);

    $rapor = Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
    ]);

    $narasi = Narasi::factory()->create([
        'Rapor_ID' => $rapor->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'Catatan_Guru' => 'Anak mulai berani berbicara di depan kelas.',
        'Status_Narasi' => StatusNarasi::Draft,
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);

    foreach ($capaian as $teks => $dipilih) {
        $indikator = IndikatorCapaian::factory()->create([
            'Program_ID' => $program->getKey(),
            'Deskripsi' => $teks,
        ]);

        NilaiSiswa::factory()->create([
            'Narasi_ID' => $narasi->getKey(),
            'Indikator_ID' => $indikator->getKey(),
            'Dipilih_Untuk_Narasi' => $dipilih,
        ]);
    }

    return $narasi->fresh();
}

it('menyusun prompt dari identitas siswa dan mata pelajaran', function (): void {
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)->toContain('Andini Pratiwi')
        ->toContain('Jati Diri');
});

it('hanya menyertakan capaian yang ditandai untuk narasi', function (): void {
    $prompt = narasiDenganCapaian([
        'Mampu bekerja sama dalam kelompok' => true,
        'Perlu bimbingan saat menggambar' => false,
    ])->susunPrompt();

    expect($prompt)
        ->toContain('Mampu bekerja sama dalam kelompok')
        ->not->toContain('Perlu bimbingan saat menggambar');
});

it('menyertakan catatan personal guru bila ada', function (): void {
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)->toContain('Anak mulai berani berbicara di depan kelas.');
});

it('tetap menyusun prompt walau tidak ada capaian yang dipilih', function (): void {
    $narasi = narasiDenganCapaian();
    $narasi->update(['Catatan_Guru' => null]);

    $prompt = $narasi->fresh()->susunPrompt();

    expect($prompt)->toContain('Andini Pratiwi')
        ->and(trim($prompt))->not->toBe('');
});
