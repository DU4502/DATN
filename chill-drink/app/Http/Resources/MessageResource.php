<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Models\User;

class MessageResource
{
    public static function toPublicArray(Message $message): array
    {
        $message->loadMissing(['sender', 'displayAsSender', 'impersonatedBy']);
        $display = $message->display_sender;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $display?->id,
            'content' => $message->content,
            'attachment_path' => $message->attachment_path,
            'attachment_name' => $message->attachment_name,
            'attachment_url' => $message->attachment_url,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at->toIso8601String(),
            'sender' => [
                'id' => $display?->id,
                'name' => $display?->name ?? 'Hệ thống',
                'avatar' => $display?->avatar,
            ],
        ];
    }

    public static function toStaffArray(Message $message, User $viewer): array
    {
        $payload = self::toPublicArray($message);

        if ($viewer->canMonitorChat() && $message->is_impersonated) {
            $message->loadMissing(['sender', 'impersonatedBy']);

            $payload['is_impersonated'] = true;
            $payload['actual_sender'] = [
                'id' => $message->sender_id,
                'name' => $message->sender->name,
            ];
            $payload['impersonated_by'] = [
                'id' => $message->impersonatedBy->id,
                'name' => $message->impersonatedBy->name,
            ];
        }

        return $payload;
    }

    public static function toBroadcastArray(Message $message): array
    {
        $message->loadMissing(['sender', 'displayAsSender']);
        $display = $message->display_sender;

        return [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $display->id,
            'sender_name' => $display->name,
            'content' => $message->content,
            'attachment_path' => $message->attachment_path,
            'attachment_name' => $message->attachment_name,
            'attachment_url' => $message->attachment_url,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
