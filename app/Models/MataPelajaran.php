<?php

namespace App\Models;

use App\Enums\JenisMataPelajaran;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\MataPelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $Mapel_ID
 * @property string $Nama_Mapel
 * @property JenisMataPelajaran $Jenis_Mapel
 * @property bool $Tampilkan_Indikator
 */
#[Fillable(['Mapel_ID', 'Nama_Mapel', 'Jenis_Mapel', 'Tampilkan_Indikator'])]
class MataPelajaran extends Model
{
    /** @use HasFactory<MataPelajaranFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Mapel_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Jenis_Mapel' => JenisMataPelajaran::class,
            'Tampilkan_Indikator' => 'boolean',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'MP-';
    }

    /**
     * @return HasMany<Pembelajaran, $this>
     */
    public function pembelajaran(): HasMany
    {
        return $this->hasMany(Pembelajaran::class, 'Mapel_ID', 'Mapel_ID');
    }

    /**
     * @return HasMany<ProgramPengembangan, $this>
     */
    public function programPengembangan(): HasMany
    {
        return $this->hasMany(ProgramPengembangan::class, 'Mapel_ID', 'Mapel_ID');
    }

    /**
     * @return HasMany<Narasi, $this>
     */
    public function narasi(): HasMany
    {
        return $this->hasMany(Narasi::class, 'Mapel_ID', 'Mapel_ID');
    }

    // Not on ERD/CD: query scope added during scaffolding.

    /**
     * @param  Builder<MataPelajaran>  $query
     * @return Builder<MataPelajaran>
     */
    #[Scope]
    protected function denganIndikator(Builder $query): Builder
    {
        return $query->where('Tampilkan_Indikator', true);
    }

    /**
     * The development programmes that carry this subject's indicators, as
     * declared on the class diagram.
     *
     * @return Collection<int, ProgramPengembangan>
     */
    public function getProgram(): Collection
    {
        return $this->programPengembangan()
            ->with('indikatorCapaian')
            ->orderBy('Nama_Program')
            ->get();
    }
}
