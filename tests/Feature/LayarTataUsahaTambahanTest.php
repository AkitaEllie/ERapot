<?php

use App\Enums\JenisMataPelajaran;
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

/*
 * Screens from the supplementary wireframe, TU-01 to TU-07.
 */

beforeEach(function (): void {
    foreach (['GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    $this->periode = TahunAjaran::factory()->create([
        'Tahun_Ajaran' => '2025/2026',
        'Semester_Aktif' => Semester::Ganjil,
        'Is_Active' => true,
    ]);
    $this->tataUsaha = User::factory()->create(['Role_ID' => 'TATAUSA', 'Nama' => 'Pak Andre']);

    Livewire::actingAs($this->tataUsaha);
});

it('membuka setiap layar Tata Usaha baru tanpa galat', function (string $rute): void {
    $this->actingAs($this->tataUsaha)->get(route($rute))->assertOk()->assertDontSee('Layar ini belum dibuat');
})->with(['tahun-ajaran', 'pengguna', 'kelas', 'mapel', 'penugasan-mengajar', 'siswa.tambah']);

it('menolak peran lain membuka layar Tata Usaha', function (string $rute): void {
    $this->actingAs(User::factory()->create(['Role_ID' => 'GURU']))->get(route($rute))->assertForbidden();
})->with(['tahun-ajaran', 'pengguna', 'kelas', 'mapel', 'penugasan-mengajar', 'siswa.tambah']);

describe('tahun ajaran', function (): void {
    it('menambah tahun ajaran baru tanpa mengubah periode aktif', function (): void {
        Livewire::test('pages::tahun-ajaran')
            ->set('tahunAjaran', '2026/2027')
            ->set('semester', 'ganjil')
            ->set('tanggalMulai', '2026-07-13')
            ->set('tanggalSelesai', '2026-12-18')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('2026/2027');

        $baru = TahunAjaran::query()->where('Tahun_Ajaran', '2026/2027')->first();

        expect($baru->Is_Active)->toBeFalse()
            ->and($this->periode->fresh()->Is_Active)->toBeTrue();
    });

    it('menolak tahun ajaran yang sudah terdaftar dan format yang salah', function (): void {
        Livewire::test('pages::tahun-ajaran')
            ->set('tahunAjaran', '2025/2026')
            ->set('tanggalMulai', '2025-07-01')
            ->set('tanggalSelesai', '2025-06-01')
            ->call('simpan')
            ->assertHasErrors(['tahunAjaran' => 'unique', 'tanggalSelesai' => 'after']);

        Livewire::test('pages::tahun-ajaran')
            ->set('tahunAjaran', '2026/2028')
            ->set('tanggalMulai', '2026-07-01')
            ->set('tanggalSelesai', '2026-12-01')
            ->call('simpan')
            ->assertHasErrors('tahunAjaran');
    });

    it('mengaktifkan satu periode dan menonaktifkan yang lain', function (): void {
        $lain = TahunAjaran::factory()->tidakAktif()->create(['Tahun_Ajaran' => '2026/2027']);

        Livewire::test('pages::tahun-ajaran')->call('aktifkan', $lain->getKey());

        expect($lain->fresh()->Is_Active)->toBeTrue()
            ->and($this->periode->fresh()->Is_Active)->toBeFalse()
            ->and(TahunAjaran::query()->active()->count())->toBe(1);
    });
});

