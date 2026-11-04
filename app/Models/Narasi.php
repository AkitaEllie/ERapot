<?php

namespace App\Models;

use App\Enums\StatusNarasi;
use App\Models\Concerns\HasShortSequenceKey;
use App\Services\Narasi\InstruksiNarasi;
use Database\Factories\NarasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
 * @property string|null $Generate_Token
 * @property Carbon|null $Generate_Mulai
 * @property string|null $Generate_Galat
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
     * How long a queued generation may stay unanswered before it counts as lost.
     * Generous, because it includes waiting behind other teachers' requests.
     */
    public const BATAS_GENERATE_MENIT = 15;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Status_Narasi' => StatusNarasi::class,
            'Tanggal_Generate' => 'datetime',
            'Tanggal_Edit' => 'datetime',
            'Generate_Mulai' => 'datetime',
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
     * Build the per-student prompt sent to the narration service.
     *
     * Only what differs between students goes here; the standing instructions live
     * in InstruksiNarasi and travel as the system message. The sections are in a
     * fixed order so the prompt's prefix stays identical across calls, which is
     * what lets a provider serve it from cache.
     */
    public function susunPrompt(): string
    {
        $rapor = $this->rapor;
        $siswa = $rapor->siswa;
        $mapel = $this->mataPelajaran;

        $nilai = $this->nilaiSiswa()
            ->where('Dipilih_Untuk_Narasi', true)
            ->with(['indikatorCapaian'])
            ->get();

        $baris = [
            'DATA PESERTA DIDIK',
            'Nama: '.$siswa->Nama,
            'Kelas: '.$rapor->kelas->Nama_Kelas,
            'Semester: '.$rapor->Semester->label(),
            'Mata pelajaran: '.$mapel->Nama_Mapel,
        ];

        if ($nilai->isNotEmpty()) {
            $baris[] = '';
            $baris[] = 'CAPAIAN YANG DICERITAKAN';
            $baris[] = 'Kode di dalam kurung hanya penanda tingkat untuk Anda, jangan ditulis ulang.';

            foreach ($nilai as $n) {
                $deskripsi = $n->indikatorCapaian?->Deskripsi;

                if (blank($deskripsi)) {
                    continue;
                }

                $baris[] = sprintf('- %s (%s)', $deskripsi, $n->Nilai->value);
            }
        }

        if (filled($this->Catatan_Guru)) {
            $baris[] = '';
            $baris[] = 'CATATAN PENGAMATAN GURU';
            $baris[] = $this->Catatan_Guru;
        }

        $baris[] = '';
        $baris[] = InstruksiNarasi::gaya($mapel->Jenis_Mapel);
        $baris[] = '';
        $baris[] = 'Tulis narasi untuk peserta didik di atas.';

        return implode("\n", $baris);
    }

    /**
     * Record that a background generation has been requested. The token identifies
     * this request, so only its own job may write the result.
     */
    public function mulaiGenerate(): string
    {
        $token = (string) Str::uuid();

        $this->forceFill([
            'Generate_Token' => $token,
            'Generate_Mulai' => now(),
            'Generate_Galat' => null,
        ])->save();

        return $token;
    }

    /**
     * Whether a generation is pending and has not been given up on.
     */
    public function sedangGenerate(): bool
    {
        return filled($this->Generate_Token) && ! $this->generateTerlambat();
    }

    /**
     * Whether a pending generation has gone unanswered too long: usually a sign
     * that no queue worker is running.
     */
    public function generateTerlambat(): bool
    {
        return filled($this->Generate_Token)
            && ($this->Generate_Mulai === null
                || $this->Generate_Mulai->lt(now()->subMinutes(self::BATAS_GENERATE_MENIT)));
    }

    /**
     * Whether a job's result is still wanted: its request is the current one, and
     * the report was not locked while the provider was working.
     */
    public function masihDitunggu(string $token): bool
    {
        return $this->Generate_Token === $token && $this->rapor->bolehDiubah();
    }

    public function selesaiGenerate(string $token, string $teks): void
    {
        if ($this->Generate_Token !== $token) {
            return;
        }

        $this->simpanDraft($teks);
        $this->forceFill(['Generate_Token' => null, 'Generate_Mulai' => null, 'Generate_Galat' => null])->save();
    }

    public function gagalGenerate(string $token, string $pesan): void
    {
        if ($this->Generate_Token !== $token) {
            return;
        }

        $this->forceFill(['Generate_Token' => null, 'Generate_Mulai' => null, 'Generate_Galat' => $pesan])->save();
    }

    /**
     * Stop waiting. A job already running finds its token gone and drops its result.
     */
    public function batalkanGenerate(): void
    {
        $this->forceFill(['Generate_Token' => null, 'Generate_Mulai' => null, 'Generate_Galat' => null])->save();
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
            'Generate_Galat' => null,
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
