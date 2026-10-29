<?php

use App\Enums\Nilai;
use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Dokumentasi;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['GURU', 'GURUWALI', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    Storage::fake(Rapor::DISK_PDF);

    $periode = TahunAjaran::factory()->create(['Is_Active' => true, 'Semester_Aktif' => Semester::Ganjil]);
    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $this->guruMapel = User::factory()->create(['Role_ID' => 'GURU']);
    $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $periode->getKey(), 'User_ID' => $this->wali->getKey()]);

    $this->mapelWali = MataPelajaran::factory()->create(['Nama_Mapel' => 'Nilai-nilai Agama & Budi Pekerti']);
    $this->mapelGuru = MataPelajaran::factory()->create(['Nama_Mapel' => 'Bahasa Inggris']);
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapelWali->getKey(), 'User_ID' => $this->wali->getKey()]);
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapelGuru->getKey(), 'User_ID' => $this->guruMapel->getKey()]);

    // A teacher assigned only in some other class.
    $this->guruKelasLain = User::factory()->create(['Role_ID' => 'GURU']);
    Pembelajaran::factory()->create(['Mapel_ID' => $this->mapelGuru->getKey(), 'User_ID' => $this->guruKelasLain->getKey()]);

    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Semester' => Semester::Ganjil,
        'Status' => RaporStatus::Draft,
    ]);
});

describe('layar input rapor', function (): void {
    it('menolak guru mata pelajaran membuka mata pelajaran yang tidak ditugaskan kepadanya', function (): void {
        Livewire::actingAs($this->guruMapel)
            ->test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelWali])
            ->assertForbidden();
    });

    it('menolak guru dari kelas lain membuka rapor', function (): void {
        Livewire::actingAs($this->guruKelasLain)
            ->test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelGuru])
            ->assertForbidden();
    });

    it('hanya menampilkan tab mata pelajaran milik guru mata pelajaran tanpa tombol ajukan', function (): void {
        Livewire::actingAs($this->guruMapel)
            ->test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelGuru])
            ->assertOk()
            ->assertSee('Bahasa Inggris')
            ->assertDontSee('Nilai-nilai Agama &amp; Budi Pekerti', escape: false)
            ->assertDontSee('Ajukan Rapor');
    });

    it('membuka seluruh mata pelajaran kelas untuk wali kelas', function (): void {
        Livewire::actingAs($this->wali)
            ->test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelGuru])
            ->assertOk()
            ->assertSee('Nilai-nilai Agama &amp; Budi Pekerti', escape: false)
            ->assertSee('Ajukan Rapor');
    });

    it('menolak pengajuan rapor oleh guru mata pelajaran', function (): void {
        $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapelGuru->getKey()]);
        $indikator = IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);
        $narasi = Narasi::factory()->create(['Rapor_ID' => $this->rapor->getKey(), 'Mapel_ID' => $this->mapelGuru->getKey(), 'Draft_Narasi' => 'Lengkap.']);
        NilaiSiswa::factory()->create(['Narasi_ID' => $narasi->getKey(), 'Indikator_ID' => $indikator->getKey(), 'Nilai' => Nilai::BerkembangSangatBaik]);

        Livewire::actingAs($this->guruMapel)
            ->test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapelGuru])
            ->call('konfirmasiAjukan')
            ->assertSee('Rapor hanya dapat diajukan oleh wali kelas.');

        expect($this->rapor->fresh()->Status)->toBe(RaporStatus::Draft);
    });

    it('tidak menghapus foto saat rapor menunggu persetujuan', function (): void {
        Storage::fake('public');
        $narasi = Narasi::factory()->create(['Rapor_ID' => $this->rapor->getKey(), 'Mapel_ID' => $this->mapelWali->getKey()]);
        $dokumentasi = Dokumentasi::factory()->create(['Narasi_ID' => $narasi->getKey(), 'Foto' => '']);
        $dokumentasi->unggahFoto(UploadedFile::fake()->image('kegiatan.jpg'));
        $this->rapor->forceFill(['Status' => RaporStatus::Menunggu])->save();

        Livewire::actingAs($this->wali)
            ->test('pages::rapor-input', ['rapor' => $this->rapor->fresh(), 'mapel' => $this->mapelWali])
            ->assertDontSeeHtml('aria-label="Hapus foto"')
            ->call('hapusFoto', $dokumentasi->getKey())
            ->assertSee('tidak dapat diubah');

        expect(Dokumentasi::query()->find($dokumentasi->getKey()))->not->toBeNull();
        Storage::disk('public')->assertExists($dokumentasi->fresh()->Foto);
    });
});

describe('layar data fisik', function (): void {
    it('menolak guru dari kelas lain membuka data fisik', function (): void {
        Livewire::actingAs($this->guruKelasLain)
            ->test('pages::rapor-data-fisik', ['rapor' => $this->rapor])
            ->assertForbidden();
    });

    it('menampilkan data fisik tanpa tombol ajukan untuk guru mata pelajaran kelas itu', function (): void {
        Livewire::actingAs($this->guruMapel)
            ->test('pages::rapor-data-fisik', ['rapor' => $this->rapor])
            ->assertOk()
            ->assertSee('hanya dapat diisi oleh wali kelas')
            ->assertDontSee('Ajukan Rapor');
    });
});

describe('unduh rapor', function (): void {
    beforeEach(function (): void {
        $this->rapor->forceFill(['Status' => RaporStatus::Disetujui])->save();
    });

    it('menolak unduhan rapor oleh guru dari kelas lain', function (): void {
        $this->actingAs($this->guruKelasLain)
            ->get(route('rapor.unduh', $this->rapor))
            ->assertForbidden();
    });

    it('mengizinkan unduhan oleh guru mata pelajaran yang mengajar di kelas itu', function (): void {
        $this->actingAs($this->guruMapel)
            ->get(route('rapor.unduh', $this->rapor))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});
