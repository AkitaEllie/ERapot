<?php

namespace App\Models;

use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\DokumentasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $Dokumentasi_ID
 * @property string $Narasi_ID
 * @property string $Foto
 * @property string|null $Keterangan
 */
#[Fillable(['Dokumentasi_ID', 'Narasi_ID', 'Foto', 'Keterangan'])]
class Dokumentasi extends Model
{
    /** @use HasFactory<DokumentasiFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Dokumentasi_ID';

    protected static function codePrefix(): string
    {
        return 'D-';
    }

    /**
     * @return BelongsTo<Narasi, $this>
     */
    public function narasi(): BelongsTo
    {
        return $this->belongsTo(Narasi::class, 'Narasi_ID', 'Narasi_ID');
    }

    /**
     * The most photos a single narrative may carry, as shown on the report input screen.
     */
    public const MAKS_FOTO = 3;

    /**
     * Attach a photo to this record and store the file on the public disk.
     */
    public function unggahFoto(UploadedFile $file): void
    {
        abort_if($this->fotoSudahPenuh(), 422, 'Maksimal '.self::MAKS_FOTO.' foto per narasi.');

        $path = $file->store('dokumentasi', 'public');

        $this->forceFill([
            'Foto' => $path,
            'Keterangan' => $file->getClientOriginalName(),
        ])->save();
    }

    /**
     * Remove the attached photo and the file behind it. The row only exists to
     * carry a photo, so it goes too rather than lingering empty.
     */
    public function hapusFoto(): void
    {
        if (filled($this->Foto)) {
            Storage::disk('public')->delete($this->Foto);
        }

        $this->delete();
    }

    /**
     * Whether this narrative has no room left for another photo.
     *
     * Only rows that already hold a file are counted, because the row for the
     * photo being uploaded exists before unggahFoto() is called.
     */
    public function fotoSudahPenuh(): bool
    {
        return $this->narasi->dokumentasi()
            ->where('Foto', '!=', '')
            ->count() >= self::MAKS_FOTO;
    }
}
