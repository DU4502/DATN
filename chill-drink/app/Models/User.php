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
        'role_id',    // Thay cho 'role'
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean', // Tự động cast về true/false
        ];
    }

    /**
     * Check if user is admin
     * Giả sử role_id = 1 là Admin (Bạn tự thay đổi số này theo logic của bạn nhé)
     */
    public function isAdmin(): bool
    {
        return (int) ($this->role_id ?? 1) === 2;
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