describe('data guru dan pengguna', function (): void {
    it('membuat akun baru dengan kata sandi yang di-hash', function (): void {
        Livewire::test('pages::pengguna')
            ->call('tambah')
            ->assertSee('Tambah Pengguna')
            ->set('nama', 'Ibu Sari Wulandari')
            ->set('email', 'sari.w@tkcktc.sch.id')
            ->set('peran', 'GURU')
            ->set('kataSandi', 'rahasia-kuat')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('formTerbuka', false);

        $user = User::query()->where('Email', 'sari.w@tkcktc.sch.id')->first();

        expect($user)->not->toBeNull()
            ->and($user->Password)->not->toBe('rahasia-kuat')
            ->and(password_verify('rahasia-kuat', $user->Password))->toBeTrue();
    });

    it('menolak email yang sudah terdaftar', function (): void {
        User::factory()->create(['Email' => 'sari@tkcktc.sch.id']);

        Livewire::test('pages::pengguna')
            ->call('tambah')
            ->set('nama', 'Ibu Sari')
            ->set('email', 'sari@tkcktc.sch.id')
            ->set('kataSandi', 'rahasia-kuat')
            ->call('simpan')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('Data sudah terdaftar');
    });

    it('mengubah pengguna tanpa mengganti kata sandi bila dikosongkan', function (): void {
        $guru = User::factory()->create(['Role_ID' => 'GURU', 'Nama' => 'Ibu Ratna']);
        $sandiLama = $guru->Password;

        Livewire::test('pages::pengguna')
            ->call('ubah', $guru->getKey())
            ->assertSet('nama', 'Ibu Ratna')
            ->set('nama', 'Ibu Ratna Dewi')
            ->call('simpan')
            ->assertHasNoErrors();

        expect($guru->fresh()->Nama)->toBe('Ibu Ratna Dewi')
            ->and($guru->fresh()->Password)->toBe($sandiLama);
    });

    it('menonaktifkan pengguna sehingga tidak bisa login, tapi tidak dirinya sendiri', function (): void {
        $guru = User::factory()->create(['Role_ID' => 'GURU', 'Email' => 'nonaktif@tkcktc.sch.id', 'Password' => 'rahasia-kuat']);

        Livewire::test('pages::pengguna')->call('ubahStatus', $guru->getKey());
        expect($guru->fresh()->Is_Active)->toBeFalse();

        Livewire::test('pages::pengguna')->call('ubahStatus', $this->tataUsaha->getKey());
        expect($this->tataUsaha->fresh()->Is_Active)->toBeTrue();

        auth()->logout();

        Livewire::test('pages::login')
            ->set('Email', 'nonaktif@tkcktc.sch.id')
            ->set('Password', 'rahasia-kuat')
            ->call('masuk')
            ->assertHasErrors('Email');
    });
});

describe('data kelas', function (): void {
    it('menambah kelas pada tahun ajaran aktif dan menjadikan gurunya wali kelas', function (): void {
        $guru = User::factory()->create(['Role_ID' => 'GURU', 'Nama' => 'Ibu Lina']);

        Livewire::test('pages::kelas')
            ->set('namaKelas', 'TK-B Compassion')
            ->set('jenjang', 'TK-B')
            ->set('waliKelas', $guru->getKey())
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('TK-B Compassion')
            ->assertSee('Ibu Lina');

        $kelas = Kelas::query()->where('Nama_Kelas', 'TK-B Compassion')->first();

        expect($kelas->TahunAjaran_ID)->toBe($this->periode->getKey())
            ->and($guru->fresh()->Role_ID)->toBe('GURUWALI');
    });

    it('menolak guru yang sudah menjadi wali kelas lain', function (): void {
        $guru = User::factory()->create(['Role_ID' => 'GURUWALI']);
        Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'User_ID' => $guru->getKey()]);

        Livewire::test('pages::kelas')
            ->set('namaKelas', 'KB-A Harmony')
            ->set('waliKelas', $guru->getKey())
            ->call('simpan')
            ->assertHasErrors(['waliKelas' => 'unique']);
    });

    it('menandai kelas tanpa wali dan tidak menghapus kelas yang sudah punya rapor', function (): void {
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'User_ID' => null, 'Nama_Kelas' => 'KB-B Serenity']);
        Rapor::factory()->create(['Kelas_ID' => $kelas->getKey(), 'Semester' => Semester::Ganjil]);

        Livewire::test('pages::kelas')
            ->assertSee('Belum ada wali kelas')
            ->call('hapus', $kelas->getKey());

        expect(Kelas::query()->find($kelas->getKey()))->not->toBeNull();
    });
});

