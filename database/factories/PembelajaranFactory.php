<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pembelajaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembelajaran>
 */
class PembelajaranFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Kelas_ID' => Kelas::factory(),
            'Mapel_ID' => MataPelajaran::factory(),
            'User_ID' => User::factory(),
        ];
    }
}
