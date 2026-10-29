<?php

use App\Enums\JenisMataPelajaran;
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
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
 * Screens from the supplementary wireframe for teachers (G-01 to G-04), the
 * principal (K-01, K-02) and signing out (U-01, U-02).
 */

beforeEach(function (): void {
    foreach (['GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    $this->periode = TahunAjaran::factory()->create(['Tahun_Ajaran' => '2025/2026', 'Semester_Aktif' => Semester::Ganjil, 'Is_Active' => true]);
    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI', 'Nama' => 'Ibu Sari']);
    $this->guruMapel = User::factory()->create(['Role_ID' => 'GURU', 'Nama' => 'Ms. Grace']);
    $this->kepsek = User::factory()->create(['Role_ID' => 'KEPSEKOL', 'Nama' => 'Ibu Mei']);
    $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'User_ID' => $this->wali->getKey(), 'Nama_Kelas' => 'TK-A Respect']);

    $this->mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Jati Diri', 'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler, 'Tampilkan_Indikator' => true]);
    Pembelajaran::factory()->create(['Kelas_ID' => $this->kelas->getKey(), 'Mapel_ID' => $this->mapel->getKey(), 'User_ID' => $this->wali->getKey()]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);
    $this->indikator = IndikatorCapaian::factory()->create(['Program_ID' => $program->getKey()]);

    $this->siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi']);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => $this->siswa->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Semester' => Semester::Ganjil,
        'Status' => RaporStatus::BelumDiisi,
        'Tinggi_Badan' => null,
        'Berat_Badan' => null,
        'Lingkar_Kepala' => null,
    ]);
});

/** Fill in everything the submit check asks for. */
function lengkapiRapor(Rapor $rapor, MataPelajaran $mapel, IndikatorCapaian $indikator): void
{
    $narasi = Narasi::factory()->create(['Rapor_ID' => $rapor->getKey(), 'Mapel_ID' => $mapel->getKey(), 'Draft_Narasi' => 'Andini percaya diri.']);
    NilaiSiswa::factory()->create(['Narasi_ID' => $narasi->getKey(), 'Indikator_ID' => $indikator->getKey(), 'Nilai' => Nilai::BerkembangSangatBaik]);
    Dokumentasi::factory()->create(['Narasi_ID' => $narasi->getKey()]);
    $rapor->forceFill(['Tinggi_Badan' => 108, 'Berat_Badan' => 18, 'Lingkar_Kepala' => 49])->save();
}

describe('data fisik dan kehadiran', function (): void {
    it('menyimpan data fisik oleh wali kelas dan menandai rapor sedang diisi', function (): void {
        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-data-fisik', ['rapor' => $this->rapor])
            ->assertSee('Pertumbuhan Fisik')
            ->set('tinggi', '108,5')
            ->set('berat', '18')
            ->set('lingkar', '49')
            ->set('sakit', '2')
            ->set('izin', '1')
            ->set('tanpaKeterangan', '0')
            ->call('simpan')
            ->assertHasNoErrors();

        $rapor = $this->rapor->fresh();

        expect((float) $rapor->Tinggi_Badan)->toBe(108.5)
            ->and($rapor->Sakit)->toBe(2)
            ->and($rapor->Status)->toBe(RaporStatus::Draft);
    });

    it('menolak nilai di luar batas wajar', function (): void {
        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-data-fisik', ['rapor' => $this->rapor])
            ->set('tinggi', '108')
            ->set('berat', '180')
            ->set('lingkar', '49')
            ->call('simpan')
            ->assertHasErrors(['berat' => 'between'])
            ->assertSee('Nilai di luar batas wajar');
    });

    it('hanya bisa diisi wali kelas', function (): void {
        Livewire::actingAs($this->guruMapel);

        Livewire::test('pages::rapor-data-fisik', ['rapor' => $this->rapor])
            ->assertSee('hanya dapat diisi oleh wali kelas')
            ->set('tinggi', '108')
            ->call('simpan');

        expect($this->rapor->fresh()->Tinggi_Badan)->toBeNull();
    });
});

