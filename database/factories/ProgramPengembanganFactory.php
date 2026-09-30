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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Mapel_ID' => MataPelajaran::factory(),
            'Nama_Program' => fake()->unique()->randomElement([
                'Program Penguatan Profil Pelajar Pancasila',
                'Program Literasi Sekolah',
                'Program Numerasi Sekolah',
                'Program Pembelajaran Karakter',
                'Program Kesehatan Sekolah',
            ]),
        ];
    }
}
