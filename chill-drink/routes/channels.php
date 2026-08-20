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

// Sự cố giao hàng: Admin chỉ nghe đúng chi nhánh của mình; Super Admin có kênh toàn hệ thống.
Broadcast::channel('branch-admin-incidents.{branchId}', function ($user, $branchId) {
    return $user
        && $user->isAdmin()
        && is_numeric($user->branch_id)
        && (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('super-admin-incidents', function ($user) {
    return $user && $user->isSuperAdmin();
});
