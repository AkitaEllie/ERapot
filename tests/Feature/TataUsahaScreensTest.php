<?php

use App\Enums\JenisKelamin;
use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function periodeAktif(): TahunAjaran
{
    return TahunAjaran::factory()->create([
        'Is_Active' => true,
        'Tahun_Ajaran' => '2025/2026',
        'Semester_Aktif' => Semester::Ganjil,
    ]);
}

function userDenganRole(string $kode): User
{
    return User::factory()->create(['Role_ID' => Role::factory()->bawaan($kode)->create()->getKey()]);
}

describe('data siswa', function (): void {
    beforeEach(function (): void {
        $this->periode = periodeAktif();
        $this->tataUsaha = userDenganRole('TATAUSA');
        $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);
    });

    it('menampilkan siswa beserta kelas saat ini dan status rapor', function (): void {
        $terempat = Siswa::factory()->create(['Nama' => 'Andini Pratiwi', 'NISN' => '0154872301', 'Jenis_Kelamin' => JenisKelamin::Perempuan]);
        $belum = Siswa::factory()->create(['Nama' => 'Elena Puspita']);

        Rapor::factory()->create([
            'Siswa_ID' => $terempat->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Status' => RaporStatus::Menunggu,
        ]);

        Livewire::actingAs($this->tataUsaha);

        Livewire::test('pages::siswa')
            ->assertOk()
            ->assertSee('Andini Pratiwi')
            ->assertSee('0154872301')
            ->assertSee($this->kelas->Nama_Kelas)
            ->assertSee('Belum ditempatkan')
            ->assertSee('Menunggu');
    });

    it('memfilter siswa berdasarkan kelas', function (): void {
        $diKelas = Siswa::factory()->create(['Nama' => 'Siswa Di Kelas']);
        $luarKelas = Siswa::factory()->create(['Nama' => 'Siswa Luar Kelas']);

        Rapor::factory()->create([
            'Siswa_ID' => $diKelas->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
        ]);

        Livewire::actingAs($this->tataUsaha);

        Livewire::test('pages::siswa')
            ->set('filterKelas', $this->kelas->getKey())
            ->assertSee('Siswa Di Kelas')
            ->assertDontSee('Siswa Luar Kelas');
    });
});

describe('penempatan siswa', function (): void {
    beforeEach(function (): void {
        $this->periode = periodeAktif();
        $this->tataUsaha = userDenganRole('TATAUSA');
        $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);
    });

    it('membuat draf rapor kosong saat penempatan disimpan', function (): void {
        $siswa = Siswa::factory()->create(['Nama' => 'Elena Puspita', 'NISN' => '0154872312']);

        Livewire::actingAs($this->tataUsaha);

        Livewire::test('pages::penempatan')
            ->set('kelasTujuan', $this->kelas->getKey())
            ->assertSee('Belum Ditempatkan')
            ->assertSee('Elena Puspita')
            ->call('toggle', $siswa->getKey())
            ->call('pindahkan')
            // staging only: saving is a separate, explicit step
            ->assertSet('pennettakan', [$siswa->getKey() => ['aksi' => 'masuk', 'kelas' => $this->kelas->getKey()]]);

        expect(Rapor::query()->where('Siswa_ID', $siswa->getKey())->exists())->toBeFalse();

        Livewire::test('pages::penempatan')
            ->set('kelasTujuan', $this->kelas->getKey())
            ->call('toggle', $siswa->getKey())
            ->call('pindahkan')
            ->call('simpan');

        $rapor = Rapor::query()->where('Siswa_ID', $siswa->getKey())->firstOrFail();

        expect($rapor->Kelas_ID)->toBe($this->kelas->getKey())
            ->and($rapor->Semester)->toBe(Semester::Ganjil)
            ->and($rapor->Status)->toBe(RaporStatus::BelumDiisi);
    });

    it('menghapus rapor saat siswa dikeluarkan dari kelas', function (): void {
        $siswa = Siswa::factory()->create();
        Rapor::factory()->create([
            'Siswa_ID' => $siswa->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
        ]);

        Livewire::actingAs($this->tataUsaha);

        Livewire::test('pages::penempatan')
            ->set('kelasTujuan', $this->kelas->getKey())
            ->call('keluarkan', $siswa->getKey())
            ->call('simpan');

        expect(Rapor::query()->where('Siswa_ID', $siswa->getKey())->exists())->toBeFalse();
    });

    it('tidak menimpa rapor yang sudah berjalan saat penempatan ulang', function (): void {
        $siswa = Siswa::factory()->create();
        $raporLama = Rapor::factory()->create([
            'Siswa_ID' => $siswa->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Genap,
            'Status' => RaporStatus::Disetujui,
        ]);

        Livewire::actingAs($this->tataUsaha);

        Livewire::test('pages::penempatan')
            ->set('kelasTujuan', $this->kelas->getKey())
            ->call('toggle', $siswa->getKey())
            ->call('pindahkan')
            ->call('simpan');

        $baru = Rapor::query()->where('Siswa_ID', $siswa->getKey())->where('Semester', Semester::Ganjil)->firstOrFail();

        expect($raporLama->fresh()->Status)->toBe(RaporStatus::Disetujui)
            ->and($baru->Status)->toBe(RaporStatus::BelumDiisi);
    });
});

