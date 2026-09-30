<?php

namespace App\Models;

use App\Models\Concerns\HasShortSequenceKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property string $User_ID
 * @property string $Role_ID
 * @property string|null $NIP
 * @property string $Nama
 * @property string $Email
 * @property string $Password
 * @property string|null $No_Telepon
 * @property bool $Is_Active
 */
#[Fillable(['User_ID', 'Role_ID', 'NIP', 'Nama', 'Email', 'Password', 'No_Telepon', 'Is_Active'])]
#[Hidden(['Password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasShortSequenceKey, Notifiable;

    protected $primaryKey = 'User_ID';

    // Not on ERD/CD: maps the framework's "name" attribute onto the ERD's Nama
    // column so authentication, notifications and auth()->user()->name work.

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getAttributeValue('Nama'),
            set: fn (?string $value): array => ['Nama' => $value],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Password' => 'hashed',
            'Is_Active' => 'boolean',
        ];
    }

    protected static function codePrefix(): string
    {
        return 'U-';
    }

    /**
     * The ERD spells this column "Password", while the auth contract reads
     * "password", so point the contract at the real column.
     */
    public function getAuthPasswordName(): string
    {
        return 'Password';
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'Role_ID', 'Role_ID');
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelasDiampu(): HasMany
    {
        return $this->hasMany(Kelas::class, 'User_ID', 'User_ID');
    }

    /**
     * @return HasMany<Pembelajaran, $this>
     */
    public function pembelajaran(): HasMany
    {
        return $this->hasMany(Pembelajaran::class, 'User_ID', 'User_ID');
    }

    /**
     * @return HasMany<Rapor, $this>
     */
    public function raporDisetujui(): HasMany
    {
        return $this->hasMany(Rapor::class, 'Disetujui_Oleh', 'User_ID');
    }

    // Not on ERD/CD: query scope and initials helper added during scaffolding.

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('Is_Active', true);
    }

    public function initials(): string
    {
        $initials = Str::initials($this->Nama, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Verify the supplied credentials and sign this user in, as declared on the
     * class diagram.
     *
     * Returns false without touching the session when either the email or the
     * password does not match this record.
     */
    public function login(string $email, string $password): bool
    {
        if (! hash_equals($this->Email, $email)) {
            return false;
        }

        if (! Hash::check($password, $this->Password)) {
            return false;
        }

        Auth::login($this);

        return true;
    }

    /**
     * Ends the session for this user, as declared on the class diagram.
     *
     * Invalidating the session drops every attribute it held and issues a new id,
     * then the CSRF token is rotated, so a fixated session cannot survive a sign out.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * @todo Implement per the class diagram.
     */
    public function ubahPassword(string $password): void
    {
        //
    }
}
