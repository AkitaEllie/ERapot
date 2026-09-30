<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The fixed set of roles the school application recognises.
 */
class RoleSeeder extends Seeder
{
    /**
     * @var array<string, array{role_id: string, nama: string, deskripsi: string}>
     */
    public const ROLES = [
        'ADMIN' => [
            'role_id' => 'ADMIN',
            'nama' => 'Administrator',
            'deskripsi' => 'Mengelola seluruh data aplikasi.',
        ],
        'GURU' => [
            'role_id' => 'GURU',
            'nama' => 'Guru',
            'deskripsi' => 'Mengisi nilai dan menyusun narasi rapor.',
        ],
        'TATAUSA' => [
            'role_id' => 'TATAUSA',
            'nama' => 'Tata Usaha',
            'deskripsi' => 'Mengelola akun, siswa, kelas, dan mata pelajaran.',
        ],
        'KEPSEKOL' => [
            'role_id' => 'KEPSEKOL',
            'nama' => 'Kepala Sekolah',
            'deskripsi' => 'Meninjau dan menyetujui rapor.',
        ],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::query()->updateOrCreate(
                ['Role_ID' => $role['role_id']],
                ['Nama_Role' => $role['nama'], 'Deskripsi' => $role['deskripsi']],
            );
        }
    }
}
