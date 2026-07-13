<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'impersonated_by_id',
        'display_as_sender_id',
        'content',
        'attachment_path',
        'attachment_name',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function impersonatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_by_id');
    }

    public function displayAsSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'display_as_sender_id');
    }

    // Accessor để lấy sender hiển thị (cho impersonation)
    public function getDisplaySenderAttribute()
    {
        return $this->display_as_sender_id ? $this->displayAsSender : $this->sender;
    }

    // Accessor để kiểm tra có đang impersonation không
    public function getIsImpersonatedAttribute()
    {
        return !is_null($this->impersonated_by_id);
    }

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }
        return Storage::url($this->attachment_path);
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->toIso8601String();
    }
}
