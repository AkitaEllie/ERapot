<?php

namespace App\Models;

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\IndikatorCapaianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $Indikator_ID
 * @property string $Program_ID
 * @property string $TahunAjaran_ID
 * @property Jenjang $Jenjang
 * @property Semester $Semester
 * @property TipeIndikator $Tipe
 * @property string $Kode_KD
 * @property string $Deskripsi
 */
#[Fillable(['Indikator_ID', 'Program_ID', 'TahunAjaran_ID', 'Jenjang', 'Semester', 'Tipe', 'Kode_KD', 'Deskripsi'])]
class IndikatorCapaian extends Model
{
    /** @use HasFactory<IndikatorCapaianFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Indikator_ID';

    /**
     * Set explicitly because the pluralizer would guess "indikator_capaians".
     *
     * @var string
     */
    protected $table = 'indikator_capaian';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Jenjang' => Jenjang::class,
            'Semester' => Semester::class,
            'Tipe' => TipeIndikator::class,
        ];
    }

    protected static function codePrefix(): string
    {
        return 'IC-';
    }

    protected static function booted(): void
    {
        // Not on ERD/CD: Kode_KD is derived from the generated Indikator_ID so the
        // teacher-facing code always stays in step with the short sequence key.
        static::creating(function (self $indikator): void {
            if (blank($indikator->Kode_KD)) {
                $indikator->Kode_KD = 'KD-'.substr((string) $indikator->getKey(), strlen(static::codePrefix()));
            }
        });
    }

    /**
     * @return BelongsTo<ProgramPengembangan, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramPengembangan::class, 'Program_ID', 'Program_ID');
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'TahunAjaran_ID', 'TahunAjaran_ID');
    }

    /**
     * @return HasMany<NilaiSiswa, $this>
     */
    public function nilaiSiswa(): HasMany
    {
        return $this->hasMany(NilaiSiswa::class, 'Indikator_ID', 'Indikator_ID');
    }

    // Not on ERD/CD: query scope added during scaffolding.

    /**
     * @param  Builder<IndikatorCapaian>  $query
     * @return Builder<IndikatorCapaian>
     */
    #[Scope]
    protected function untukKelas(Builder $query, Kelas $kelas, Semester $semester): Builder
    {
        return $query->where('Jenjang', $kelas->Jenjang->value)
            ->where('Semester', $semester->value);
    }

    /**
     * Copy this indicator into another semester of the same school year, so a
     * teacher can bring last term's criteria forward instead of retyping them.
     *
     * Skipped when the indicator already exists in the target semester, which
     * makes the operation safe to run more than once.
     */
    public function salinIndikator(Semester $semester): void
    {
        if ($semester === $this->Semester) {
            return;
        }

        $sudahAda = static::query()
            ->where('Program_ID', $this->Program_ID)
            ->where('Jenjang', $this->Jenjang)
            ->where('Semester', $semester)
            ->where('Deskripsi', $this->Deskripsi)
            ->exists();

        if ($sudahAda) {
            return;
        }

        static::create([
            'Program_ID' => $this->Program_ID,
            'TahunAjaran_ID' => $this->TahunAjaran_ID,
            'Jenjang' => $this->Jenjang,
            'Semester' => $semester,
            'Tipe' => $this->Tipe,
            'Kode_KD' => '',
            'Deskripsi' => $this->Deskripsi,
        ]);
    }
}
