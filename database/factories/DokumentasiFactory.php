<?php

namespace Database\Factories;

use App\Models\Dokumentasi;
use App\Models\Narasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dokumentasi>
 */
class DokumentasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Narasi_ID' => Narasi::factory(),
            'Foto' => 'dokumentasi/'.fake()->uuid().'.jpg',
            'Keterangan' => fake()->sentence(),
        ];
    }

    public function untukNarasi(Narasi $narasi): static
    {
        return $this->state(fn (array $attributes): array => [
            'Narasi_ID' => $narasi->getKey(),
        ]);
    }
}
