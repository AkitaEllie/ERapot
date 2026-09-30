<?php

namespace Database\Factories;

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rapor>
 */
class RaporFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Siswa_ID' => Siswa::factory(),
            'Kelas_ID' => Kelas::factory(),
            'Disetujui_Oleh' => null,
            'Semester' => fake()->randomElement(Semester::cases()),
            'Status' => RaporStatus::BelumDiisi,
            'Tinggi_Badan' => fake()->randomFloat(1, 110, 170),
            'Berat_Badan' => fake()->randomFloat(1, 20, 80),
            'Lingkar_Kepala' => fake()->randomFloat(1, 45, 58),
            'Status_Pertumbuhan' => fake()->randomElement(['Normal', 'Kurang', 'Gemuk']),
            'Sakit' => fake()->numberBetween(0, 4),
            'Izin' => fake()->numberBetween(0, 4),
            'Tanpa_Keterangan' => fake()->numberBetween(0, 2),
            'Catatan_Revisi' => null,
            'Tanggal_Persetujuan' => null,
            'Tanggal_Cetak' => null,
            'File_PDF' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Status' => RaporStatus::Draft,
        ]);
    }

    public function menunggu(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Status' => RaporStatus::Menunggu,
        ]);
    }

    public function perluRevisi(string $catatan = 'Mohon lengkapi deskripsi capaian.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'Status' => RaporStatus::PerluRevisi,
            'Catatan_Revisi' => $catatan,
        ]);
    }

    public function disetujui(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'Status' => RaporStatus::Disetujui,
            'Disetujui_Oleh' => $user->getKey(),
            'Tanggal_Persetujuan' => now(),
        ]);
    }
}
