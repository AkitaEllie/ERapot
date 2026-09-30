<?php

namespace App\Models;

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\TahunAjaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $TahunAjaran_ID
 * @property string $Tahun_Ajaran
 * @property Semester $Semester_Aktif
 * @property Carbon $Tanggal_Mulai
 * @property Carbon $Tanggal_Selesai
 * @property bool $Is_Active
 */
#[Fillable(['TahunAjaran_ID', 'Tahun_Ajaran', 'Semester_Aktif', 'Tanggal_Mulai', 'Tanggal_Selesai', 'Is_Active'])]
class TahunAjaran extends Model
{
    /** @use HasFactory<TahunAjaranFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'TahunAjaran_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Semester_Aktif' => Semester::class,
            'Tanggal_Mulai' => 'date',
            'Tanggal_Selesai' => 'date',
            'Is_Active' => 'boolean',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'TA-';
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'TahunAjaran_ID', 'TahunAjaran_ID');
    }

    /**
     * @return HasMany<IndikatorCapaian, $this>
     */
    public function indikatorCapaian(): HasMany
    {
        return $this->hasMany(IndikatorCapaian::class, 'TahunAjaran_ID', 'TahunAjaran_ID');
    }

    // Not on ERD/CD: query scope and date-range helper added during scaffolding.

    /**
     * @param  Builder<TahunAjaran>  $query
     * @return Builder<TahunAjaran>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('Is_Active', true);
    }

    public function covers(\DateTimeInterface $date): bool
    {
        return $date >= $this->Tanggal_Mulai->startOfDay()
            && $date <= $this->Tanggal_Selesai->endOfDay();
    }

    /**
     * Make this the active school year.
     *
     * @todo Implement per the class diagram.
     */
    public function aktifkan(): void
    {
        //
    }

    /**
     * Move this school year to the other semester.
     *
     * As stated on the Tata Usaha dashboard, switching semester opens a fresh set
     * of empty report drafts for every student, keeping each one in the class they
     * were last placed in.
     */
    public function gantiSemester(): void
    {
        $semesterBaru = $this->Semester_Aktif === Semester::Ganjil
            ? Semester::Genap
            : Semester::Ganjil;

        $this->forceFill(['Semester_Aktif' => $semesterBaru])->save();

        foreach ($this->kelasTerakhirSetiapSiswa() as $siswaId => $kelasId) {
            $sudahAda = Rapor::query()
                ->where('Siswa_ID', $siswaId)
                ->where('Kelas_ID', $kelasId)
                ->where('Semester', $semesterBaru)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            Rapor::create([
                'Siswa_ID' => $siswaId,
                'Kelas_ID' => $kelasId,
                'Semester' => $semesterBaru,
                'Status' => RaporStatus::BelumDiisi,
            ]);
        }
    }

    /**
     * The class each student was most recently placed in, keyed by student.
     *
     * Students who have never been placed are absent, because a report needs a
     * class; they are handled by the Penempatan Siswa screen instead.
     *
     * @return Collection<string, string> Siswa_ID => Kelas_ID
     */
    private function kelasTerakhirSetiapSiswa(): Collection
    {
        // Rapor_ID is a per-year sequence, so the highest one is the newest.
        return Rapor::query()
            ->orderByDesc('Rapor_ID')
            ->get(['Siswa_ID', 'Kelas_ID'])
            ->groupBy('Siswa_ID')
            ->map(fn (Collection $baris): string => $baris->first()->Kelas_ID);
    }
}
