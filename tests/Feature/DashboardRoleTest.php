<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buatRole(string $code): Role
{
    return Role::factory()->bawaan($code)->create();
}

function raporStatus(Kelas $kelas, RaporStatus $status): Rapor
{
    return Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => $kelas->getKey(),
        'Status' => $status,
        // The factory randomises the semester; dashboards scope by the active one.
        'Semester' => Semester::Ganjil,
    ]);
}

describe('dashboard tata usaha', function (): void {
    beforeEach(function (): void {
        $this->periode = TahunAjaran::factory()->create([
            'Is_Active' => true,
            'Tahun_Ajaran' => '2025/2026',
            'Semester_Aktif' => Semester::Ganjil,
        ]);
    });

    it('menampilkan ringkasan dan periode aktif', function (): void {
        $tataUsaha = User::factory()->create(['Role_ID' => buatRole('TATAUSA')->getKey()]);
        Kelas::factory()->count(6)->create(['TahunAjaran_ID' => $this->periode->getKey()]);
        MataPelajaran::factory()->count(9)->create();

        Livewire::actingAs($tataUsaha);

        Livewire::test('pages::dashboard-tata-usaha')
            ->assertOk()
            ->assertSee('Periode Aktif')
            ->assertSee('2025/2026')
            ->assertSee('Ganti ke Semester Genap')
            ->assertSee('Kelas');
    });

    it('menghitung siswa yang belum ditempatkan ke kelas', function (): void {
        $tataUsaha = User::factory()->create(['Role_ID' => buatRole('TATAUSA')->getKey()]);
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);

        raporStatus($kelas, RaporStatus::BelumDiisi);
        raporStatus($kelas, RaporStatus::Draft);
        raporStatus($kelas, RaporStatus::Disetujui);

        // these two have no rapor at all, so they are unplaced
        Siswa::factory()->count(2)->create();

        Livewire::actingAs($tataUsaha);

        Livewire::test('pages::dashboard-tata-usaha')
            ->assertSee('2 siswa belum ditempatkan ke kelas');
    });

    it('menghitung kelas tanpa wali kelas', function (): void {
        $tataUsaha = User::factory()->create(['Role_ID' => buatRole('TATAUSA')->getKey()]);
        Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'User_ID' => User::factory()->create()->getKey()]);
        Kelas::factory()->count(2)->create(['TahunAjaran_ID' => $this->periode->getKey(), 'User_ID' => null]);

        Livewire::actingAs($tataUsaha);

        Livewire::test('pages::dashboard-tata-usaha')
            ->assertSee('2 kelas belum punya wali kelas');
    });

    it('menghitung penugasan tanpa guru pengampu', function (): void {
        $tataUsaha = User::factory()->create(['Role_ID' => buatRole('TATAUSA')->getKey()]);
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);

        Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'User_ID' => User::factory()->create()->getKey()]);
        Pembelajaran::factory()->create(['Kelas_ID' => $kelas->getKey(), 'User_ID' => null]);

        Livewire::actingAs($tataUsaha);

        Livewire::test('pages::dashboard-tata-usaha')
            ->assertSee('1 mapel belum punya guru pengampu');
    });
});

describe('dashboard kepala sekolah', function (): void {
    beforeEach(function (): void {
        $this->periode = TahunAjaran::factory()->create([
            'Is_Active' => true,
            'Tahun_Ajaran' => '2025/2026',
            'Semester_Aktif' => Semester::Ganjil,
        ]);
        $this->kepsekol = User::factory()->create(['Role_ID' => buatRole('KEPSEKOL')->getKey()]);
    });

    it('meringkas status rapor per kategori', function (): void {
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);
        raporStatus($kelas, RaporStatus::Disetujui);
        raporStatus($kelas, RaporStatus::Menunggu);
        raporStatus($kelas, RaporStatus::PerluRevisi);
        raporStatus($kelas, RaporStatus::BelumDiisi);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::dashboard-kepsekol')
            ->assertOk()
            ->assertSee('Rapor selesai')
            ->assertSee('1 / 4')
            ->assertSee('Progres per Kelas')
            ->assertSee('Lihat Detail');
    });

    it('menampilkan progres kelas hanya dari rapor yang disetujui', function (): void {
        $kelas = Kelas::factory()->create([
            'Nama_Kelas' => 'TK-A Respect',
            'User_ID' => User::factory()->create(['Nama' => 'Ibu Sari'])->getKey(),
        ]);

        raporStatus($kelas, RaporStatus::Disetujui);
        raporStatus($kelas, RaporStatus::Disetujui);
        raporStatus($kelas, RaporStatus::Draft);
        raporStatus($kelas, RaporStatus::Menunggu);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::dashboard-kepsekol')
            ->assertSee('TK-A Respect')
            ->assertSee('Wali kelas: Ibu Sari')
            ->assertSee('2 / 4 rapor selesai');
    });

    it('membuka rincian rapor tiap siswa saat kartu kelas diklik', function (): void {
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);
        $rapor = raporStatus($kelas, RaporStatus::Menunggu);
        $rapor->siswa->update(['Nama' => 'Andini Pratiwi']);

        Livewire::actingAs($this->kepsekol);

        Livewire::test('pages::dashboard-kepsekol')
            ->assertDontSee('Andini Pratiwi')
            ->call('lihatDetail', $kelas->getKey())
            ->assertSee('Andini Pratiwi')
            ->assertSee('Menunggu');
    });
});
