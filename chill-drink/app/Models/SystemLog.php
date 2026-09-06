<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_email',
        'action',
        'category',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        ?User $user,
        string $action,
        string $category = 'system',
        string $status = 'success',
        array $metadata = [],
        ?string $actorEmail = null,
    ): ?self {
        if (! Schema::hasTable('system_logs')) {
            return null;
        }

        return self::create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? ($actorEmail ? 'Khách' : 'Hệ thống'),
            'actor_email' => $user?->email ?? $actorEmail,
            'action' => $action,
            'category' => $category,
            'status' => $status,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
