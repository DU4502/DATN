<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Channel for admin users - receives new order notifications
Broadcast::channel('admin-notifications', function ($user) {
    return $user && $user->isAdmin();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    if (!$conversation) {
        return false;
    }

    return (int) $user->id === (int) $conversation->user_id
        || (int) $user->id === (int) $conversation->cskh_id
        || $user->isAdmin();  // admin và super admin đều được xem
});

Broadcast::channel('group-order.{groupOrderId}', function ($user, $groupOrderId) {
    $groupOrder = \App\Models\GroupOrder::find($groupOrderId);
    if (! $groupOrder) {
        return false;
    }

    return $user->isAdmin()
        || (int) $groupOrder->owner_id === (int) $user->id
        || $groupOrder->members()->where('user_id', $user->id)->exists();
});
