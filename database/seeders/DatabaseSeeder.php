<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The demo seeder is development-only data, and it is what makes a fresh
     * clone usable — without it there are no accounts to sign in with. It seeds
     * the roles itself, so RoleSeeder runs through it.
     *
     * Model events stay enabled on purpose. Laravel's WithoutModelEvents trait
     * would silence them for the whole run, and IndikatorCapaian derives its
     * Kode_KD in a creating hook, so the inserts would fail on a NOT NULL
     * column.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
