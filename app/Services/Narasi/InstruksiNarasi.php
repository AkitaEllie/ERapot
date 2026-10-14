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
 *   is printed. A subject's Jenis_Mapel already decides that: the intrakurikuler
 *   and kokurikuler subjects fill the PDF's Section A and B prose, while the
 *   muatan_lokal subjects fill the narrower Catatan box on their own page.
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
    narasi rapor perkembangan peserta didik.

    Tulis narasi dalam bahasa Indonesia baku, dengan ketentuan berikut.

    IS1. Buka dengan bagaimana anak mengikuti kegiatan pembelajaran dan sikapnya.
    IS2. Lanjutkan dengan pengamatan yang konkret dan spesifik sesuai capaian yang diberikan.
    IS3. Tutup dengan harapan untuk perkembangan anak selanjutnya.
    IS4. Sebut nama anak pada kalimat pertama saja, lalu gunakan "ia" atau "anak".
    IS5. Sesuaikan nada dengan tingkat capaian: capaian MB berarti anak masih mulai
         berkembang sehingga perlu kalimat yang mendorong, BSH berarti sudah sesuai
         harapan, dan BSB berarti sudah sangat baik. Jangan pernah menuliskan kode
         MB, BSH, atau BSB, dan jangan menyebut nilai angka apa pun.
    IS6. Jangan menambahkan pembuka, penutup, judul, atau penjelasan tentang tugas ini.
         Balas hanya dengan narasinya.
    TEKS;

    /**
     * The style for the PDF's Section A and B prose, which the homeroom teacher's
     * report book carries. Matches that document: several paragraphs, and the
     * habit list the reference shows.
     *
     * @var string
     */
    public const GAYA_PROSA = <<<'TEKS'
    Bentuk narasi untuk bagian ini.

    PS1. Tulis tiga paragraf, dipisahkan satu baris kosong. Dua paragraf bila
         capaiannya sedikit.
    PS2. Panjang keseluruhan 130 sampai 180 kata, sekitar tiga sampai empat
         kalimat panjang per paragraf. Jumlah kata ini yang utama: kembangkan
         pengamatan secara rinci sehingga kalimatnya panjang dan mengalir.
    PS3. Paragraf pertama tentang bagaimana anak mengikuti kegiatan dan sikapnya.
    PS4. Paragraf kedua tentang pengamatan konkret atas capaian yang diberikan.
         Bila ada beberapa kebiasaan atau kemampuan yang perlu dirinci, boleh
         memakai daftar berpoin dengan tanda "-" di paragraf ini.
    PS5. Paragraf terakhir berisi harapan untuk perkembangan anak selanjutnya.
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

    CS1. Tulis satu paragraf saja, 90 sampai 120 kata. Jumlah kata ini yang
         utama: kembangkan pengamatan secara rinci sehingga kalimatnya panjang
         dan mengalir, bukan kalimat pendek.
    CS2. Jangan membuat daftar bernomor atau berpoin, dan jangan memakai tanda
         bintang atau penanda format lainnya.
    TEKS;

    /**
     * The style a subject's narration should follow.
     *
     * The muatan_lokal subjects print into the narrow Catatan box on their own
     * page; everything else fills the report book's prose sections.
     */
    public static function gaya(JenisMataPelajaran $jenis): string
    {
        return $jenis === JenisMataPelajaran::MuatanLokal
            ? self::GAYA_CATATAN
            : self::GAYA_PROSA;
    }
}
