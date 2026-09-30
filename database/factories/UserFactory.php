<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Role_ID' => Role::query()->value('Role_ID') ?? Role::factory(),
            'NIP' => fake()->unique()->numerify('##########'),
            'Nama' => fake()->name(),
            'Email' => fake()->unique()->safeEmail(),
            'Password' => static::$password ??= Hash::make('password'),
            'No_Telepon' => fake()->numerify('08##########'),
            'Is_Active' => true,
        ];
    }

    public function tidakAktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'Is_Active' => false,
        ]);
    }

    public function denganRole(string $roleId): static
    {
        return $this->state(fn (array $attributes): array => [
            'Role_ID' => $roleId,
        ]);
    }
}
