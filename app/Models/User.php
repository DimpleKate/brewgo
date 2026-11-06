<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'avatar_path',
        'password',
        'is_admin',
        'is_active',
        'role',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->is_admin && $this->role === self::ROLE_STAFF;
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasRole(array $roles): bool
    {
        if (! $this->is_admin) {
            return false;
        }

        $role = $this->role ?: self::ROLE_ADMIN;

        return in_array($role, $roles, true);
    }

    public function roleLabel(): string
    {
        return (string) (config('security.admin.roles.'.($this->role ?: self::ROLE_ADMIN)) ?? ucfirst((string) $this->role));
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        $codes = $this->two_factor_recovery_codes;

        return is_array($codes) ? array_values($codes) : [];
    }

    public function useRecoveryCode(string $code): bool
    {
        $normalized = strtoupper(trim($code));
        $codes = $this->recoveryCodes();

        $index = array_search($normalized, array_map('strtoupper', $codes), true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }

    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, $this->isAdmin()));
    }

    public function avatarUrl(): ?string
    {
        if (! is_string($this->avatar_path) || trim($this->avatar_path) === '') {
            return null;
        }

        return asset($this->avatar_path);
    }

    public function avatarInitial(): string
    {
        return strtoupper(substr(trim($this->name) !== '' ? $this->name : $this->email, 0, 1));
    }

    public function hasPendingRequestFor(Order $order): bool
    {
        return $order->requests()
            ->where('status', OrderRequest::STATUS_PENDING)
            ->exists();
    }
}
