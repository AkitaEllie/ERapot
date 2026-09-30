<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\Semester;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $Siswa_ID
 * @property string $NISN
 * @property string $NIS
 * @property string $Nama
 * @property JenisKelamin $Jenis_Kelamin
 * @property string $Agama
 */
#[Fillable(['Siswa_ID', 'NISN', 'NIS', 'Nama', 'Jenis_Kelamin', 'Agama'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Siswa_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Jenis_Kelamin' => JenisKelamin::class,
        ];
    }

    protected static function codePrefix(): string
    {
        return 'S-';
    }

    /**
     * @return HasMany<Rapor, $this>
     */
    public function rapor(): HasMany
    {
        return $this->hasMany(Rapor::class, 'Siswa_ID', 'Siswa_ID');
    }

    /**
     * This student's report history, newest first, as declared on the class diagram.
     *
     * @return Collection<int, Rapor>
     */
    public function getRiwayatRapor(): Collection
    {
        return $this->rapor()
            ->with(['kelas', 'narasi'])
            ->orderByDesc('Rapor_ID')
            ->get();
    }

    /**
     * Students who have no report for the given semester, i.e. those not yet
     * placed in a class. Returned as a query so callers can count, search or
     * limit without re-implementing the condition.
     *
     * @return Builder<static>
     */
    public static function belumDitempatkan(Semester $semester): Builder
    {
        return static::query()
            ->whereDoesntHave('rapor', fn ($query) => $query->where('Semester', $semester))
            ->orderBy('Nama');
    }
}
