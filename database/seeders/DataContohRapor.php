<?php

namespace Database\Seeders;

use RuntimeException;

/**
 * Invented students for the demo: no record here describes a real child.
 *
 * Notes vary on purpose. Some are detailed and some are one thin line, because a
 * teacher writes both, and thin ones are where a narration model is tempted to make
 * things up. The narratives in data/narasi-contoh.json were generated from these
 * notes, so seeding needs no AI key; run `php artisan rapor:contoh-narasi` to
 * regenerate them after changing the notes or the instructions.
 */
final class DataContohRapor
{
    public const BERKAS_NARASI = __DIR__.'/data/narasi-contoh.json';

    /** The subject each note key belongs to. */
    public const MAPEL = [
        'agama' => 'Nilai-nilai Agama & Budi Pekerti',
        'jati' => 'Jati Diri',
        'literasi' => 'Dasar-dasar Literasi, Numerasi, Sains, Teknologi, Rekayasa, dan Seni',
        'proyek' => 'Proyek Penguatan Profil Pelajar Pancasila',
        'inggris' => 'Bahasa Inggris',
        'mandarin' => 'Bahasa Mandarin',
        'komputer' => 'Komputer',
        'budaya' => 'Budaya Humanis',
    ];

    /**
     * The students placed in TK-A Respect. "nilai" is cycled over a subject's
     * indicators, and "narasi" names the subjects that already have a narrative.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function kelasRespect(): array
    {
        return [
            '0154872301' => [
                'nama' => 'Andini Pratiwi', 'kelamin' => 'P', 'status' => 'disetujui',
                'tinggi' => 106.5, 'berat' => 17.4, 'lingkar' => 49.5, 'pertumbuhan' => 'Normal',
                'sakit' => 1, 'izin' => 0, 'alpa' => 0,
                'nilai' => ['BSB', 'BSB', 'BSH', 'BSB'],
                'catatan' => [
                    'agama' => 'Andini memimpin doa pagi saat mendapat giliran pada minggu ketiga Oktober dan hafal doa sebelum makan. Ketika Bima menumpahkan bekal, Andini menawarkan separuh rotinya.',
                    'jati' => 'Andini bercerita di depan kelas tentang adik barunya dengan suara yang jelas. Ia menyebut dirinya senang tetapi kadang cemburu, dan memilih memeluk boneka ketika sedih.',
                    'literasi' => 'Andini menulis namanya sendiri tanpa contoh, mengenal semua huruf vokal, dan berhitung sampai 20. Saat percobaan warna, ia menebak bahwa merah dan kuning menjadi jingga.',
                    'proyek' => 'Pada proyek alat transportasi, Andini membuat bus dari kardus susu dan memberi ide agar rodanya memakai tutup botol. Ia membagi tugas mewarnai dengan dua temannya, lalu menjelaskan hasilnya kepada kelas.',
                    'inggris' => 'Andini menyebut sepuluh nama warna dalam bahasa Inggris dan menyanyikan lagu Head, Shoulders, Knees and Toes sambil menunjuk anggota tubuhnya.',
                    'mandarin' => 'Andini menyapa guru dengan 老师好 setiap pagi dan berhitung sampai sepuluh dalam bahasa Mandarin. Ia dapat mencocokkan pinyin dengan gambar buah.',
                    'komputer' => 'Andini menggambar rumah di Paint memakai bentuk persegi dan segitiga, lalu mewarnainya dengan fill. Ia dapat menutup jendela aplikasi sendiri.',
                    'budaya' => 'Saat kelas saji teh, Andini menuang teh dengan dua tangan dan memberikannya sambil membungkuk. Ia dapat menyebut makna kata perenungan tentang bersyukur.',
                ],
            ],
            '0154872302' => [
                'nama' => 'Bima Arya Saputra', 'kelamin' => 'L', 'status' => 'disetujui',
                'tinggi' => 110.0, 'berat' => 19.2, 'lingkar' => 50.5, 'pertumbuhan' => 'Normal',
                'sakit' => 3, 'izin' => 1, 'alpa' => 0,
                'nilai' => ['BSH', 'MB', 'BSH', 'BSB'],
                'catatan' => [
                    'agama' => 'Bima ikut berdoa bersama dan mengucapkan terima kasih bila diingatkan guru.',
                    'jati' => 'Bima senang bermain bola saat istirahat. Pada bulan September ia sering menangis saat ditinggal ibunya, dan sejak Oktober ia masuk kelas sambil melambaikan tangan.',
                    'literasi' => 'Bima berhitung sampai 10 dan mengenal huruf B dari namanya. Ia paling lama bertahan pada kegiatan menyusun balok dan membuat menara setinggi sembilan balok.',
                    'proyek' => 'Bima membuat truk dari kotak sepatu. Ia lebih senang mengerjakan sendiri, dan guru mengajaknya bergabung saat kelompok memasang roda.',
                    'inggris' => 'Bima menyebut lima nama binatang dalam bahasa Inggris dengan bantuan gambar.',
                    'mandarin' => 'Bima menirukan salam 你好 dan mengikuti gerakan lagu, tetapi belum dapat mencocokkan pinyin dengan gambar.',
                    'komputer' => 'Bima bisa klik dan seret bentuk di Paint dengan bantuan guru. Ia antusias menunggu giliran memakai komputer.',
                    'budaya' => 'Bima duduk seperti lonceng selama kurang lebih lima menit saat kegiatan hening.',
                ],
            ],
            '0154872303' => [
                'nama' => 'Clarissa Wijaya', 'kelamin' => 'P', 'status' => 'menunggu',
                'tinggi' => 103.0, 'berat' => 15.8, 'lingkar' => 48.5, 'pertumbuhan' => 'Normal',
                'sakit' => 0, 'izin' => 2, 'alpa' => 0,
                'nilai' => ['BSH', 'BSH', 'BSB', 'BSH'],
                'catatan' => [
                    'agama' => 'Clarissa tenang saat berdoa dan menegur teman dengan lembut ketika ada yang berlari di kelas.',
                    'jati' => 'Clarissa pemalu pada bulan pertama. Ia sekarang mau menjawab pertanyaan guru bila ditanya langsung, walau suaranya pelan.',
                    'literasi' => 'Clarissa suka melihat buku bergambar di pojok baca dan menceritakan isi gambar kepada guru. Ia berhitung sampai 15.',
                    'proyek' => 'Clarissa mengusulkan warna perahu kelompoknya dan menggunting layar dari kertas lipat.',
                    'inggris' => 'Clarissa menyebut sepuluh kosakata buah dan melafalkan apple serta orange dengan jelas.',
                    'mandarin' => 'Clarissa suka menulis mengikuti garis karakter 人 dan 大 dan urutan goresannya sudah benar.',
                    'komputer' => 'Clarissa dapat menyebut monitor, keyboard, dan mouse.',
                    'budaya' => 'Clarissa berdiri tegak seperti pohon saat baris dan menyimpan sepatu di rak tanpa diminta.',
                ],
            ],
            '0154872304' => [
                'nama' => 'Dimas Nugroho', 'kelamin' => 'L', 'status' => 'draft',
                'tinggi' => 108.5, 'berat' => 20.6, 'lingkar' => 51.0, 'pertumbuhan' => 'Normal',
                'sakit' => 2, 'izin' => 0, 'alpa' => 1,
                'nilai' => ['BSH', 'BSH', 'MB', 'BSH'],
                'narasi' => ['agama', 'inggris'],
                'catatan' => [
                    'agama' => 'Dimas mulai berani speak up di depan kelas.',
                    'jati' => 'Dimas aktif, kadang berebut mainan.',
                    'literasi' => 'Dimas senang mengamati semut di halaman dan bertanya mengapa semut berbaris. Ia berhitung sampai 10.',
                    'proyek' => 'Dalam proyek alat transportasi, Dimas menyusun roda dari kardus bersama teman dan berani menjelaskan hasilnya di depan kelas.',
                    'inggris' => 'Dimas suka bernyanyi dalam bahasa Inggris.',
                    'mandarin' => 'Dimas menghafal berhitung satu sampai lima.',
                    'komputer' => 'Dimas belum sabar menunggu giliran.',
                    'budaya' => 'Dimas mengikuti etika makan pada kelas saji teh dengan arahan guru.',
                ],
            ],
            '0154872305' => [
                'nama' => 'Erika Salim', 'kelamin' => 'P', 'status' => 'perlu_revisi',
                'tinggi' => 104.5, 'berat' => 16.3, 'lingkar' => 49.0, 'pertumbuhan' => 'Normal',
                'sakit' => 0, 'izin' => 1, 'alpa' => 0,
                'nilai' => ['BSB', 'BSH', 'BSH', 'BSH'],
                'revisi' => 'Narasi Komputer masih terlalu umum, mohon tambahkan contoh kegiatan di kelas.',
                'narasi' => ['agama', 'jati', 'literasi', 'proyek', 'inggris', 'mandarin', 'komputer', 'budaya'],
                'catatan' => [
                    'agama' => 'Erika hafal doa harian dan mengajak temannya mencuci tangan sebelum makan.',
                    'jati' => 'Erika percaya diri saat pentas kelas dan menjadi pembawa acara kecil pada Hari Guru.',
                    'literasi' => 'Erika menulis nama beberapa temannya dan senang membaca label di rak mainan.',
                    'proyek' => 'Erika menjadi juru bicara kelompok kereta api dan mengingatkan teman agar bergantian memakai lem.',
                    'inggris' => 'Erika dapat memperkenalkan diri dengan kalimat My name is Erika.',
                    'mandarin' => 'Erika mengikuti lagu Mandarin.',
                    'komputer' => 'Erika baik.',
                    'budaya' => 'Erika menyebut makna kata perenungan tentang menghormati orang tua.',
                ],
            ],
            '0154872306' => [
                'nama' => 'Fajar Alvaro', 'kelamin' => 'L', 'status' => 'belum_diisi',
                'tinggi' => null, 'berat' => null, 'lingkar' => null, 'pertumbuhan' => null,
                'sakit' => 0, 'izin' => 0, 'alpa' => 0,
                'nilai' => [],
                'catatan' => [],
            ],
            '0154872307' => [
                'nama' => 'Gabriela Tanu', 'kelamin' => 'P', 'status' => 'draft',
                'tinggi' => null, 'berat' => null, 'lingkar' => null, 'pertumbuhan' => null,
                'sakit' => 1, 'izin' => 1, 'alpa' => 0,
                'nilai' => ['BSH', 'BSB', 'BSH', 'MB'],
                'catatan' => [
                    'agama' => 'Gabriela membantu merapikan alat doa bersama guru dan menyapa teman yang baru datang.',
                    'jati' => 'Gabriela sering menggambar keluarganya dan menceritakan siapa saja yang ada pada gambar itu.',
                    'literasi' => 'Gabriela mengenal huruf vokal dan mencocokkan angka 1 sampai 10 dengan jumlah benda. Ia masih tertukar antara angka 6 dan 9.',
                    'proyek' => 'Gabriela membuat pesawat dari botol plastik dan menempel sayap dengan selotip bersama Andini.',
                    'inggris' => 'Gabriela menyebut warna merah, biru, dan kuning dalam bahasa Inggris.',
                    'mandarin' => 'Gabriela menyukai lagu Mandarin tentang binatang dan menirukan gerakannya.',
                    'komputer' => 'Gabriela memakai fill di Paint untuk mewarnai gambar buatan guru.',
                    'budaya' => 'Gabriela duduk rapi saat kegiatan hening.',
                ],
            ],
        ];
    }

    /**
     * Students of TK-A Sincerity, so the second homeroom teacher has work to do.
     * Only some subjects are started, as early in a semester.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function kelasSincerity(): array
    {
        return [
            '0154872316' => [
                'nama' => 'Ivan Pratama', 'kelamin' => 'L', 'status' => 'draft',
                'tinggi' => 107.0, 'berat' => 18.1, 'lingkar' => 50.0, 'pertumbuhan' => 'Normal',
                'sakit' => 0, 'izin' => 0, 'alpa' => 0,
                'nilai' => ['BSH', 'BSH', 'BSB', 'BSH'],
                'catatan' => [
                    'agama' => 'Ivan berdoa dengan tertib dan mengembalikan mainan temannya yang terjatuh.',
                    'jati' => 'Ivan senang menjadi pemimpin barisan dan bangga saat mendapat tugas membagikan buku.',
                    'inggris' => 'Ivan menyebut nama anggota keluarga dalam bahasa Inggris.',
                    'komputer' => 'Ivan dapat melakukan klik ganda untuk membuka Paint.',
                ],
            ],
            '0154872317' => [
                'nama' => 'Jessica Tanoto', 'kelamin' => 'P', 'status' => 'draft',
                'tinggi' => 102.5, 'berat' => 15.2, 'lingkar' => 48.0, 'pertumbuhan' => 'Kurang',
                'sakit' => 4, 'izin' => 1, 'alpa' => 0,
                'nilai' => ['MB', 'BSH', 'MB', 'BSH'],
                'catatan' => [
                    'agama' => 'Jessica ikut berdoa sambil melihat temannya.',
                    'jati' => 'Jessica masih memilih duduk dekat guru pada pagi hari. Ia mulai bermain masak-masakan bersama satu teman.',
                    'mandarin' => 'Jessica sudah bisa menirukan salam dalam bahasa Mandarin.',
                    'budaya' => 'Jessica mencoba duduk seperti lonceng bersama teman-temannya.',
                ],
            ],
            '0154872318' => [
                'nama' => 'Kevin Wijaya', 'kelamin' => 'L', 'status' => 'belum_diisi',
                'tinggi' => null, 'berat' => null, 'lingkar' => null, 'pertumbuhan' => null,
                'sakit' => 0, 'izin' => 0, 'alpa' => 0,
                'nilai' => [],
                'catatan' => [],
            ],
        ];
    }

    /**
     * Which subjects need a stored narrative: all of a submitted report's, and
     * those a report in progress names.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function butuhNarasi(): array
    {
        $daftar = [];

        foreach (self::kelasRespect() + self::kelasSincerity() as $nisn => $data) {
            $kunci = in_array($data['status'], ['disetujui', 'menunggu'], true)
                ? array_keys($data['catatan'])
                : ($data['narasi'] ?? []);

            foreach ($kunci as $k) {
                $daftar[] = [(string) $nisn, $k];
            }
        }

        return $daftar;
    }

    /**
     * The stored narrative for a student's subject, or null when none was generated.
     */
    public static function narasi(string $nisn, string $kunciMapel): ?string
    {
        static $semua = null;

        if ($semua === null) {
            $isi = is_file(self::BERKAS_NARASI) ? file_get_contents(self::BERKAS_NARASI) : '{}';

            if ($isi === false) {
                throw new RuntimeException('Gagal membaca narasi contoh.');
            }

            $semua = json_decode($isi, true, flags: JSON_THROW_ON_ERROR);
        }

        return $semua[$nisn][$kunciMapel] ?? null;
    }
}
