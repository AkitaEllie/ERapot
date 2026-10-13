<?php

namespace Database\Seeders;

use App\Enums\JenisKelamin;
use App\Enums\JenisMataPelajaran;
use App\Enums\Jenjang;
use App\Enums\Nilai;
use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Enums\StatusNarasi;
use App\Enums\TipeIndikator;
use App\Models\Dokumentasi;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

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

        // Ibu Sari is the homeroom teacher of TK-A Respect: she owns the whole book.
        $guru = User::factory()->create([
            'Role_ID' => 'GURUWALI',
            'Nama' => 'Ibu Sari',
            'Email' => 'sari@tkcktc.sch.id',
            'Password' => Hash::make('rahasia-kuat'),
        ]);
        // Ibu Ratna teaches subjects across classes, so she is a subject teacher.
        $guruLain = User::factory()->create([
            'Role_ID' => 'GURU',
            'Nama' => 'Ibu Ratna',
            'Email' => 'ratna@tkcktc.sch.id',
            'Password' => Hash::make('rahasia-kuat'),
        ]);
        User::factory()->count(9)->create(['Role_ID' => 'GURU']);

        // A second homeroom teacher, so more than one class has an owner.
        $guruWaliLain = User::factory()->create([
            'Role_ID' => 'GURUWALI',
            'Nama' => 'Ibu Dewi',
            'Email' => 'dewi@tkcktc.sch.id',
            'Password' => Hash::make('rahasia-kuat'),
        ]);
        User::factory()->create(['Role_ID' => 'TATAUSA', 'Nama' => 'Pak Andre', 'Email' => 'andre@tkcktc.sch.id', 'Password' => Hash::make('rahasia-kuat')]);
        User::factory()->create(['Role_ID' => 'KEPSEKOL', 'Nama' => 'Ibu Mei', 'Email' => 'mei@tkcktc.sch.id', 'Password' => Hash::make('rahasia-kuat')]);

        // Jenis_Mapel decides the report's section and page template, so these
        // values follow the reference document (docs/contoh-rapor.pdf):
        //   intrakurikuler -> Section A, prose + 3 photos
        //   kokurikuler    -> Section B, project narrative + numbered dimensions
        $mapelAgama = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Nilai-nilai Agama & Budi Pekerti',
            'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelJati = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Jati Diri',
            'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelLiterasi = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Dasar-dasar Literasi, Numerasi, Sains, Teknologi, Rekayasa, dan Seni',
            'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler,
            'Tampilkan_Indikator' => true,
        ]);

        // Section B: the kokurikuler project.
        $mapelKokurikuler = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Proyek Penguatan Profil Pelajar Pancasila',
            'Jenis_Mapel' => JenisMataPelajaran::KoKurikuler,
            'Tampilkan_Indikator' => true,
        ]);

        // Subjects the reference prints with the indicator-table template.
        $mapelInggris = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Bahasa Inggris',
            'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelMandarin = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Bahasa Mandarin',
            'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelKomputer = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Komputer',
            'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelBudaya = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Budaya Humanis',
            'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
            'Tampilkan_Indikator' => true,
        ]);
        $mapelFisik = MataPelajaran::factory()->create([
            'Nama_Mapel' => 'Data Fisik & Kehadiran',
            'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
            'Tampilkan_Indikator' => false,
        ]);

        // Named explicitly because the factory pool can collide with the subjects above.
        foreach (['Matematika', 'Bahasa Indonesia', 'Pendidikan Pancasila'] as $nama) {
            MataPelajaran::factory()->create([
                'Nama_Mapel' => $nama,
                'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler,
                'Tampilkan_Indikator' => true,
            ]);
        }

        $kelasRespect = Kelas::factory()->create([
            'TahunAjaran_ID' => $periode->getKey(),
            'User_ID' => $guru->getKey(),
            'Nama_Kelas' => 'TK-A Respect',
            'Jenjang' => Jenjang::Tka,
        ]);
        $kelasSincerity = Kelas::factory()->create([
            'TahunAjaran_ID' => $periode->getKey(),
            'User_ID' => $guruWaliLain->getKey(),
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

        // The homeroom teacher covers the prose subjects; the muatan-lokal subjects
        // belong to a subject teacher, so the two export scopes both have data.
        $mapelWali = [$mapelAgama, $mapelJati, $mapelLiterasi, $mapelKokurikuler, $mapelFisik];
        $mapelMapel = [$mapelInggris, $mapelMandarin, $mapelKomputer, $mapelBudaya];

        foreach ([$kelasRespect, $kelasSincerity] as $kelas) {
            foreach ($mapelWali as $mapel) {
                Pembelajaran::factory()->create([
                    'Kelas_ID' => $kelas->getKey(),
                    'Mapel_ID' => $mapel->getKey(),
                    'User_ID' => $kelas->User_ID,
                ]);
            }

            foreach ($mapelMapel as $mapel) {
                Pembelajaran::factory()->create([
                    'Kelas_ID' => $kelas->getKey(),
                    'Mapel_ID' => $mapel->getKey(),
                    'User_ID' => $guruLain->getKey(),
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

        // Table-template subjects get their own programmes and indicators, which is
        // what the report uses to tell them apart from the prose subjects.
        $programInggris = ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelInggris->getKey(),
            'Nama_Program' => 'Bahasa',
        ]);
        $indikatorInggris = collect([
            'Listening: Identify simple vocabularies and specific pictures.',
            'Speaking: Practice pronunciation of specific vocabularies.',
            'Reading: Reading simple Consonant-Vowel-Consonant words.',
            'Writing: Rewrite simple Consonant-Vowel-Consonant words.',
        ])->map(fn (string $teks) => IndikatorCapaian::factory()->create([
            'Program_ID' => $programInggris->getKey(),
            'TahunAjaran_ID' => $periode->getKey(),
            'Semester' => Semester::Ganjil,
            'Tipe' => TipeIndikator::Capaian,
            'Deskripsi' => $teks,
        ]));

        $programKomputer = ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelKomputer->getKey(),
            'Nama_Program' => 'Dasar-dasar Literasi, Numerasi, Teknologi dan Seni Digital',
        ]);
        $indikatorKomputer = collect([
            'Terampil menggunakan mouse (single-click, double-click, drag and drop).',
            'Memahami fungsi tombol minimize, maximize, dan close pada jendela aplikasi.',
            'Mampu menggunakan aplikasi Paint untuk menggambar dan memahami fungsinya.',
            'Mampu mengidentifikasi perangkat keras utama (monitor, mouse, dan keyboard).',
        ])->map(fn (string $teks) => IndikatorCapaian::factory()->create([
            'Program_ID' => $programKomputer->getKey(),
            'TahunAjaran_ID' => $periode->getKey(),
            'Semester' => Semester::Ganjil,
            'Tipe' => TipeIndikator::Capaian,
            'Deskripsi' => $teks,
        ]));

        $programBudaya = ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelBudaya->getKey(),
            'Nama_Program' => 'Nilai agama, moral dan budi pekerti serta jati diri',
        ]);
        $indikatorBudaya = collect([
            'Pembiasaan budaya di sekolah: menerapkan sikap berdiri seperti pohon, duduk seperti lonceng.',
            'Pengetahuan: mengidentifikasi gambar perilaku yang menunjukkan hak dan kewajiban.',
            'Keterampilan: terampil mempraktikkan etika makan pada kelas saji teh (cha dao).',
            'Kata Perenungan: menyebutkan makna sederhana dari kata perenungan.',
        ])->map(fn (string $teks) => IndikatorCapaian::factory()->create([
            'Program_ID' => $programBudaya->getKey(),
            'TahunAjaran_ID' => $periode->getKey(),
            'Semester' => Semester::Ganjil,
            'Tipe' => TipeIndikator::Capaian,
            'Deskripsi' => $teks,
        ]));

        // Mandarin's four skills, with the bilingual wording from the reference.
        $programMandarin = ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelMandarin->getKey(),
            'Nama_Program' => 'Bahasa',
        ]);
        $indikatorMandarin = collect([
            "听 Listening :\n能根据所听到的拼音，正确识别并匹配对应的图画。\nMengidentifikasi gambar sesuai pinyin yang didengar.",
            "说 Speaking :\n能通过图画提示，开口说出词。\nMenyebutkan kata (28 kosakata) sesuai simbol gambar.",
            "读 Reading :\n能认读附有拼音的汉字，并能准确朗读。\nMembaca kosakata sesuai pinyin dengan tepat.",
            "写 Writing :\n能依照笔画顺序，规范描写常用汉字。\nMencontoh karakter Mandarin sesuai urutan goresannya.",
        ])->map(fn (string $teks) => IndikatorCapaian::factory()->create([
            'Program_ID' => $programMandarin->getKey(),
            'TahunAjaran_ID' => $periode->getKey(),
            'Semester' => Semester::Ganjil,
            'Tipe' => TipeIndikator::Capaian,
            'Deskripsi' => $teks,
        ]));

        // Section B: the kokurikuler project, narrated once for the whole student.
        ProgramPengembangan::factory()->create([
            'Mapel_ID' => $mapelKokurikuler->getKey(),
            'Nama_Program' => 'Proyek Penguatan Profil Pelajar Pancasila - Alat Transportasi',
        ]);

        // Keyed by the seeded subject name: the narrative loop below looks each one
        // up and creates the matching Narasi row.
        $narasiTeks = [
            'Nilai-nilai Agama & Budi Pekerti' => 'Andini terbiasa berdoa sebelum kegiatan tanpa diingatkan. Ia juga mulai menunjukkan sikap menghargai teman yang berbeda keyakinan saat kegiatan bersama di kelas.',
            'Jati Diri' => 'Andini semakin percaya diri menyampaikan pendapat di depan kelas dan mampu menyelesaikan tugas kelompok bersama temannya.',
            'Dasar-dasar Literasi, Numerasi, Sains, Teknologi, Rekayasa, dan Seni' => 'Andini mengenali huruf awal namanya dan senang bereksperimen dengan bahan sederhana di kelas.',
            'Proyek Penguatan Profil Pelajar Pancasila' => 'Andini bekerja sama dengan teman sekelompok menyusun proyek alat transportasi. Ia menyumbang ide bentuk roda dan membantu mewarnai hasil karya kelompok.',
            'Bahasa Inggris' => 'Andini mampu menyebutkan kosakata sederhana tentang warna dan anggota tubuh. Ia senang menyanyi lagu bahasa Inggris bersama teman sekelas.',
            'Bahasa Mandarin' => 'Andini mengenali pinyin sederhana dan mengikuti gerakan lagu Mandarin bersama teman sekelas.',
            'Komputer' => 'Andini terampil menggunakan mouse dan mengenali bagian utama komputer. Ia senang menggambar menggunakan aplikasi Paint.',
            'Budaya Humanis' => 'Andini mempraktikkan sikap duduk seperti lonceng dan mengikuti etika makan pada kelas saji teh dengan tertib.',
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
                $mapel = match (true) {
                    str_contains($namaMapel, 'Agama') => $mapelAgama,
                    str_contains($namaMapel, 'Jati Diri') => $mapelJati,
                    str_contains($namaMapel, 'Literasi') => $mapelLiterasi,
                    str_contains($namaMapel, 'Proyek') => $mapelKokurikuler,
                    str_contains($namaMapel, 'Inggris') => $mapelInggris,
                    str_contains($namaMapel, 'Mandarin') => $mapelMandarin,
                    str_contains($namaMapel, 'Komputer') => $mapelKomputer,
                    str_contains($namaMapel, 'Budaya') => $mapelBudaya,
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

                // Only the prose subjects carry scored indicators in the reference; the
                // table subjects do too, so score whichever programme this subject owns.
                $milik = match ($mapel->getKey()) {
                    $mapelAgama->getKey() => $indikator,
                    $mapelInggris->getKey() => $indikatorInggris,
                    $mapelKomputer->getKey() => $indikatorKomputer,
                    $mapelBudaya->getKey() => $indikatorBudaya,
                    $mapelMandarin->getKey() => $indikatorMandarin,
                    default => collect(),
                };

                if ($milik->isNotEmpty()) {
                    $sampled = $lengkap ? $milik : $milik->take(3);

                    foreach ($sampled as $i => $ind) {
                        NilaiSiswa::factory()->create([
                            'Narasi_ID' => $narasi->getKey(),
                            'Indikator_ID' => $ind->getKey(),
                            'Nilai' => $i % 3 === 0 ? Nilai::BerkembangSangatBaik : ($i % 3 === 1 ? Nilai::BerkembangSesuaiHarapan : Nilai::MulaiBerkembang),
                            'Dipilih_Untuk_Narasi' => $i < 2,
                        ]);
                    }
                }

                // Prose sections print a three-photo strip; the table-template pages
                // carry a single photo inside the Catatan block instead.
                $jumlahFoto = $mapel->Jenis_Mapel === JenisMataPelajaran::MuatanLokal ? 1 : Dokumentasi::MAKS_FOTO;

                foreach ($this->fotoKegiatan($mapel->Nama_Mapel, $jumlahFoto) as $berkas) {
                    Dokumentasi::factory()->create([
                        'Narasi_ID' => $narasi->getKey(),
                        'Foto' => $berkas,
                        'Keterangan' => 'Dokumentasi kegiatan '.$mapel->Nama_Mapel,
                    ]);
                }
            }
        }

        $this->command->info('Demo data siap. Akun: sari@tkcktc.sch.id (Guru Wali Kelas), ratna@tkcktc.sch.id (Guru Mata Pelajaran), andre@tkcktc.sch.id (Tata Usaha), mei@tkcktc.sch.id (Kepala Sekolah) - password: rahasia-kuat');
    }

    /**
     * Write placeholder JPEGs for one subject and return their public-disk paths.
     *
     * The reference report shows photos under every subject, and the seed has no
     * photos to draw from, so the images are generated here.
     *
     * @return array<int, string>
     */
    private function fotoKegiatan(string $namaMapel, int $jumlah = 3): array
    {
        $slug = Str::slug($namaMapel);
        $berkas = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $relatif = 'dokumentasi/'.$slug.'-'.$i.'.jpg';
            $warna = match ($i) {
                1 => [0x7F, 0xC9, 0x7F],
                2 => [0x7F, 0xA8, 0xD8],
                default => [0xD8, 0xC0, 0x7F],
            };

            $gambar = imagecreatetruecolor(600, 400);

            if ($gambar === false) {
                throw new RuntimeException('GD tidak tersedia untuk membuat foto contoh.');
            }

            $isi = imagecolorallocate($gambar, ...$warna);
            $tinta = imagecolorallocate($gambar, 0x1A, 0x1A, 0x1A);

            if ($isi === false || $tinta === false) {
                throw new RuntimeException('Gagal menyiapkan warna untuk foto contoh.');
            }

            imagefill($gambar, 0, 0, $isi);
            imagestring($gambar, 5, 20, 180, $namaMapel.' - foto '.$i, $tinta);

            ob_start();
            imagejpeg($gambar);
            Storage::disk('public')->put($relatif, (string) ob_get_clean());
            imagedestroy($gambar);

            $berkas[] = $relatif;
        }

        return $berkas;
    }
}