describe('mata pelajaran', function (): void {
    it('menambah, mengubah dan menghapus mata pelajaran yang belum dipakai', function (): void {
        Livewire::test('pages::mapel')
            ->set('nama', 'Bahasa Mandarin')
            ->set('jenis', JenisMataPelajaran::MuatanLokal->value)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('Bahasa Mandarin');

        $mapel = MataPelajaran::query()->where('Nama_Mapel', 'Bahasa Mandarin')->first();

        Livewire::test('pages::mapel')
            ->call('ubah', $mapel->getKey())
            ->set('tampilkanIndikator', false)
            ->call('simpan');

        expect($mapel->fresh()->Tampilkan_Indikator)->toBeFalse();

        Livewire::test('pages::mapel')->call('hapus', $mapel->getKey());

        expect(MataPelajaran::query()->find($mapel->getKey()))->toBeNull();
    });

    it('tidak menghapus mata pelajaran yang sudah ditugaskan', function (): void {
        $mapel = MataPelajaran::factory()->create();
        Pembelajaran::factory()->create(['Mapel_ID' => $mapel->getKey()]);

        Livewire::test('pages::mapel')->call('hapus', $mapel->getKey());

        expect(MataPelajaran::query()->find($mapel->getKey()))->not->toBeNull();
    });
});

describe('penugasan mengajar', function (): void {
    beforeEach(function (): void {
        $this->kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'Nama_Kelas' => 'TK-A Respect']);
        $this->mapel = MataPelajaran::factory()->create(['Nama_Mapel' => 'Bahasa Inggris']);
        $this->guruA = User::factory()->create(['Role_ID' => 'GURU', 'Nama' => 'Ms. Grace']);
        $this->guruB = User::factory()->create(['Role_ID' => 'GURU', 'Nama' => 'Ibu Ratna']);
    });

    it('menugaskan guru pengampu untuk mata pelajaran di sebuah kelas', function (): void {
        Livewire::test('pages::penugasan-mengajar')
            ->assertSee('Belum ditugaskan')
            ->set("guru.{$this->mapel->getKey()}", $this->guruA->getKey())
            ->call('simpan')
            ->assertSee('Ditugaskan');

        expect(Pembelajaran::query()->where('Kelas_ID', $this->kelas->getKey())->value('User_ID'))->toBe($this->guruA->getKey());
    });

    it('meminta konfirmasi sebelum mengganti guru pengampu yang sudah ada', function (): void {
        Pembelajaran::tugaskan($this->kelas, $this->mapel, $this->guruA);

        $layar = Livewire::test('pages::penugasan-mengajar')
            ->set("guru.{$this->mapel->getKey()}", $this->guruB->getKey())
            ->call('simpan')
            ->assertSee('Penugasan sudah terdaftar');

        expect(Pembelajaran::query()->value('User_ID'))->toBe($this->guruA->getKey());

        $layar->call('simpan', true)->assertSet('konflik', []);

        expect(Pembelajaran::query()->value('User_ID'))->toBe($this->guruB->getKey());
    });
});

describe('form siswa', function (): void {
    it('menambah siswa dan kembali ke daftar', function (): void {
        Livewire::test('pages::siswa-form')
            ->set('nisn', '0154872312')
            ->set('nis', '2025.001')
            ->set('nama', 'Elena Puspita')
            ->set('jenisKelamin', 'P')
            ->set('agama', 'Buddha')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('siswa.index'));

        expect(Siswa::query()->where('NISN', '0154872312')->exists())->toBeTrue();
    });

    it('memvalidasi NISN sepuluh digit', function (): void {
        Livewire::test('pages::siswa-form')
            ->set('nisn', '015487231')
            ->call('simpan')
            ->assertHasErrors(['nisn' => 'digits'])
            ->assertSee('NISN harus terdiri dari 10 digit angka');
    });

    it('mengubah siswa yang ada dan menampilkan kelasnya saat ini', function (): void {
        $siswa = Siswa::factory()->create(['Nama' => 'Andini Pratiwi']);
        $kelas = Kelas::factory()->create(['TahunAjaran_ID' => $this->periode->getKey(), 'Nama_Kelas' => 'TK-A Respect']);
        Rapor::factory()->create(['Siswa_ID' => $siswa->getKey(), 'Kelas_ID' => $kelas->getKey(), 'Semester' => Semester::Ganjil, 'Status' => RaporStatus::Draft]);

        Livewire::test('pages::siswa-form', ['siswa' => $siswa])
            ->assertSet('nama', 'Andini Pratiwi')
            ->assertSee('TK-A Respect')
            ->set('nama', 'Andini Pratiwi Putri')
            ->call('simpan')
            ->assertHasNoErrors();

        expect($siswa->fresh()->Nama)->toBe('Andini Pratiwi Putri');
    });
});
