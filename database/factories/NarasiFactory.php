<?php

namespace Database\Factories;

use App\Enums\StatusNarasi;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Rapor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Narasi>
 */
class NarasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Rapor_ID' => Rapor::factory(),
            'Mapel_ID' => MataPelajaran::factory(),
            'Catatan_Guru' => null,
            'Draft_Narasi' => null,
            'Narasi_Final' => null,
            'Status_Narasi' => StatusNarasi::Draft,
            'Tanggal_Generate' => null,
            'Tanggal_Edit' => null,
        ];
    }

    public function denganDraft(string $draft = 'Siswa sudah menunjukkan perkembangan yang baik.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'Draft_Narasi' => $draft,
            'Status_Narasi' => StatusNarasi::Draft,
            'Tanggal_Generate' => now(),
        ]);
    }

    public function final(string $teks = 'Siswa sudah menunjukkan perkembangan yang baik.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'Draft_Narasi' => $teks,
            'Narasi_Final' => $teks,
            'Status_Narasi' => StatusNarasi::Final,
            'Tanggal_Generate' => now(),
            'Tanggal_Edit' => now(),
        ]);
    }
}
