<?php

namespace Database\Factories;

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\ProgramPengembangan;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndikatorCapaian>
 */
class IndikatorCapaianFactory extends Factory
{
    /**
     * Kode_KD is deliberately absent so the model can derive it from the
     * generated Indikator_ID, keeping the teacher-facing code sequential.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Program_ID' => ProgramPengembangan::factory(),
            'TahunAjaran_ID' => TahunAjaran::factory(),
            'Jenjang' => fake()->randomElement(Jenjang::cases()),
            'Semester' => fake()->randomElement(Semester::cases()),
            'Tipe' => fake()->randomElement(TipeIndikator::cases()),
            'Deskripsi' => fake()->sentence(),
        ];
    }

    public function untukJenjang(Jenjang $jenjang): static
    {
        return $this->state(fn (array $attributes): array => [
            'Jenjang' => $jenjang,
        ]);
    }

    public function untukSemester(Semester $semester): static
    {
        return $this->state(fn (array $attributes): array => [
            'Semester' => $semester,
        ]);
    }
}
