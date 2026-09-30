<?php

namespace App\Models;

use App\Enums\Jenjang;
use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $Kelas_ID
 * @property string $TahunAjaran_ID
 * @property string|null $User_ID
 * @property string $Nama_Kelas
 * @property Jenjang $Jenjang
 * @property string $Fase
 */
#[Fillable(['Kelas_ID', 'TahunAjaran_ID', 'User_ID', 'Nama_Kelas', 'Jenjang', 'Fase'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Kelas_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Jenjang' => Jenjang::class,
        ];
    }

    protected static function codePrefix(): string
    {
        return 'K-';
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'TahunAjaran_ID', 'TahunAjaran_ID');
    }

    /**
     * The teacher assigned as this class's homeroom teacher, if one has been set.
     *
     * @return BelongsTo<User, $this>
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID', 'User_ID');
    }

    /**
     * @return HasMany<Pembelajaran, $this>
     */
    public function pembelajaran(): HasMany
    {
        return $this->hasMany(Pembelajaran::class, 'Kelas_ID', 'Kelas_ID');
    }

    /**
     * @return HasMany<Rapor, $this>
     */
    public function rapor(): HasMany
    {
        return $this->hasMany(Rapor::class, 'Kelas_ID', 'Kelas_ID');
    }

    /**
     * The share of this class's report cards that the principal has approved,
     * 0-100.
     */
    public function hitungProgres(): int
    {
        $total = $this->rapor()->count();

        if ($total === 0) {
            return 0;
        }

        $disetujui = $this->rapor()
            ->where('Status', RaporStatus::Disetujui->value)
            ->count();

        return (int) round($disetujui / $total * 100);
    }

    /**
     * This class's reports for a semester, as declared on the class diagram.
     *
     * @return EloquentCollection<int, Rapor>
     */
    public function getDaftarRapor(?Semester $semester = null): EloquentCollection
    {
        return $this->rapor()
            ->with('siswa')
            ->when($semester !== null, fn ($query) => $query->where('Semester', $semester))
            ->orderBy('Rapor_ID')
            ->get();
    }

    /**
     * Every class with its report progress for a semester counted in one query,
     * so the school-wide dashboard does not assemble it by hand.
     *
     * Adds `total_rapor` and `rapor_disetujui` counts to each class.
     *
     * @return EloquentCollection<int, static>
     */
    public static function denganProgres(Semester $semester): EloquentCollection
    {
        return static::query()
            ->with('waliKelas')
            ->withCount([
                'rapor as total_rapor' => fn ($query) => $query->where('Semester', $semester),
                'rapor as rapor_disetujui' => fn ($query) => $query
                    ->where('Semester', $semester)
                    ->where('Status', RaporStatus::Disetujui),
            ])
            ->orderBy('Nama_Kelas')
            ->get();
    }
}
