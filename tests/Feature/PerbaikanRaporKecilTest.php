<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }
});

function teksNarasi(?string $teks): string
{
    return Blade::render('<x-rapor.teks-narasi :teks="$teks" />', ['teks' => $teks]);
}

describe('teks narasi pada rapor', function (): void {
    it('memisahkan butir bernomor dari penjelasannya', function (): void {
        $html = teksNarasi("Pengantar proyek:\n1. Kolaborasi\nIa bekerja sama.\n2. Kemandirian\nIa mencoba sendiri.");

        expect($html)
            ->toContain('<p class="mb-2">Pengantar proyek:</p>')
            ->toContain('<p class="narasi-butir">1. Kolaborasi</p>')
            ->toContain('<p class="narasi-rincian">Ia bekerja sama.</p>')
            ->toContain('<p class="narasi-butir">2. Kemandirian</p>');
    });

    it('menandai daftar berpoin sebagai butir', function (): void {
        expect(teksNarasi("- Berdoa sebelum makan\n- Berbagi bekal"))
            ->toContain('<p class="narasi-butir">- Berdoa sebelum makan</p>');
    });

    it('tidak menganggap angka di awal kalimat biasa sebagai butir', function (): void {
        expect(teksNarasi('2025 menjadi tahun pertamanya di sekolah.'))
            ->toContain('<p class="mb-2">2025 menjadi tahun pertamanya di sekolah.</p>');
    });

    it('mengembalikan paragraf biasa setelah baris kosong', function (): void {
        expect(teksNarasi("1. Kolaborasi\nIa bekerja sama.\n\nHarapan ke depan."))
            ->toContain('<p class="mb-2">Harapan ke depan.</p>');
    });

    it('meloloskan html di dalam narasi', function (): void {
        expect(teksNarasi('<script>alert(1)</script>'))->not->toContain('<script>');
    });

    it('menampilkan pesan kosong bila narasi belum ada', function (?string $teks): void {
        expect(teksNarasi($teks))->toContain('Belum ada narasi.');
    })->with([null, '', "  \n\n  "]);
});

describe('nama kepala sekolah pada rapor', function (): void {
    beforeEach(function (): void {
        $this->rapor = Rapor::factory()->create();
        $this->kepsek = User::factory()->create(['Role_ID' => 'KEPSEKOL', 'Nama' => 'Kepsek Aktif']);
    });

    it('memakai kepala sekolah yang menyetujui, walau bukan yang aktif sekarang', function (): void {
        $lama = User::factory()->create(['Role_ID' => 'KEPSEKOL', 'Nama' => 'Kepsek Lama', 'Is_Active' => false]);
        $this->rapor->update(['Disetujui_Oleh' => $lama->getKey()]);

        expect($this->rapor->fresh()->namaKepalaSekolah())->toBe('Kepsek Lama');
    });

    it('memakai kepala sekolah aktif bila rapor disetujui administrator', function (): void {
        $admin = User::factory()->create(['Role_ID' => 'ADMIN', 'Nama' => 'Admin Sekolah']);
        $this->rapor->update(['Disetujui_Oleh' => $admin->getKey()]);

        expect($this->rapor->fresh()->namaKepalaSekolah())->toBe('Kepsek Aktif');
    });

    it('memakai kepala sekolah aktif bila rapor belum disetujui', function (): void {
        expect($this->rapor->namaKepalaSekolah())->toBe('Kepsek Aktif');
    });
});

describe('penempatan siswa', function (): void {
    beforeEach(function (): void {
        TahunAjaran::factory()->create(['Is_Active' => true, 'Semester_Aktif' => Semester::Ganjil]);
        $this->actingAs(User::factory()->create(['Role_ID' => 'TATAUSA']));
    });

    it('menonaktifkan tombol simpan selama belum ada perubahan', function (): void {
        Livewire::test('pages::penempatan')
            ->assertSeeHtmlInOrder(['disabled', 'Simpan Penempatan'])
            ->assertDontSeeHtml('@disabled')
            ->assertDontSeeHtml(':checked');
    });

    it('tidak mengumumkan penyimpanan bila tidak ada perubahan', function (): void {
        Livewire::test('pages::penempatan')
            ->call('simpan')
            ->assertDontSee('penempatan disimpan');
    });

    it('mencentang siswa yang dipilih', function (): void {
        $siswa = Siswa::factory()->create();
        $kelas = Kelas::factory()->create();

        $halaman = Livewire::test('pages::penempatan')->set('kelasTujuan', $kelas->getKey());
        $tercentang = '/<input type="checkbox"[^>]*\schecked\s/';

        expect($halaman->html())->not->toMatch($tercentang);

        $halaman->call('toggle', $siswa->getKey());

        expect($halaman->html())->toMatch($tercentang);
    });
});

it('tidak menyebut mata pelajaran pertama pada kartu revisi dashboard', function (): void {
    TahunAjaran::factory()->create(['Is_Active' => true]);
    $wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $kelas = Kelas::factory()->create(['User_ID' => $wali->getKey()]);
    $rapor = Rapor::factory()->create([
        'Kelas_ID' => $kelas->getKey(),
        'Status' => RaporStatus::PerluRevisi,
        'Catatan_Revisi' => 'Narasi Komputer perlu diperbaiki.',
    ]);
    $mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Mapel Pertama Rapor']);
    Narasi::factory()->create(['Rapor_ID' => $rapor->getKey(), 'Mapel_ID' => $mapel->getKey()]);

    $this->actingAs($wali);

    Livewire::test('pages::dashboard')
        ->assertSee('Narasi Komputer perlu diperbaiki.')
        ->assertDontSee('Mapel Pertama Rapor');
});

it('menampilkan pesan validasi dalam bahasa Indonesia', function (): void {
    $this->actingAs(User::factory()->create(['Role_ID' => 'TATAUSA']));

    Livewire::test('pages::siswa-form')
        ->call('simpan')
        ->assertSee('jenis kelamin wajib diisi')
        ->assertDontSee('field is required');
});

it('mengisi data demo dengan narasi atas nama tiap anak dan penyetujunya', function (): void {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);

    $bima = Rapor::query()->whereRelation('siswa', 'Nama', 'Bima Arya Saputra')->firstOrFail();

    expect($bima->narasi->pluck('Narasi_Final')->implode(' '))
        ->toContain('Bima')
        ->not->toContain('Andini')
        ->and($bima->namaKepalaSekolah())->toBe('Ibu Mei')
        ->and($bima->Tanggal_Persetujuan)->not->toBeNull();
});
