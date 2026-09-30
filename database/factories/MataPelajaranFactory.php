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
    public function definition(): array
    {
        return [
            'Nama_Mapel' => fake()->unique()->randomElement([
                'Pendidikan Agama Islam',
                'Pendidikan Pancasila',
                'Bahasa Indonesia',
                'Matematika',
                'Ilmu Pengetahuan Alam',
                'Ilmu Pengetahuan Sosial',
                'Bahasa Inggris',
                'Informatika',
                'Pendidikan Jasmani',
                'Seni Budaya',
            ]),
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
