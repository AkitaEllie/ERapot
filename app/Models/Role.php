<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $Role_ID
 * @property string $Nama_Role
 * @property string|null $Deskripsi
 */
#[Fillable(['Role_ID', 'Nama_Role', 'Deskripsi'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * Roles are a small, fixed set of reference data, so their keys are
     * readable codes ("ADMIN", "GURU") rather than generated ones.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'string';

    protected $primaryKey = 'Role_ID';

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'Role_ID', 'Role_ID');
    }

    /**
     * Capabilities granted per role. The key is the area a capability protects,
     * which is also the sidebar entry it matches.
     *
     * @var array<string, array<int, string>>
     */
    private const HAK_AKSES = [
        'ADMIN' => ['*'],

        'GURU' => [
            'dashboard.guru',
            'rapor',
            'indikator',
            'ekspor',
        ],

        'TATAUSA' => [
            'dashboard.tata-usaha',
            'tahun-ajaran',
            'pengguna',
            'siswa',
            'kelas',
            'penempatan',
            'mapel',
            'penugasan',
        ],

        'KEPSEKOL' => [
            'dashboard.progres',
            'review',
            'persetujuan',
        ],
    ];

    /**
     * The capabilities this role grants, as declared on the class diagram.
     *
     * @return array<int, string>
     */
    public function getHakAkses(): array
    {
        return self::HAK_AKSES[$this->Role_ID] ?? [];
    }

    /**
     * Whether this role grants the given capability. "*" is the wildcard held by
     * administrators.
     */
    public function punyaHakAkses(string $hakAkses): bool
    {
        $dimiliki = $this->getHakAkses();

        return in_array('*', $dimiliki, true) || in_array($hakAkses, $dimiliki, true);
    }
}
