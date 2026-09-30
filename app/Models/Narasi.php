<?php

namespace App\Models;

use App\Enums\StatusNarasi;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\NarasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A subject-level narrative inside a report card.
 *
 * @property string $Narasi_ID
 * @property string $Rapor_ID
 * @property string $Mapel_ID
 * @property string|null $Catatan_Guru
 * @property string|null $Draft_Narasi
 * @property string|null $Narasi_Final
 * @property StatusNarasi $Status_Narasi
 * @property Carbon|null $Tanggal_Generate
 * @property Carbon|null $Tanggal_Edit
 */
#[Fillable([
    'Narasi_ID', 'Rapor_ID', 'Mapel_ID', 'Catatan_Guru',
    'Draft_Narasi', 'Narasi_Final', 'Status_Narasi', 'Tanggal_Generate', 'Tanggal_Edit',
])]
class Narasi extends Model
{
    /** @use HasFactory<NarasiFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Narasi_ID';

    /**
     * Set explicitly because the pluralizer would guess "narasis".
     *
     * @var string
     */
    protected $table = 'narasi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Status_Narasi' => StatusNarasi::class,
            'Tanggal_Generate' => 'datetime',
            'Tanggal_Edit' => 'datetime',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'N-';
    }

    /**
     * @return BelongsTo<Rapor, $this>
     */
    public function rapor(): BelongsTo
    {
        return $this->belongsTo(Rapor::class, 'Rapor_ID', 'Rapor_ID');
    }

    /**
     * @return BelongsTo<MataPelajaran, $this>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'Mapel_ID', 'Mapel_ID');
    }

    /**
     * @return HasMany<NilaiSiswa, $this>
     */
    public function nilaiSiswa(): HasMany
    {
        return $this->hasMany(NilaiSiswa::class, 'Narasi_ID', 'Narasi_ID');
    }

    /**
     * @return HasMany<Dokumentasi, $this>
     */
    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class, 'Narasi_ID', 'Narasi_ID');
    }

    // Not on ERD/CD: query scope and text accessor added during scaffolding.

    /**
     * @param  Builder<Narasi>  $query
     * @return Builder<Narasi>
     */
    #[Scope]
    protected function final(Builder $query): Builder
    {
        return $query->where('Status_Narasi', StatusNarasi::Final->value);
    }

    public function teks(): ?string
    {
        return $this->Status_Narasi->isFinal()
            ? $this->Narasi_Final
            : $this->Draft_Narasi;
    }

    /**
     * Build the prompt sent to the narration service.
     *
     * @todo Implement per the class diagram.
     */
    public function susunPrompt(): string
    {
        return '';
    }

    /**
     * Save the teacher's own note about this narrative.
     *
     * @todo Implement per the class diagram.
     */
    public function isiCatatanPersonal(string $catatan): void
    {
        $this->forceFill(['Catatan_Guru' => $catatan])->save();
    }

    /**
     * Save a generated draft and stamp the generation time.
     */
    public function simpanDraft(string $teks): void
    {
        $this->forceFill([
            'Draft_Narasi' => $teks,
            'Status_Narasi' => StatusNarasi::Draft,
            'Tanggal_Generate' => now(),
        ])->save();
    }

    /**
     * Revise the draft by hand, moving the narrative to Diedit.
     */
    public function editNarasi(string $teks): void
    {
        $this->forceFill([
            'Draft_Narasi' => $teks,
            'Status_Narasi' => StatusNarasi::Diedit,
            'Tanggal_Edit' => now(),
        ])->save();
    }

    /**
     * Promote the current draft to the final narrative.
     */
    public function simpanNarasiFinal(?string $teks = null): void
    {
        $this->forceFill([
            'Narasi_Final' => $teks ?? $this->Draft_Narasi,
            'Status_Narasi' => StatusNarasi::Final,
            'Tanggal_Edit' => now(),
        ])->save();
    }
}
