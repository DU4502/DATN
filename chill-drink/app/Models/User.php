<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'points',
        'reset_token',
        'reset_expire',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

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
            'reset_expire' => 'datetime',
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
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get all orders for the user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all reviews for the user
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
