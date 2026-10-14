<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when narration cannot be generated: no provider is configured, or the
 * configured provider rejected or failed the request.
 *
 * A distinct type so callers can tell "the AI is unavailable" from any other
 * failure and say something useful to the teacher.
 */
class NarasiTidakTersedia extends RuntimeException
{
    public static function belumDikonfigurasi(string $penyedia): self
    {
        return new self("Penyedia narasi \"{$penyedia}\" belum dikonfigurasi. Isi kunci API pada berkas .env.");
    }

    public static function tidakDikenal(string $penyedia): self
    {
        return new self("Penyedia narasi \"{$penyedia}\" tidak dikenal.");
    }

    public static function permintaanGagal(string $penyedia, string $sebab): self
    {
        return new self("Penyedia narasi \"{$penyedia}\" gagal menjawab: {$sebab}");
    }

    public static function balasanKosong(string $penyedia): self
    {
        return new self("Penyedia narasi \"{$penyedia}\" tidak mengembalikan teks.");
    }
}
