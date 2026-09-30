<?php

namespace App\Models;

use App\Enums\Nilai;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\NilaiSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One indicator's attainment for one narrative, reached through the narrative's
 * report card and therefore its student.
 *
 * @property string $Nilai_ID
 * @property string $Narasi_ID
 * @property string $Indikator_ID
 * @property Nilai $Nilai
 * @property bool $Dipilih_Untuk_Narasi
 * @property string|null $Deskripsi_Capaian
 */
#[Fillable(['Nilai_ID', 'Narasi_ID', 'Indikator_ID', 'Nilai', 'Dipilih_Untuk_Narasi', 'Deskripsi_Capaian'])]
class NilaiSiswa extends Model
{
    /** @use HasFactory<NilaiSiswaFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Nilai_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Nilai' => Nilai::class,
            'Dipilih_Untuk_Narasi' => 'boolean',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'NS-';
    }

    /**
     * @return BelongsTo<Narasi, $this>
     */
    public function narasi(): BelongsTo
    {
        return $this->belongsTo(Narasi::class, 'Narasi_ID', 'Narasi_ID');
    }

    /**
     * @return BelongsTo<IndikatorCapaian, $this>
     */
    public function indikatorCapaian(): BelongsTo
    {
        return $this->belongsTo(IndikatorCapaian::class, 'Indikator_ID', 'Indikator_ID');
    }

    // Not on ERD/CD: query scope added during scaffolding.

    /**
     * @param  Builder<NilaiSiswa>  $query
     * @return Builder<NilaiSiswa>
     */
    #[Scope]
    protected function untukNarasi(Builder $query): Builder
    {
        return $query->where('Dipilih_Untuk_Narasi', true);
    }

    // Not on ERD/CD: the ERD gives this table no Siswa_ID, so the student is
    // reached by walking narasi -> rapor -> siswa. A plain method rather than a
    // relation because it cannot be eager loaded.

    public function pemilikNilai(): ?Siswa
    {
        return $this->narasi?->rapor?->siswa;
    }

    /**
     * Record or change this indicator's attainment.
     */
    public function isiNilai(Nilai $nilai): void
    {
        $this->forceFill(['Nilai' => $nilai])->save();
    }

    /**
     * Include this attainment when the narrative is written.
     */
    public function tandaiUntukNarasi(bool $dipilih = true): void
    {
        $this->forceFill(['Dipilih_Untuk_Narasi' => $dipilih])->save();
    }
}
