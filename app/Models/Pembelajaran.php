<?php

namespace App\Models;

use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\PembelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One subject taught by one teacher to one class.
 *
 * @property string $Pembelajaran_ID
 * @property string $Kelas_ID
 * @property string $Mapel_ID
 * @property string $User_ID
 */
#[Fillable(['Pembelajaran_ID', 'Kelas_ID', 'Mapel_ID', 'User_ID'])]
class Pembelajaran extends Model
{
    /** @use HasFactory<PembelajaranFactory> */
    use HasFactory, HasShortSequenceKey;

    protected $primaryKey = 'Pembelajaran_ID';

    protected static function codePrefix(): string
    {
        return 'PM-';
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'Kelas_ID', 'Kelas_ID');
    }

    /**
     * @return BelongsTo<MataPelajaran, $this>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'Mapel_ID', 'Mapel_ID');
    }

    /**
     * The teacher responsible for this teaching assignment.
     *
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID', 'User_ID');
    }

    /**
     * The teacher assigned to this teaching slot.
     *
     * The class diagram types this as Guru, but the no-persona-classes decision
     * means the teacher is a User, so the return type follows the decision.
     */
    public function getGuru(): ?User
    {
        return $this->guru;
    }

    /**
     * Assign (or clear) the teacher for a class and subject pair.
     *
     * The row is created on first use, matching the unique index on
     * (Kelas_ID, Mapel_ID). Passing null leaves the slot unassigned, which is
     * what the "subject without a teacher" task counts.
     */
    public static function tugaskan(Kelas $kelas, MataPelajaran $mapel, ?User $user = null): self
    {
        $pembelajaran = static::query()->firstOrNew([
            'Kelas_ID' => $kelas->getKey(),
            'Mapel_ID' => $mapel->getKey(),
        ]);

        $pembelajaran->User_ID = $user?->getKey();
        $pembelajaran->save();

        return $pembelajaran;
    }
}
