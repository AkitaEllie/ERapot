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
    /**
     * Whether whoever runs the application has to act, rather than a retry helping.
     */
    private bool $perluDisiapkan = false;

    public static function belumDikonfigurasi(string $penyedia): self
    {
        $galat = new self("Penyedia narasi \"{$penyedia}\" belum dikonfigurasi. Isi kunci API pada berkas .env.");
        $galat->perluDisiapkan = true;

        return $galat;
    }

    /**
     * What to tell the teacher. The exception's own message carries the technical
     * cause for the log, which a teacher can neither use nor should have to read.
     */
    public function pesanUntukPengguna(): string
    {
        return $this->perluDisiapkan
            ? 'Layanan AI belum disiapkan. Hubungi administrator, atau tulis narasinya sendiri.'
            : 'Narasi belum dapat dibuat karena layanan AI tidak merespons. Coba lagi beberapa saat, atau tulis narasinya sendiri.';
    }

    public static function tidakDikenal(string $penyedia): self
    {
        $galat = new self("Penyedia narasi \"{$penyedia}\" tidak dikenal.");
        $galat->perluDisiapkan = true;

        return $galat;
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
