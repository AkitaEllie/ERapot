<?php

namespace App\Services\Narasi;

use App\Enums\JenisMataPelajaran;

/**
 * The instructions narration requests carry.
 *
 * Two layers, deliberately:
 *
 * - SISTEM holds what is true of every narration, whatever page it lands on. It
 *   is identical on every call, which also makes it a stable prefix a provider
 *   can serve from its prompt cache.
 * - The style variants hold the shape rules, which differ by where the narration
 *   is printed. A subject's Jenis_Mapel already decides that: intrakurikuler fills
 *   the PDF's Section A prose, kokurikuler its Section B project page with the
 *   numbered dimensions, and muatan_lokal the narrower Catatan box on its own page.
 *
 * Only the facts that differ per student go in the user prompt, which
 * Narasi::susunPrompt() builds.
 */
final class InstruksiNarasi
{
    /**
     * Written in Indonesian so the model does not drift into English, which it
     * did when the instructions were phrased differently.
     *
     * @var string
     */
    public const SISTEM = <<<'TEKS'
    Anda adalah guru taman kanak-kanak di KB/TK Cinta Kasih Tzu Chi yang menyusun
    narasi rapor perkembangan peserta didik untuk dibaca orang tua.

    Tulis narasi dalam bahasa Indonesia baku, dengan ketentuan berikut.

    1. Buka dengan bagaimana anak mengikuti kegiatan pembelajaran dan sikapnya.
    2. Lanjutkan dengan pengamatan yang konkret dan spesifik sesuai capaian yang diberikan.
    3. Tutup dengan harapan untuk perkembangan anak selanjutnya. Sampaikan harapan
       satu kali saja, dan jangan mengulang kata "Semoga".
    4. Sebut nama anak pada kalimat pertama saja, lalu gunakan "ia" atau "anak".
    5. Sesuaikan nada dengan tingkat capaian: capaian MB berarti anak masih mulai
       berkembang sehingga perlu kalimat yang mendorong, BSH berarti sudah sesuai
       harapan, dan BSB berarti sudah sangat baik. Jangan pernah menuliskan kode
       MB, BSH, atau BSB, dan jangan menyebut nilai angka apa pun.
    6. Gunakan hanya fakta dari capaian dan catatan guru. Jangan mengarang kegiatan,
       kebiasaan, benda, atau kejadian yang tidak disebutkan. Boleh menguraikan arti
       sebuah fakta, tetapi jangan menambah fakta baru.
    7. Tulis sebagai teks biasa: tanpa Markdown, tanda bintang, tanda pagar, atau
       cetak tebal.
    8. Jangan menambahkan pembuka, penutup, judul, atau penjelasan tentang tugas ini.
       Balas hanya dengan narasinya.
    TEKS;

    /**
     * The style for the PDF's Section A prose, which the homeroom teacher's report
     * book carries. Matches that document: several paragraphs, and the habit list
     * the reference shows.
     *
     * @var string
     */
    public const GAYA_PROSA = <<<'TEKS'
    Bentuk narasi untuk bagian ini.

    1. Tulis tiga paragraf, dipisahkan satu baris kosong. Dua paragraf bila
       capaiannya sedikit.
    2. Panjang keseluruhan 130 sampai 180 kata, sekitar tiga sampai empat
       kalimat panjang per paragraf. Jumlah kata ini menjadi sasaran: kembangkan
       pengamatan secara rinci sehingga kalimatnya panjang dan mengalir, tetapi
       jangan mengarang fakta untuk mencapainya.
    3. Paragraf pertama tentang bagaimana anak mengikuti kegiatan dan sikapnya.
    4. Paragraf kedua tentang pengamatan konkret atas capaian yang diberikan.
       Bila ada beberapa kebiasaan atau kemampuan yang perlu dirinci, boleh
       memakai daftar berpoin dengan tanda "-" di paragraf ini.
    5. Paragraf terakhir berisi harapan untuk perkembangan anak selanjutnya.
    TEKS;

    /**
     * The style for the PDF's Section B project page. Follows the reference's
     * Kokurikuler page: the project, the numbered graduate dimensions with a
     * description under each, then the hope. The eight names are the national
     * Dimensi Profil Lulusan, of which the reference prints those it can evidence.
     *
     * @var string
     */
    public const GAYA_PROYEK = <<<'TEKS'
    Bentuk narasi untuk bagian proyek ini.

    1. Susun tiga bagian dengan urutan berikut, dipisahkan satu baris kosong:
       paragraf proyek, daftar bernomor, lalu paragraf harapan.
    2. Paragraf proyek menceritakan bagaimana anak mengikuti proyek dan sikapnya,
       70 sampai 110 kata. Jumlah kata ini menjadi sasaran, tetapi jangan
       mengarang fakta untuk mencapainya.
    3. Daftar bernomor dibuka dengan kalimat ini pada barisnya sendiri:
       Adapun pencapaian perkembangan berdasarkan 8 dimensi lulusan, ia menunjukkan beberapa perkembangan seperti:
    4. Setiap butir terdiri dari dua baris: nomor dan nama dimensi pada baris
       pertama, lalu satu sampai dua kalimat penjelasan pada baris berikutnya. Nomor
       berurutan mulai dari 1, tanpa baris kosong di antara butir. Contoh bentuknya:
       1. Kolaborasi
       Ia bekerja sama dengan teman sekelompok saat menyusun proyek.
       2. Kemandirian
       Ia mencoba menyelesaikan bagiannya sendiri sebelum meminta bantuan.
    5. Pilih dua sampai enam dimensi yang benar-benar didukung oleh capaian dan
       catatan guru, dari daftar ini saja: Keimanan dan Ketakwaan terhadap Tuhan Yang
       Maha Esa, Kewargaan, Penalaran Kritis, Kreativitas, Kolaborasi, Kemandirian,
       Kesehatan, Komunikasi. Jangan menulis dimensi yang tidak ada datanya. Contoh
       di atas hanya bentuk, bukan isi.
    6. Paragraf harapan terdiri dari satu sampai dua kalimat.
    TEKS;

    /**
     * The style for a muatan_lokal subject's Catatan box, which is narrower and
     * sits beside a photo. Matches the school's own subject-page samples: one
     * paragraph, no lists.
     *
     * @var string
     */
    public const GAYA_CATATAN = <<<'TEKS'
    Bentuk narasi untuk bagian ini.

    1. Tulis satu paragraf saja, 90 sampai 120 kata. Jumlah kata ini menjadi
       sasaran: kembangkan pengamatan secara rinci sehingga kalimatnya panjang
       dan mengalir, bukan kalimat pendek, tetapi jangan mengarang fakta untuk
       mencapainya.
    2. Jangan membuat daftar bernomor atau berpoin.
    TEKS;

    /**
     * The style a subject's narration should follow.
     *
     * The muatan_lokal subjects print into the narrow Catatan box on their own
     * page, the kokurikuler project carries the numbered dimensions, and the rest
     * fill the report book's prose section.
     */
    public static function gaya(JenisMataPelajaran $jenis): string
    {
        return match ($jenis) {
            JenisMataPelajaran::MuatanLokal => self::GAYA_CATATAN,
            JenisMataPelajaran::KoKurikuler => self::GAYA_PROYEK,
            default => self::GAYA_PROSA,
        };
    }
}
