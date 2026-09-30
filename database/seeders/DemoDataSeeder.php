<?php

namespace Database\Seeders;

use App\Enums\JenisKelamin;
use App\Enums\Jenjang;
use App\Enums\Nilai;
use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Enums\StatusNarasi;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Populates a realistic slice of data so every screen has something to show.
 *
 * Development only: never run in production.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $periode = TahunAjaran::factory()->create([
            'Tahun_Ajaran' => '2025/2026',
            'Semester_Aktif' => Semester::Ganjil,
            'Tanggal_Mulai' => '2025-07-15',
            'Tanggal_Selesai' => '2025-12-19',
            'Is_Active' => true,
        ]);

        $guru = User::factory()->create([
            'Role_ID' => 'GURU',
            'Nama' => 'Ibu Sari',
            'Email' => 'sari@tkcktc.sch.id',
            'Password' => Hash::make('rahasia-kuat'),
        ]);
        $guruLain = User::factory()->create([
            'Role_ID' => 'GURU',
            'Nama' => 'Ibu Ratna',
            'Email' => 'ratna@tkcktc.sch.id',
        ]);
        User::factory()->count(9)->create(['Role_ID' => 'GURU']);
        User::factory()->create(['Role_ID' => 'TATAUSA', 'Nama' => 'Pak Andre', 'Email' => 'andre@tkcktc.sch.id', 'Password' => Hash::make('rahasia-kuat')]);
        User::factory()->create(['Role_ID' => 'KEPSEKOL', 'Nama' => 'Ibu Mei', 'Email' => 'mei@tkcktc.sch.id', 'Password' => Hash::make('rahasia-kuat')]);

        $mapelAgama = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Nilai-nilai Agama & Budi Pekerti',
            'Jenis_Mapel' => 'intrakurikuler',
            'Tampilkan_Indikator' => true,
        ]);
        $mapelJati = MataPelajaran::factory()->create(['Nama_Mapel' => 'Jati Diri', 'Tampilkan_Indikator' => true]);
        $mapelLiterasi = MataPelajaran::factory()->create(['Nama_Mapel' => 'Literasi & STEAM', 'Tampilkan_Indikator' => true]);
        $mapelInggris = MataPelajaran::factory()->create(['Nama_Mapel' => 'Bahasa Inggris', 'Tampilkan_Indikator' => true]);
        // Kokurikuler and Data Fisik complete the six subject tabs shown on the report input screen.
        $mapelKokurikuler = MataPelajaran::factory()->create(['Nama_Mapel' => 'Kokurikuler', 'Tampilkan_Indikator' => true]);
        $mapelFisik = MataPelajaran::factory()->create(['Nama_Mapel' => 'Data Fisik & Kehadiran', 'Tampilkan_Indikator' => true]);

        // Named explicitly because the factory pool can collide with the subjects above.
        foreach (['Matematika', 'Bahasa Indonesia', 'Pendidikan Pancasila'] as $nama) {
            MataPelajaran::factory()->create(['Nama_Mapel' => $nama, 'Tampilkan_Indikator' => true]);
        }

        $kelasRespect = Kelas::factory()->create([
            'TahunAjaran_ID' => $periode->getKey(),
            'User_ID' => $guru->getKey(),
            'Nama_Kelas' => 'TK-A Respect',
            'Jenjang' => Jenjang::Tka,
        ]);
        $kelasSincerity = Kelas::factory()->create([
            'TahunAjaran_ID' => $periode->getKey(),
            'User_ID' => $guruLain->getKey(),
            'Nama_Kelas' => 'TK-A Sincerity',
            'Jenjang' => Jenjang::Tka,
        ]);
        foreach ([['TK-B Gratitude', Jenjang::Tkb], ['TK-B Compassion', Jenjang::Tkb], ['KB Harmony', Jenjang::Kba], ['KB Serenity', Jenjang::Kbb]] as [$nama, $jenjang]) {
            Kelas::factory()->create([
                'TahunAjaran_ID' => $periode->getKey(),
                'Nama_Kelas' => $nama,
                'Jenjang' => $jenjang,
            ]);
        }

        foreach ([$kelasRespect, $kelasSincerity] as $kelas) {
            foreach ([$mapelAgama, $mapelJati, $mapelLiterasi, $mapelInggris, $mapelKokurikuler, $mapelFisik] as $mapel) {
                Pembelajaran::factory()->create([
                    'Kelas_ID' => $kelas->getKey(),
                    'Mapel_ID' => $mapel->getKey(),
                    'User_ID' => $kelas->User_ID,
                ]);
            }
        }
        // one unassigned teaching slot so the "belum ada guru pengampu" task appears
        $kelasKosong = Kelas::factory()->create([
            'TahunAjaran_ID' => $periode->getKey(),
            'Nama_Kelas' => 'TK-C Karakter',
            'Jenjang' => Jenjang::Tkb,
        ]);
        Pembelajaran::factory()->create([
            'Kelas_ID' => $kelasKosong->getKey(),
            'Mapel_ID' => $mapelInggris->getKey(),
            'User_ID' => null,
        ]);

        $program = ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelAgama->getKey(),
            'Nama_Program' => 'Program 1 - Mengenal nilai agama dan budi pekerti',
        ]);
        $deskripsi = [
            'Mempraktikkan doa sebelum dan sesudah kegiatan',
            'Menunjukkan sikap menghargai teman yang berbeda keyakinan',
            'Terbiasa mengucapkan terima kasih, maaf, dan tolong',
            'Menjaga kebersihan diri dan lingkungan sekolah',
            'Mengenal hari besar keagamaan yang dirayakan di sekolah',
        ];
        $indikator = collect($deskripsi)->map(fn (string $d) => IndikatorCapaian::factory()->create([
            'Program_ID' => $program->getKey(),
            'TahunAjaran_ID' => $periode->getKey(),
            'Semester' => Semester::Ganjil,
            'Tipe' => TipeIndikator::Capaian,
            'Deskripsi' => $d,
        ]));

        $narasiTeks = [
            'Nilai-nilai Agama & Budi Pekerti' => 'Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan. Ia juga mulai menunjukkan sikap menghargai teman yang berbeda keyakinan saat kegiatan bersama di kelas.',
            'Jati Diri' => 'Andini semakin percaya diri menyampaikan pendapat di depan kelas dan mampu menyelesaikan tugas kelompok bersama temannya.',
            'Literasi & STEAM' => 'Andini mengenali huruf awal namanya dan senang bereksperimen dengan bahan sederhana di kelas.',
        ];

        $siswaRespect = collect([
            ['Andini Pratiwi', '0154872301', JenisKelamin::Perempuan],
            ['Bima Arya Saputra', '0154872302', JenisKelamin::LakiLaki],
            ['Clarissa Wijaya', '0154872303', JenisKelamin::Perempuan],
            ['Dimas Nugroho', '0154872304', JenisKelamin::LakiLaki],
            ['Erika Salim', '0154872305', JenisKelamin::Perempuan],
            ['Fajar Alvaro', '0154872306', JenisKelamin::LakiLaki],
            ['Gabriela Tanu', '0154872307', JenisKelamin::Perempuan],
        ])->map(fn (array $s) => Siswa::factory()->create([
            'Nama' => $s[0],
            'NISN' => $s[1],
            'Jenis_Kelamin' => $s[2],
        ]));

        // unplaced students, shown in the placement screen and Data Siswa
        collect([
            ['Elena Puspita', '0154872312', JenisKelamin::Perempuan],
            ['Hana Lestari', '0154872315', JenisKelamin::Perempuan],
            ['Ivan Pratama', '0154872316', JenisKelamin::LakiLaki],
            ['Jessica Tanoto', '0154872317', JenisKelamin::Perempuan],
            ['Kevin Wijaya', '0154872318', JenisKelamin::LakiLaki],
            ['Lina Marlina', '0154872319', JenisKelamin::Perempuan],
        ])->each(fn (array $s) => Siswa::factory()->create([
            'Nama' => $s[0],
            'NISN' => $s[1],
            'Jenis_Kelamin' => $s[2],
        ]));

        $pola = [
            RaporStatus::Disetujui, RaporStatus::Disetujui, RaporStatus::Menunggu,
            RaporStatus::Draft, RaporStatus::PerluRevisi, RaporStatus::BelumDiisi,
            RaporStatus::Draft,
        ];

        foreach ($siswaRespect->values() as $index => $siswa) {
            $rapor = Rapor::factory()->create([
                'Siswa_ID' => $siswa->getKey(),
                'Kelas_ID' => $kelasRespect->getKey(),
                'Semester' => Semester::Ganjil,
                'Status' => $pola[$index],
            ]);

            if ($pola[$index] === RaporStatus::BelumDiisi) {
                continue;
            }

            $lengkap = ! in_array($pola[$index], [RaporStatus::Draft, RaporStatus::PerluRevisi], true);

            foreach ($narasiTeks as $namaMapel => $teks) {
                $mapel = match ($namaMapel) {
                    'Jati Diri' => $mapelJati,
                    'Literasi & STEAM' => $mapelLiterasi,
                    default => $mapelAgama,
                };

                $narasi = Narasi::factory()->create([
                    'Rapor_ID' => $rapor->getKey(),
                    'Mapel_ID' => $mapel->getKey(),
                    'Catatan_Guru' => 'Anak mulai berani speak up di depan kelas.',
                    'Draft_Narasi' => $teks,
                    'Narasi_Final' => $pola[$index] === RaporStatus::Disetujui ? $teks : null,
                    'Status_Narasi' => $pola[$index] === RaporStatus::Disetujui ? StatusNarasi::Final : StatusNarasi::Draft,
                ]);

                if ($mapel->Mapel_ID === $mapelAgama->Mapel_ID) {
                    $sampled = $lengkap ? $indikator : $indikator->take(3);

                    foreach ($sampled as $i => $ind) {
                        NilaiSiswa::factory()->create([
                            'Narasi_ID' => $narasi->getKey(),
                            'Indikator_ID' => $ind->getKey(),
                            'Nilai' => $i % 3 === 0 ? Nilai::BerkembangSangatBaik : ($i % 3 === 1 ? Nilai::BerkembangSesuaiHarapan : Nilai::MulaiBerkembang),
                            'Dipilih_Untuk_Narasi' => $i < 2,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Demo data siap. Akun: sari@tkcktc.sch.id (Guru), andre@tkcktc.sch.id (Tata Usaha), mei@tkcktc.sch.id (Kepala Sekolah) - password: rahasia-kuat');
    }
}
