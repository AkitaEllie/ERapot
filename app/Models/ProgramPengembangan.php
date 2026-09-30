<?php

namespace App\Models;

use App\Enums\Semester;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\ProgramPengembanganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $Program_ID
 * @property string $Mapel_ID
 * @property string $Nama_Program
 */
#[Fillable(['Program_ID', 'Mapel_ID', 'Nama_Program'])]
class ProgramPengembangan extends Model
{
    /** @use HasFactory<ProgramPengembanganFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Program_ID';

    protected static function codePrefix(): string
    {
        return 'PP-';
    }

    /**
     * @return BelongsTo<MataPelajaran, $this>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'Mapel_ID', 'Mapel_ID');
    }

    /**
     * @return HasMany<IndikatorCapaian, $this>
     */
    public function indikatorCapaian(): HasMany
    {
        return $this->hasMany(IndikatorCapaian::class, 'Program_ID', 'Program_ID');
    }

    /**
     * The indicators this programme carries, as declared on the class diagram.
     *
     * @return Collection<int, IndikatorCapaian>
     */
    public function getIndikator(?Semester $semester = null): Collection
    {
        return $this->indikatorCapaian()
            ->when($semester !== null, fn ($query) => $query->where('Semester', $semester))
            ->orderBy('Kode_KD')
            ->get();
    }
}
