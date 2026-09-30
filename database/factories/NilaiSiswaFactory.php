<?php

namespace Database\Factories;

use App\Enums\Nilai;
use App\Models\IndikatorCapaian;
use App\Models\Narasi;
use App\Models\NilaiSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiSiswa>
 */
class NilaiSiswaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Narasi_ID' => Narasi::factory(),
            'Indikator_ID' => IndikatorCapaian::factory(),
            'Nilai' => fake()->randomElement(Nilai::cases()),
            'Dipilih_Untuk_Narasi' => false,
            'Deskripsi_Capaian' => null,
        ];
    }

    public function untukNarasi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Dipilih_Untuk_Narasi' => true,
        ]);
    }
}
