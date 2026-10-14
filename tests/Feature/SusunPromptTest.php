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
use App\Services\Narasi\InstruksiNarasi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function narasiDenganCapaian(array $capaian = []): Narasi
{
    $kelas = Kelas::factory()->create();
    $siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi']);
    // Nama_Mapel is unique, and this helper may run more than once per test.
    $mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Jati Diri '.fake()->unique()->numerify('###')]);

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

it('menyusun prompt berisi identitas peserta didik', function (): void {
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)
        ->toContain('Andini Pratiwi')
        ->toContain('Jati Diri')
        ->toContain('DATA PESERTA DIDIK');
});

it('menuangkan hanya capaian yang ditandai untuk narasi', function (): void {
    $prompt = narasiDenganCapaian([
        'Mampu bekerja sama dalam kelompok' => true,
        'Perlu bimbingan saat menggambar' => false,
    ])->susunPrompt();

    expect($prompt)
        ->toContain('Mampu bekerja sama dalam kelompok')
        ->not->toContain('Perlu bimbingan saat menggambar');
});

it('menyertakan tingkat capaian sebagai penanda untuk model', function (): void {
    $prompt = narasiDenganCapaian(['Mampu bekerja sama dalam kelompok' => true])->susunPrompt();

    // The level travels as a bracket code the model is told not to print, so it
    // can pitch the wording to what the child actually reached.
    expect($prompt)->toMatch('/\((MB|BSH|BSB)\)/');
});

it('menyertakan catatan pengamatan guru bila ada', function (): void {
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)
        ->toContain('CATATAN PENGAMATAN GURU')
        ->toContain('Anak mulai berani berbicara di depan kelas.');
});

it('tidak membocorkan aturan panjang dan gaya ke prompt pengguna', function (): void {
    // Those belong to the system instructions, which are identical on every call.
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)->not->toContain('tiga sampai empat kalimat');
});

it('tetap menyusun prompt walau tanpa capaian dan catatan', function (): void {
    $narasi = narasiDenganCapaian();
    $narasi->update(['Catatan_Guru' => null]);

    $prompt = $narasi->fresh()->susunPrompt();

    expect($prompt)->toContain('Andini Pratiwi')
        ->not->toContain('CATATAN PENGAMATAN GURU');
});

it('menyusun prompt dengan urutan yang tetap agar awalan dapat di-cache', function (): void {
    // Two students must share the same section order, so the provider can serve
    // the repeated prefix from its prompt cache.
    $judul = fn (string $p): array => array_values(array_filter(
        preg_split('/\R/', $p),
        fn (string $b): bool => str_starts_with($b, 'DATA PESERTA DIDIK')
            || str_starts_with($b, 'CAPAIAN YANG DICERITAKAN')
            || str_starts_with($b, 'CATATAN PENGAMATAN GURU')
            || str_starts_with($b, 'Tulis narasi'),
    ));

    expect($judul(narasiDenganCapaian()->susunPrompt()))
        ->toBe($judul(narasiDenganCapaian()->susunPrompt()));
});

describe('instruksi sistem', function (): void {
    it('meminta narasi dengan panjang seperti contoh rapor sekolah', function (): void {
        // The school's samples run three to four sentences; without this the model
        // returns one thin sentence, which is not what gets printed.
        expect(InstruksiNarasi::SISTEM)
            ->toContain('tiga sampai empat kalimat')
            ->toContain('75')
            ->toContain('100 kata');
    });

    it('meminta struktur contoh rapor: pembuka, pengamatan, harapan', function (): void {
        $sistem = InstruksiNarasi::SISTEM;

        expect($sistem)
            ->toContain('Buka dengan')
            ->toContain('pengamatan yang konkret')
            ->toContain('harapan untuk perkembangan anak selanjutnya');
    });

    it('melarang kode tingkat dan nilai angka di dalam narasi', function (): void {
        expect(InstruksiNarasi::SISTEM)
            ->toContain('Jangan pernah menuliskan kode')
            ->toContain('BSH');
    });

    it('melarang daftar dan pembuka tambahan', function (): void {
        expect(InstruksiNarasi::SISTEM)
            ->toContain('Jangan membuat daftar')
            ->toContain('Balas hanya dengan narasinya');
    });

    it('membatasi nama anak pada kalimat pertama', function (): void {
        expect(InstruksiNarasi::SISTEM)->toContain('Sebut nama anak pada kalimat pertama saja');
    });

    it('memuat contoh bentuk tanpa menanamkan nama tertentu', function (): void {
        // A hardcoded name in the example teaches the model to reuse it.
        expect(InstruksiNarasi::SISTEM)
            ->toContain('[nama]')
            ->not->toContain('Arana');
    });

    it('ditulis dalam bahasa Indonesia', function (): void {
        expect(InstruksiNarasi::SISTEM)->toContain('bahasa Indonesia');
    });
});
