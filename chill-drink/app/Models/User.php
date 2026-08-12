<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * Email của Super Admin.
     */
    public const SUPER_ADMIN_EMAIL = 'superadmin@chilldrink.com';

    /**
     * Các trường được phép mass assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'plain_password',
        'google_id',
        'facebook_id',
        'role_id',
        'phone',
        'address',
        'area',
        'latitude',
        'longitude',
        'branch_id',
        'reset_token',
        'reset_expire',
        'avatar',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Các trường không hiển thị khi serialize.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reset_expire' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Tạo token reset password.
     */
    public function generatePasswordResetToken(
        int $ttlMinutes = 60
    ): string {
        $plainToken = bin2hex(random_bytes(32));

        $this->forceFill([
            'reset_token' => hash('sha256', $plainToken),
            'reset_expire' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $plainToken;
    }

    /**
     * Kiểm tra token reset password.
     */
    public function hasValidPasswordResetToken(
        string $plainToken
    ): bool {
        if (
            blank($this->reset_token) ||
            blank($this->reset_expire)
        ) {
            return false;
        }

        return $this->reset_expire->isFuture()
            && hash_equals(
                $this->reset_token,
                hash('sha256', $plainToken)
            );
    }

    /**
     * Tìm user theo email và reset token.
     */
    public static function findForPasswordReset(
        string $email,
        string $plainToken
    ): ?self {
        $user = static::where('email', $email)->first();

        return $user &&
            $user->hasValidPasswordResetToken($plainToken)
            ? $user
            : null;
    }

    /**
     * Xóa token reset password.
     */
    public function clearPasswordResetToken(): void
    {
        $this->forceFill([
            'reset_token' => null,
            'reset_expire' => null,
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE
    |--------------------------------------------------------------------------
    |
    | role_id:
    | 1 = Customer
    | 2 = Admin
    | 3 = Super Admin
    | 4 = CSKH
    | 5 = Shipper
    |
    */

    /**
     * Kiểm tra Admin.
     */
    public function isAdmin(): bool
    {
        return (int) ($this->role_id ?? 1) === 2;
    }

    /**
     * Kiểm tra Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return (int) ($this->role_id ?? 1) === 3
<<<<<<< HEAD
            || strcasecmp(
                (string) $this->email,
                self::SUPER_ADMIN_EMAIL
            ) === 0;
=======
            || strcasecmp((string) $this->email, self::SUPER_ADMIN_EMAIL) === 0;
    }

    public function isViewingAdminWorkspace(): bool
    {
        return $this->isSuperAdmin() && (bool) session('super_admin_admin_view', false);
    }

    public function adminWorkspaceBranchId(): ?int
    {
        if (! $this->isViewingAdminWorkspace()) {
            return null;
        }

        $branchId = session('super_admin_preview_branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function preferredAdminLayout(): string
    {
        return $this->isSuperAdmin() && ! $this->isViewingAdminWorkspace()
            ? 'layouts.super-admin'
            : 'layouts.admin';
    }

    public function canMonitorChat(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canImpersonateInChat(): bool
    {
        return $this->isSuperAdmin();
>>>>>>> 5b3381183eab985031f200b21f0067dbfdec9944
    }

    /**
     * Kiểm tra CSKH.
     */
    public function isCskh(): bool
    {
        return (int) ($this->role_id ?? 1) === 4;
    }

    /**
<<<<<<< HEAD
     * Kiểm tra Shipper.
     */
    public function isShipper(): bool
    {
        return (int) ($this->role_id ?? 1) === 5;
=======
     * Nhân viên (role_id = 5): có quyền chat, đổi trạng thái đơn hàng/đơn nhóm
     */
    public function isStaffOnly(): bool
    {
        return (int) ($this->role_id ?? 1) === 5;
    }

    public function isStaff(): bool
    {
        return in_array((int) ($this->role_id ?? 1), [2, 3, 4, 5], true);
    }

    /**
     * Có thể quản lý đơn hàng: admin (2,3) hoặc nhân viên (5)
     */
    public function canManageOrders(): bool
    {
        return in_array((int) ($this->role_id ?? 1), [2, 3, 5], true);
    }

    /**
     * Có thể truy cập khu vực staff panel
     */
    public function canAccessStaffPanel(): bool
    {
        return $this->isStaffOnly();
>>>>>>> 5b3381183eab985031f200b21f0067dbfdec9944
    }

    /**
     * Kiểm tra nhân viên.
     */
    public function isStaff(): bool
    {
        return in_array(
            (int) ($this->role_id ?? 1),
            [2, 3, 4],
            true
        );
    }

    /**
     * Kiểm tra khách hàng.
     */
    public function isCustomer(): bool
    {
        return ! $this->isStaff()
            && ! $this->isShipper();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeCustomers($query)
    {
        return $query->where('role_id', 1);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role_id', [2, 3]);
    }

    public function scopeShippers($query)
    {
        return $query->where('role_id', 5);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * User thuộc Branch.
     */
    public function branch()
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id'
        );
    }

    /**
     * User có một hồ sơ Shipper.
     */
    public function shipper()
    {
        return $this->hasOne(
            Shipper::class,
            'user_id'
        );
    }

    /**
     * Voucher.
     */
    public function vouchers()
    {
        return $this->hasMany(UserVoucher::class);
    }

    /**
     * Đơn hàng.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Địa chỉ.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Review.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Conversation của user.
     */
    public function conversations()
    {
        return $this->hasMany(
            Conversation::class,
            'user_id'
        );
    }

    /**
     * Conversation do CSKH phụ trách.
     */
    public function cskhConversations()
    {
        return $this->hasMany(
            Conversation::class,
            'cskh_id'
        );
    }

    /**
     * Điểm tích lũy.
     */
    public function loyaltyPoint()
    {
        return $this->hasOne(LoyaltyPoint::class);
    }

    /**
     * Lịch sử giao dịch điểm.
     */
    public function pointTransactions()
    {
        return $this->hasMany(
            PointTransaction::class
        );
    }

    /**
     * Đếm tin nhắn chưa đọc.
     */
    public function unreadConversationMessagesCount(): int
    {
        if ($this->isCustomer()) {

            $conversationIds = $this->conversations()
                ->where('status', 'open')
                ->pluck('id');

            return Message::whereIn(
                'conversation_id',
                $conversationIds
            )
                ->where(
                    'sender_id',
                    '!=',
                    $this->id
                )
                ->where('is_read', false)
                ->count();
        }

<<<<<<< HEAD
        $query = Conversation::whereHas(
            'user',
            fn($query) => $query->customers()
        )
=======
        // For staff: chỉ đếm tin nhắn từ KHÁCH HÀNG (role_id = 1) chưa đọc.
        // Không đếm tin nhắn từ staff khác để tránh đếm nhầm sau khi fix markMessagesAsRead.
        if (!$this->isSuperAdmin() && !$this->branch_id) {
            return 0;
        }

        $query = Conversation::whereHas('user', fn ($c) => $c->customers())
>>>>>>> 5b3381183eab985031f200b21f0067dbfdec9944
            ->where('status', 'open');

        if (! $this->isSuperAdmin()) {

            if ($this->branch_id) {
                $query->where(
                    'branch_id',
                    $this->branch_id
                );
            }
<<<<<<< HEAD

            if (
                $this->isCskh() &&
                ! $this->isAdmin()
            ) {
=======
            if (($this->isCskh() || $this->isStaffOnly()) && !$this->isAdmin()) {
>>>>>>> 5b3381183eab985031f200b21f0067dbfdec9944
                $query->where(function ($inner) {
                    $inner->whereNull('cskh_id')
                        ->orWhere(
                            'cskh_id',
                            $this->id
                        );
                });
            }
        }

        $conversationIds = $query->pluck('id');

        $customerIds = User::where(
            'role_id',
            1
        )->pluck('id');

        return Message::whereIn(
            'conversation_id',
            $conversationIds
        )
            ->whereIn(
                'sender_id',
                $customerIds
            )
            ->where('is_read', false)
            ->count();
    }

    /**
     * Laravel Remember Token.
     */
    public function getRememberToken()
    {
        if (! $this->getRememberTokenName()) {
            return null;
        }

        return $this->attributes[$this->getRememberTokenName()] ?? null;
    }

    public function setRememberToken($value): void
    {
        $rememberTokenName =
            $this->getRememberTokenName();

        if (
            $rememberTokenName &&
            array_key_exists(
                $rememberTokenName,
                $this->attributes
            )
        ) {
            $this->attributes[$rememberTokenName] = $value;
        }
    }
}
