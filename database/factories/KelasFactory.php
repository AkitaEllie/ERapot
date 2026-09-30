<?php

namespace Database\Factories;

use App\Enums\Jenjang;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'TahunAjaran_ID' => TahunAjaran::factory(),
            'User_ID' => User::factory(),
            'Nama_Kelas' => fake()->unique()->bothify('??-?#'),
            'Jenjang' => fake()->randomElement(Jenjang::cases()),
            'Fase' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
        ];
    }
}