describe('review dan persetujuan rapor', function (): void {
    beforeEach(function (): void {
        $this->periode = periodeAktif();
        $this->kepsekol = userDenganRole('KEPSEKOL');
        $this->guru = User::factory()->create(['Nama' => 'Ibu Sari']);
        $this->kelas = Kelas::factory()->create([
            'TahunAjaran_ID' => $this->periode->getKey(),
            'User_ID' => $this->guru->getKey(),
        ]);
    });

    it('menampilkan rapor yang menunggu persetujuan', function (): void {
        $rapor = Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create(['Nama' => 'Andini Pratiwi'])->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Status' => RaporStatus::Menunggu,
        ]);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::review-rapor')
            ->assertOk()
            ->assertSee('Menunggu Persetujuan (1)')
            ->assertSee('Andini Pratiwi')
            ->call('pilih', $rapor->getKey())
            ->assertSee('Diajukan oleh Ibu Sari');
    });

    it('menyetujui rapor yang dipilih', function (): void {
        $rapor = Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create()->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Status' => RaporStatus::Menunggu,
        ]);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::review-rapor')
            ->call('pilih', $rapor->getKey())
            ->call('setujui');

        expect($rapor->fresh()->Status)->toBe(RaporStatus::Disetujui)
            ->and($rapor->fresh()->Disetujui_Oleh)->toBe($this->kepsekol->getKey());
    });

    it('menolak mengirim revisi tanpa catatan', function (): void {
        $rapor = Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create()->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Status' => RaporStatus::Menunggu,
        ]);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::review-rapor')
            ->call('pilih', $rapor->getKey())
            ->call('kirimRevisi');

        expect($rapor->fresh()->Status)->toBe(RaporStatus::Menunggu);
    });

    it('mengembalikan rapor ke guru dengan catatan revisi', function (): void {
        $rapor = Rapor::factory()->create([
            'Siswa_ID' => Siswa::factory()->create()->getKey(),
            'Kelas_ID' => $this->kelas->getKey(),
            'Semester' => Semester::Ganjil,
            'Status' => RaporStatus::Menunggu,
        ]);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::review-rapor')
            ->call('pilih', $rapor->getKey())
            ->set('catatanRevisi', 'narasi Jati Diri perlu diperbaiki tata bahasanya')
            ->call('kirimRevisi');

        expect($rapor->fresh()->Status)->toBe(RaporStatus::PerluRevisi)
            ->and($rapor->fresh()->Catatan_Revisi)->toBe('narasi Jati Diri perlu diperbaiki tata bahasanya');
    });
});
