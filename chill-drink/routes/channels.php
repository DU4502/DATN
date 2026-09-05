<?php

use App\Models\Conversation;
use App\Models\GroupOrder;
use App\Models\Order;
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
    return $user && $user->isSuperAdmin();
});

Broadcast::channel('admin-notifications.{branchId}', function ($user, $branchId) {
    return $user
        && ($user->isSuperAdmin() || (
            ($user->isAdmin() || $user->isStaffOnly())
            && is_numeric($user->branch_id)
            && (int) $user->branch_id === (int) $branchId
        ));
});

Broadcast::channel('staff-orders.{branchId}', function ($user, $branchId) {
    return $user
        && $user->isStaffOnly()
        && is_numeric($user->branch_id)
        && (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('super-admin-orders', function ($user) {
    return $user && $user->isSuperAdmin();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return $user && Order::query()
        ->whereKey((int) $orderId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('shipper-orders.{userId}', function ($user, $userId) {
    return $user
        && $user->isShipper()
        && (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    if ((int) $user->id === (int) $conversation->user_id || $user->isSuperAdmin()) {
        return true;
    }

    if (! is_numeric($conversation->branch_id)
        || ! is_numeric($user->branch_id)
        || (int) $conversation->branch_id !== (int) $user->branch_id) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    return ($user->isCskh() || $user->isStaffOnly())
        && ($conversation->cskh_id === null || (int) $conversation->cskh_id === (int) $user->id);
});

Broadcast::channel('group-order.{groupOrderId}', function ($user, $groupOrderId) {
    $groupOrder = GroupOrder::find($groupOrderId);
    if (! $groupOrder) {
        return false;
    }

    if ((int) $groupOrder->owner_id === (int) $user->id
        || $groupOrder->members()->where('user_id', $user->id)->exists()
        || $user->isSuperAdmin()) {
        return true;
    }

    return ($user->isAdmin() || $user->isStaffOnly())
        && is_numeric($user->branch_id)
        && (int) $user->branch_id === (int) $groupOrder->branch_id;
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
