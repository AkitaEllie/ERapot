<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * The application's four built-in roles, keyed by their readable code.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'ADMIN' => 'Administrator',
        'GURU' => 'Guru',
        'TATAUSA' => 'Tata Usaha',
        'KEPSEKOL' => 'Kepala Sekolah',
    ];

    /**
     * Roles are reference data, so the default is a throwaway code that cannot
     * collide. Use bawaan() for one of the built-in roles.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Role_ID' => strtoupper(fake()->unique()->bothify('??##??')),
            // Kept short because roles.Nama_Role is a 25-character column; two random
            // words overflowed it intermittently and made the suite flaky.
            'Nama_Role' => 'Role-'.fake()->unique()->numerify('#####'),
            'Deskripsi' => fake()->sentence(),
        ];
    }

    /**
     * One of the roles the application ships with.
     */
    public function bawaan(string $code = 'GURU'): static
    {
        return $this->state(fn (array $attributes): array => [
            'Role_ID' => $code,
            'Nama_Role' => self::ROLES[$code],
        ]);
    }
}
