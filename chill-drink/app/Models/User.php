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
        'avatar',     // Thêm vào vì database có
        'phone',
        'password',
        'is_active',  // Thêm vào vì database có
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
        return $this->role_id == 1;
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
