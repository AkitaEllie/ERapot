<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'NISN' => fake()->unique()->numerify('##########'),
            'NIS' => fake()->unique()->numerify('####'),
            'Nama' => fake()->name(),
            'Jenis_Kelamin' => fake()->randomElement(JenisKelamin::cases()),
            'Agama' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha']),
        ];
    }
}
