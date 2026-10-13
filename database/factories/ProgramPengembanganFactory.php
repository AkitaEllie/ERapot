<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use App\Models\ProgramPengembangan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramPengembangan>
 */
class ProgramPengembanganFactory extends Factory
{
    /**
     * The name is generated rather than drawn from a fixed pool: the seeder
     * defines several real programmes explicitly, and a pool entry that happens to
     * match one of them would trip the unique index on Nama_Program.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Mapel_ID' => MataPelajaran::factory(),
            'Nama_Program' => 'Program '.fake()->unique()->numerify('###'),
        ];
    }
}
