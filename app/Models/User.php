<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar
 * @property UserStatus $status
 * @property bool $is_admin
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'status' => UserStatus::class,
            'is_admin' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Link, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    public function isAdminEmail(): bool
    {
        $admin = config('shortener.admin_email');

        return filled($admin) && strcasecmp($this->email, $admin) === 0;
    }

    /**
     * Aprueba la cuenta: desde ahora puede acortar y crear tokens de API.
     */
    public function approve(?User $by = null): void
    {
        $this->forceFill([
            'status' => UserStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $by?->id,
        ])->save();
    }

    /**
     * Rechaza (o revoca) la cuenta: pierde el acceso y sus tokens de API.
     */
    public function reject(?User $by = null): void
    {
        $this->forceFill([
            'status' => UserStatus::Rejected,
            'approved_at' => null,
            'approved_by' => $by?->id,
        ])->save();

        $this->tokens()->delete();
    }

    /**
     * Estado inicial de una cuenta nueva: la del admin entra aprobada y como admin;
     * el resto queda pendiente hasta que el admin la apruebe.
     */
    public function applyInitialStatus(): void
    {
        if ($this->isAdminEmail()) {
            $this->forceFill([
                'is_admin' => true,
                'status' => UserStatus::Approved,
                'approved_at' => now(),
            ])->save();
        }
    }
}
