<?php

use App\Enums\JenisMataPelajaran;
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

function narasiDenganCapaian(
    array $capaian = [],
    JenisMataPelajaran $jenis = JenisMataPelajaran::Intrakurikuler,
): Narasi {
    $kelas = Kelas::factory()->create();
    $siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi']);
    // Nama_Mapel is unique, and this helper may run more than once per test.
    $mapel = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Jati Diri '.fake()->unique()->numerify('###'),
        'Jenis_Mapel' => $jenis,
    ]);

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
    // The standing rules belong to the system instructions, shared by every call.
    $prompt = narasiDenganCapaian()->susunPrompt();

    expect($prompt)->not->toContain('Balas hanya dengan narasinya');
});

it('menyertakan gaya prosa pada prompt mata pelajaran intrakurikuler', function (): void {
    $prompt = narasiDenganCapaian(jenis: JenisMataPelajaran::Intrakurikuler)->susunPrompt();

    expect($prompt)->toContain('Tulis tiga paragraf');
});

it('menyertakan gaya catatan pada prompt mata pelajaran muatan lokal', function (): void {
    $prompt = narasiDenganCapaian(jenis: JenisMataPelajaran::MuatanLokal)->susunPrompt();

    expect($prompt)
        ->toContain('satu paragraf saja')
        ->not->toContain('Tulis tiga paragraf');
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
    it('meminta struktur contoh rapor: pembuka, pengamatan, harapan', function (): void {
        expect(InstruksiNarasi::SISTEM)
            ->toContain('Buka dengan')
            ->toContain('pengamatan yang konkret')
            ->toContain('harapan untuk perkembangan anak selanjutnya');
    });

    it('melarang kode tingkat dan nilai angka di dalam narasi', function (): void {
        expect(InstruksiNarasi::SISTEM)
            ->toContain('Jangan pernah menuliskan kode')
            ->toContain('BSH');
    });

    it('meminta hanya narasinya tanpa pembuka tambahan', function (): void {
        expect(InstruksiNarasi::SISTEM)
            ->toContain('Balas hanya dengan narasinya')
            ->toContain('Jangan menambahkan pembuka');
    });

    it('membatasi nama anak pada kalimat pertama', function (): void {
        expect(InstruksiNarasi::SISTEM)->toContain('Sebut nama anak pada kalimat pertama saja');
    });

    it('ditulis dalam bahasa Indonesia', function (): void {
        expect(InstruksiNarasi::SISTEM)->toContain('bahasa Indonesia');
    });
});

describe('gaya per bentuk halaman', function (): void {
    it('memilih gaya prosa untuk mata pelajaran intrakurikuler', function (): void {
        expect(InstruksiNarasi::gaya(JenisMataPelajaran::Intrakurikuler))
            ->toBe(InstruksiNarasi::GAYA_PROSA);
    });

    it('memilih gaya prosa untuk mata pelajaran kokurikuler', function (): void {
        expect(InstruksiNarasi::gaya(JenisMataPelajaran::KoKurikuler))
            ->toBe(InstruksiNarasi::GAYA_PROSA);
    });

    it('memilih gaya catatan untuk mata pelajaran muatan lokal', function (): void {
        expect(InstruksiNarasi::gaya(JenisMataPelajaran::MuatanLokal))
            ->toBe(InstruksiNarasi::GAYA_CATATAN);
    });

    it('meminta beberapa paragraf pada gaya prosa', function (): void {
        // The PDF's Section A prose runs to several paragraphs, and the report
        // book is the homeroom teacher's document.
        expect(InstruksiNarasi::GAYA_PROSA)
            ->toContain('Tulis tiga paragraf')
            ->toContain('baris kosong');
    });

    it('mengizinkan daftar berpoin pada gaya prosa saja', function (): void {
        // The reference's Section A lists habits; the Catatan box has no room.
        expect(InstruksiNarasi::GAYA_PROSA)->toContain('daftar berpoin')
            ->and(InstruksiNarasi::GAYA_CATATAN)->toContain('Jangan membuat daftar');
    });

    it('meminta satu paragraf pada gaya catatan', function (): void {
        expect(InstruksiNarasi::GAYA_CATATAN)->toContain('satu paragraf saja');
    });

    it('memuat target jumlah kata pada kedua gaya', function (): void {
        // The model honours a word target only when it is explicit, and the
        // sentence wraps inside the heredoc.
        expect(InstruksiNarasi::GAYA_PROSA)->toContain('Jumlah kata ini')
            ->and(InstruksiNarasi::GAYA_CATATAN)->toContain('Jumlah kata ini');
    });
});
