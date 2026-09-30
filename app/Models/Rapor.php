<?php

namespace App\Models;

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Enums\StatusNarasi;
use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\RaporFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $Rapor_ID
 * @property string $Siswa_ID
 * @property string $Kelas_ID
 * @property string|null $Disetujui_Oleh
 * @property Semester $Semester
 * @property RaporStatus $Status
 * @property string|null $Tinggi_Badan
 * @property string|null $Berat_Badan
 * @property string|null $Lingkar_Kepala
 * @property string|null $Status_Pertumbuhan
 * @property int $Sakit
 * @property int $Izin
 * @property int $Tanpa_Keterangan
 * @property string|null $Catatan_Revisi
 * @property Carbon|null $Tanggal_Persetujuan
 * @property Carbon|null $Tanggal_Cetak
 * @property string|null $File_PDF
 */
#[Fillable([
    'Rapor_ID', 'Siswa_ID', 'Kelas_ID', 'Disetujui_Oleh', 'Semester', 'Status',
    'Tinggi_Badan', 'Berat_Badan', 'Lingkar_Kepala', 'Status_Pertumbuhan',
    'Sakit', 'Izin', 'Tanpa_Keterangan', 'Catatan_Revisi', 'Tanggal_Persetujuan',
    'Tanggal_Cetak', 'File_PDF',
])]
class Rapor extends Model
{
    /** @use HasFactory<RaporFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Rapor_ID';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Semester' => Semester::class,
            'Status' => RaporStatus::class,
            'Tinggi_Badan' => 'decimal:1',
            'Berat_Badan' => 'decimal:1',
            'Lingkar_Kepala' => 'decimal:1',
            'Sakit' => 'integer',
            'Izin' => 'integer',
            'Tanpa_Keterangan' => 'integer',
            'Tanggal_Persetujuan' => 'datetime',
            'Tanggal_Cetak' => 'datetime',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'R-';
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'Siswa_ID', 'Siswa_ID');
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'Kelas_ID', 'Kelas_ID');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Disetujui_Oleh', 'User_ID');
    }

    /**
     * @return HasMany<Narasi, $this>
     */
    public function narasi(): HasMany
    {
        return $this->hasMany(Narasi::class, 'Rapor_ID', 'Rapor_ID');
    }

    // Not on ERD/CD: query scopes added during scaffolding.

    /**
     * @param  Builder<Rapor>  $query
     * @return Builder<Rapor>
     */
    #[Scope]
    protected function denganStatus(Builder $query, RaporStatus $status): Builder
    {
        return $query->where('Status', $status->value);
    }

    /**
     * @param  Builder<Rapor>  $query
     * @return Builder<Rapor>
     */
    #[Scope]
    protected function menungguPersetujuan(Builder $query): Builder
    {
        return $query->where('Status', RaporStatus::Menunggu->value);
    }

    // Not on ERD/CD: two-hop lookup (rapor -> kelas -> pembelajaran -> mapel) that
    // Eloquent cannot express as a relation, so it cannot be eager loaded.

    /**
     * @return Builder<MataPelajaran>
     */
    public function mataPelajaranRapor(): Builder
    {
        return MataPelajaran::query()
            ->whereIn('Mapel_ID', function ($query): void {
                $query->select('Mapel_ID')
                    ->from('pembelajarans')
                    ->where('Kelas_ID', $this->Kelas_ID);
            });
    }

    /**
     * Submit this report card for the principal's approval.
     */
    public function ajukanPersetujuan(): bool
    {
        if ($this->Status !== RaporStatus::Draft) {
            return false;
        }

        $this->forceFill([
            'Status' => RaporStatus::Menunggu,
            'Catatan_Revisi' => null,
        ])->save();

        return true;
    }

    /**
     * Approve this report card.
     */
    public function setujui(User $user): bool
    {
        if ($this->Status !== RaporStatus::Menunggu) {
            return false;
        }

        $this->forceFill([
            'Status' => RaporStatus::Disetujui,
            'Disetujui_Oleh' => $user->getKey(),
            'Tanggal_Persetujuan' => now(),
        ])->save();

        $this->finalkanNarasi();

        return true;
    }

    /**
     * Promote every written narrative to final, so an approved report never
     * keeps a draft alongside it.
     *
     * Narratives with no text are left as drafts on purpose: there is nothing to
     * finalise, and marking an empty one final would hide the gap.
     */
    private function finalkanNarasi(): void
    {
        $this->narasi()
            ->where('Status_Narasi', '!=', StatusNarasi::Final)
            ->whereNotNull('Draft_Narasi')
            ->where('Draft_Narasi', '!=', '')
            ->each(fn (Narasi $narasi) => $narasi->simpanNarasiFinal());
    }

    /**
     * Send this report card back to the teacher with a revision note.
     */
    public function kembalikanRevisi(string $catatan): bool
    {
        if ($this->Status !== RaporStatus::Menunggu) {
            return false;
        }

        $this->forceFill([
            'Status' => RaporStatus::PerluRevisi,
            'Catatan_Revisi' => $catatan,
        ])->save();

        return true;
    }

    /**
     * Review a submitted report card before approving it.
     *
     * @todo Implement per the class diagram.
     */
    public function reviewDrafRapor(): void
    {
        //
    }

    /**
     * Render this report card to a PDF and return its path.
     *
     * @todo Implement per the class diagram.
     */
    public function eksporPDF(): string
    {
        return '';
    }

    /**
     * Place a student in a class for a semester, expressed as an empty report.
     *
     * Reuses the student's existing report for that semester so a move does not
     * leave the previous class behind, and creates one when they have none. This
     * is the behaviour the Penempatan Siswa screen describes.
     */
    public static function pempatkan(Siswa $siswa, Kelas $kelas, Semester $semester): self
    {
        $rapor = static::query()
            ->where('Siswa_ID', $siswa->getKey())
            ->where('Semester', $semester)
            ->orderBy('Rapor_ID')
            ->first();

        if ($rapor instanceof self) {
            $rapor->forceFill([
                'Kelas_ID' => $kelas->getKey(),
                'Status' => RaporStatus::BelumDiisi,
            ])->save();

            return $rapor;
        }

        return static::create([
            'Siswa_ID' => $siswa->getKey(),
            'Kelas_ID' => $kelas->getKey(),
            'Semester' => $semester,
            'Status' => RaporStatus::BelumDiisi,
        ]);
    }
}
