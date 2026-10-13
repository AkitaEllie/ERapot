<?php

namespace Database\Factories;

use App\Enums\JenisMataPelajaran;
use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataPelajaran>
 */
class MataPelajaranFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    /**
     * The name is generated rather than drawn from a fixed pool: the seeder
     * defines several real subjects explicitly, and a pool entry that happens to
     * match one of them would trip the unique index on Nama_Mapel.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Nama_Mapel' => 'Mapel '.fake()->unique()->numerify('###'),
            'Jenis_Mapel' => fake()->randomElement(JenisMataPelajaran::cases()),
            'Tampilkan_Indikator' => true,
        ];
    }

    public function denganIndikator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Tampilkan_Indikator' => true,
        ]);
    }

    public function tanpaIndikator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Tampilkan_Indikator' => false,
        ]);
    }
}