describe('mengajukan rapor', function (): void {
    it('menampilkan daftar bagian yang belum lengkap', function (): void {
        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel])
            ->set("nilai.{$this->indikator->getKey()}", Nilai::BerkembangSesuaiHarapan->value)
            ->call('ajukan')
            ->assertSet('modalAjukan', 'kurang')
            ->assertSee('Rapor belum dapat diajukan')
            ->assertSee('Narasi belum dibuat')
            ->assertSee('Foto dokumentasi belum diunggah')
            ->assertSee('Data Fisik &amp; Kehadiran', escape: false);

        expect($this->rapor->fresh()->Status)->toBe(RaporStatus::Draft);
    });

    it('mengajukan rapor yang lengkap setelah konfirmasi', function (): void {
        lengkapiRapor($this->rapor, $this->mapel, $this->indikator);

        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel])
            ->call('ajukan')
            ->assertSet('modalAjukan', 'konfirmasi')
            ->call('konfirmasiAjukan')
            ->assertSet('modalAjukan', null);

        expect($this->rapor->fresh()->Status)->toBe(RaporStatus::Menunggu);
    });

    it('menampilkan catatan revisi dan mengizinkan pengajuan ulang', function (): void {
        lengkapiRapor($this->rapor, $this->mapel, $this->indikator);
        $this->rapor->forceFill(['Status' => RaporStatus::PerluRevisi, 'Catatan_Revisi' => 'Narasi Jati Diri perlu diperbaiki tata bahasanya.'])->save();

        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor->fresh(), 'mapel' => $this->mapel])
            ->assertSee('Catatan revisi dari Kepala Sekolah')
            ->assertSee('Narasi Jati Diri perlu diperbaiki tata bahasanya.')
            ->assertSee('Ajukan Ulang')
            ->call('ajukan')
            ->call('konfirmasiAjukan');

        expect($this->rapor->fresh()->Status)->toBe(RaporStatus::Menunggu)
            ->and($this->rapor->fresh()->Catatan_Revisi)->toBeNull();
    });

    it('mengunci rapor yang sedang menunggu persetujuan', function (): void {
        $this->rapor->forceFill(['Status' => RaporStatus::Menunggu])->save();

        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor->fresh(), 'mapel' => $this->mapel])
            ->assertSee('tidak dapat diubah')
            ->set('catatan', 'Diubah diam-diam')
            ->call('simpanDraft');

        expect(Narasi::query()->where('Rapor_ID', $this->rapor->getKey())->value('Catatan_Guru'))->toBeNull();
    });

    it('menyimpan narasi yang disunting guru', function (): void {
        Livewire::actingAs($this->wali);

        Livewire::test('pages::rapor-input', ['rapor' => $this->rapor, 'mapel' => $this->mapel])
            ->set('draftNarasi', 'Ananda Andini menunjukkan rasa percaya diri.')
            ->call('simpanNarasi')
            ->assertHasNoErrors()
            ->assertSee('Narasi berhasil disimpan');

        $narasi = Narasi::query()->where('Rapor_ID', $this->rapor->getKey())->first();

        expect($narasi->Draft_Narasi)->toBe('Ananda Andini menunjukkan rasa percaya diri.')
            ->and($narasi->Status_Narasi->value)->toBe('diedit');
    });
});

describe('daftar rapor dan export', function (): void {
    it('mengaktifkan Export PDF hanya untuk rapor yang disetujui', function (): void {
        $disetujui = Rapor::factory()->disetujui($this->kepsek)->create([
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Bima Arya'])->getKey(),
        ]);

        $this->actingAs($this->wali)
            ->get(route('ekspor-rapor'))
            ->assertOk()
            ->assertSee('Andini Pratiwi')
            ->assertSee('Bima Arya')
            ->assertSee(route('rapor.unduh', $disetujui), escape: false)
            ->assertDontSee(route('rapor.unduh', $this->rapor), escape: false);
    });
});

describe('kepala sekolah', function (): void {
    it('menampilkan riwayat keputusan persetujuan beserta catatan revisi', function (): void {
        Rapor::factory()->disetujui($this->kepsek)->create(['Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Ganjil, 'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Bima Arya'])->getKey()]);
        Rapor::factory()->perluRevisi('Foto dokumentasi belum sesuai.')->create(['Kelas_ID' => $this->kelas->getKey(), 'Semester' => Semester::Ganjil, 'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Erika Salim'])->getKey()]);

        Livewire::actingAs($this->kepsek);

        Livewire::test('pages::persetujuan-rapor')
            ->assertSee('Bima Arya')
            ->assertSee('Erika Salim')
            ->assertSee('Foto dokumentasi belum sesuai.')
            ->assertDontSee('Andini Pratiwi')
            ->call('pilihTab', 'disetujui')
            ->assertSee('Bima Arya')
            ->assertDontSee('Erika Salim');
    });

    it('membuka persetujuan dan pratinjau rapor untuk kepala sekolah saja', function (): void {
        $this->actingAs($this->kepsek)->get(route('persetujuan-rapor'))->assertOk();
        $this->actingAs($this->kepsek)->get(route('rapor.pratinjau', $this->rapor))->assertOk()->assertDontSee('Export PDF');
        $this->actingAs($this->kepsek)->get(route('dashboard.kepsekol.kelas', $this->kelas))->assertOk()->assertSee('Andini Pratiwi');

        $this->actingAs($this->wali)->get(route('persetujuan-rapor'))->assertForbidden();
        $this->actingAs($this->wali)->get(route('dashboard.kepsekol.kelas', $this->kelas))->assertForbidden();
    });

    it('membuka review langsung pada rapor yang dipilih', function (): void {
        $this->rapor->forceFill(['Status' => RaporStatus::Menunggu])->save();

        $this->actingAs($this->kepsek)
            ->get(route('review-rapor', ['rapor' => $this->rapor->getKey()]))
            ->assertOk()
            ->assertSee('Andini Pratiwi - NISN');
    });
});

it('meminta konfirmasi sebelum logout', function (): void {
    $this->actingAs($this->wali)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Keluar dari akun?')
        ->assertSee('form="form-keluar"', escape: false);
});
