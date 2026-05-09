<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'email',
    'password',
    'municipio_id',
    'user_type_id',
    'staff_id',
    'users_type_legacy',
    'active_operational',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_operational' => 'boolean',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Central ampla / administrativo (legado users_type ≤ 2). */
    public function isOperationalCentral(): bool
    {
        return $this->users_type_legacy !== null && $this->users_type_legacy <= 2;
    }

    /**
     * Administrador da central — gestão de identidades/usuários do sistema.
     *
     * @see docs/migracao/entidades.md — `users_type <= 2` como acesso central; o perfil 1
     *      corresponde ao nível administrativo que mantém cadastro de usuários.
     */
    public function isCentralAdministrator(): bool
    {
        return $this->users_type_legacy === 1;
    }

    /** @return list<string> */
    public function operationalAbilities(): array
    {
        if ($this->isOperationalCentral()) {
            return ['*'];
        }

        $base = [
            'dispatch.view',
            'dispatch.assign_unit',
            'incident.advance_stage',
            'incident.close',
            'incident.create',
            'catalog.manage',
            'victim.record',
        ];

        if ($this->users_type_legacy === 4) {
            $base[] = 'victim.prescribe';
        }

        return $base;
    }

    public function hasOperationalAbility(string $ability): bool
    {
        $abilities = $this->operationalAbilities();

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Escopo operacional por `municipio_id` (docs/migracao). */
    public function canAccessOperationalMunicipio(?int $municipioId): bool
    {
        if ($municipioId === null) {
            return false;
        }

        if ($this->hasOperationalAbility('*')) {
            return true;
        }

        return $this->municipio_id !== null && (int) $this->municipio_id === $municipioId;
    }
}
