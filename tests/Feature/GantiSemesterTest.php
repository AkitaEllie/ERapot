<?php

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

beforeEach(function (): void {
    Role::factory()->bawaan('TATAUSA')->create();
    $this->tataUsaha = User::factory()->create(['Role_ID' => 'TATAUSA']);
    $this->periode = TahunAjaran::factory()->create([
        'Tahun_Ajaran' => '2025/2026',
        'Semester_Aktif' => Semester::Ganjil,
        'Is_Active' => true,
    ]);
    $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);

    $this->actingAs($this->tataUsaha);
});

/**
 * Count a semester's reports, mirroring how the dashboard measures the drafts
 * gantiSemester() creates. The method itself is void per the class diagram.
 */
function hitungRapor(Semester $semester): int
{
    return Rapor::query()->where('Semester', $semester)->count();
}

/** Put a student into a class for the running semester with the given status. */
function daftarkan(Siswa $siswa, Kelas $kelas, RaporStatus $status, Semester $semester = Semester::Ganjil): Rapor
{
    return Rapor::factory()->create([
        'Siswa_ID' => $siswa->getKey(),
        'Kelas_ID' => $kelas->getKey(),
        'Semester' => $semester,
        'Status' => $status,
    ]);
}

it('berpindah dari ganjil ke genap', function (): void {
    $this->periode->gantiSemester();

    expect($this->periode->fresh()->Semester_Aktif)->toBe(Semester::Genap);
});

it('kembali ke ganjil saat dipanggil lagi', function (): void {
    $this->periode->gantiSemester();
    $this->periode->gantiSemester();

    expect($this->periode->fresh()->Semester_Aktif)->toBe(Semester::Ganjil);
});

it('membuat draf kosong untuk setiap siswa yang sudah ditempatkan', function (): void {
    $terempatkan = collect(range(1, 3))->map(fn () => daftarkan(
        Siswa::factory()->create(),
        $this->kelas,
        RaporStatus::Disetujui,
    ));

    $sebelum = hitungRapor(Semester::Genap);
    $this->periode->gantiSemester();
    $dibuat = hitungRapor(Semester::Genap) - $sebelum;

    expect($dibuat)->toBe(3);

    foreach ($terempatkan as $raporLama) {
        $baru = Rapor::query()
            ->where('Siswa_ID', $raporLama->Siswa_ID)
            ->where('Semester', Semester::Genap)
            ->first();

        expect($baru)->not->toBeNull()
            ->and($baru->Kelas_ID)->toBe($this->kelas->getKey())
            ->and($baru->Status)->toBe(RaporStatus::BelumDiisi)
            ->and($baru->Disetujui_Oleh)->toBeNull()
            ->and($baru->Tanggal_Persetujuan)->toBeNull();
    }
});

it('tidak membuat draf untuk siswa yang belum ditempatkan', function (): void {
    daftarkan(Siswa::factory()->create(), $this->kelas, RaporStatus::Draft);
    Siswa::factory()->count(2)->create(); // never placed

    $sebelum = hitungRapor(Semester::Genap);
    $this->periode->gantiSemester();

    expect(hitungRapor(Semester::Genap) - $sebelum)->toBe(1);
});

it('menjaga siswa pada kelasnya saat berpindah semester', function (): void {
    $kelasLama = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey()]);
    $siswa = Siswa::factory()->create();

    daftarkan($siswa, $kelasLama, RaporStatus::BelumDiisi, Semester::Genap);
    daftarkan($siswa, $this->kelas, RaporStatus::Disetujui, Semester::Ganjil);

    $this->periode->gantiSemester();

    $baru = Rapor::query()
        ->where('Siswa_ID', $siswa->getKey())
        ->where('Semester', Semester::Genap)
        ->first();

    // The newest report decides the placement, which is the active class.
    expect($baru->Kelas_ID)->toBe($this->kelas->getKey());
});

it('tidak menggandakan draf yang sudah ada', function (): void {
    $siswa = Siswa::factory()->create();
    daftarkan($siswa, $this->kelas, RaporStatus::Disetujui);

    $sebelum = hitungRapor(Semester::Genap);
    $this->periode->gantiSemester();

    expect(hitungRapor(Semester::Genap) - $sebelum)->toBe(1);

    // Switching back must not duplicate the ganjil report either.
    $this->periode->gantiSemester();

    expect(Rapor::query()->where('Siswa_ID', $siswa->getKey())->count())->toBe(2);
});

it('melaporkan jumlah draf dari layar dashboard tata usaha', function (): void {
    daftarkan(Siswa::factory()->create(), $this->kelas, RaporStatus::Draft);
    daftarkan(Siswa::factory()->create(), $this->kelas, RaporStatus::Disetujui);
    Siswa::factory()->create();

    Livewire::test('pages::dashboard-tata-usaha')
        ->call('gantiSemester')
        ->assertSee('Semester diganti ke Genap')
        ->assertSee('2 draf rapor dibuat');

    expect($this->periode->fresh()->Semester_Aktif)->toBe(Semester::Genap);
});

it('menampilkan tombol sesuai periode aktif setelah pergantian', function (): void {
    Livewire::test('pages::dashboard-tata-usaha')
        ->assertSee('Ganti ke Semester Genap')
        ->call('gantiSemester')
        ->assertSee('Ganti ke Semester Ganjil');
});
