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
        'role_id',
        'phone',
        'address',
        'area',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * Check if user is admin
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
