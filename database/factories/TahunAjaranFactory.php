<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    /**
     * Years are drawn from 1990-2009 on purpose. Tests that care about a real
     * school year pin one (2025/2026, say), and a factory-generated year that
     * happened to land on the same value would violate the unique index on
     * (Tahun_Ajaran, Semester_Aktif). The range keeps the filler distinct.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(1990, 2009);
        $mulai = sprintf('%d-07-01', $year);

        return [
            'Tahun_Ajaran' => $year.'/'.($year + 1),
            'Semester_Aktif' => fake()->randomElement(Semester::cases()),
            'Tanggal_Mulai' => $mulai,
            'Tanggal_Selesai' => sprintf('%d-06-30', $year + 1),
            'Is_Active' => true,
        ];
    }

    public function ganjil(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Semester_Aktif' => Semester::Ganjil,
        ]);
    }

    public function genap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Semester_Aktif' => Semester::Genap,
        ]);
    }

    public function aktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Is_Active' => true,
        ]);
    }

    public function tidakAktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Is_Active' => false,
        ]);
    }
}
