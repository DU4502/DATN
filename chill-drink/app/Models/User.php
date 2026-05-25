<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Các trường được phép fill (Đã đồng bộ với database trong ảnh)
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'address',
        'points',
        'reset_token',
        'reset_expire',
        'area',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reset_expire' => 'datetime',
            'is_active' => 'boolean', // Tự động cast về true/false
        ];
    }

    /**
     * Generate and persist a one-time password reset token.
     */
    public function generatePasswordResetToken(int $ttlMinutes = 60): string
    {
        $plainToken = bin2hex(random_bytes(32));

        $this->forceFill([
            'reset_token' => hash('sha256', $plainToken),
            'reset_expire' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $plainToken;
    }

    /**
     * Determine whether the given reset token is still valid.
     */
    public function hasValidPasswordResetToken(string $plainToken): bool
    {
        if (blank($this->reset_token) || blank($this->reset_expire)) {
            return false;
        }

        return $this->reset_expire->isFuture()
            && hash_equals($this->reset_token, hash('sha256', $plainToken));
    }

    /**
     * Find a user by reset email/token pair.
     */
    public static function findForPasswordReset(string $email, string $plainToken): ?self
    {
        $user = static::where('email', $email)->first();

        return $user && $user->hasValidPasswordResetToken($plainToken) ? $user : null;
    }

    /**
     * Clear the password reset token once it is no longer valid.
     */
    public function clearPasswordResetToken(): void
    {
        $this->forceFill([
            'reset_token' => null,
            'reset_expire' => null,
        ])->save();
    }

    /**
     * Check if user is admin
     * Giả sử role_id = 1 là Admin (Bạn tự thay đổi số này theo logic của bạn nhé)
     */
    public function isAdmin(): bool
    {
        return (int) ($this->role_id ?? 1) === 2 || $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return ! $this->isAdmin();
    }

    public function scopeCustomers($query)
    {
        return $query->where('role_id', 1);
    }

    public function scopeAdmins($query)
    {
        return $query->where(function ($q) {
            $q->where('role_id', 2)->orWhere('role', 'admin');
        });
    }

    /**
     * The current database does not include Laravel's remember_token column.
     */
    public function getRememberToken()
    {
        return $this->attributes[$this->getRememberTokenName()] ?? null;
    }

    public function setRememberToken($value): void
    {
        if (array_key_exists($this->getRememberTokenName(), $this->attributes)) {
            $this->attributes[$this->getRememberTokenName()] = $value;
        }
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
