<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Review;
use App\Support\OrderStatus;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Display the user's order history outside the profile form.
     */
    public function orders(Request $request): View
    {
        $orderHistoryData = $this->orderHistoryData($request);

        return view('profile.orders', [
            'user' => $request->user(),
            'profileOrders' => $orderHistoryData['profileOrders'],
            'orderStatusLabels' => $orderHistoryData['orderStatusLabels'],
            'paymentLabels' => $orderHistoryData['paymentLabels'],
        ]);
    }

    public function notificationsFeed(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Thông báo',
                'message' => $notification->data['message'] ?? '',
                'type' => $notification->data['type'] ?? null,
                'icon' => OrderStatus::notificationIconByType($notification->data['type'] ?? null),
                'order_id' => $notification->data['order_id'] ?? null,
                'url' => $this->orderNotificationUrl($notification->data['order_id'] ?? null),
                'status' => $notification->data['status'] ?? null,
                'status_label' => $notification->data['status_label'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc',
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        unset($data['avatar_file']);

        if ($request->hasFile('avatar_file')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'preset-')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar_file')->store('avatars', 'public');
        }

        $user->fill($data);

        if ($user->isDirty('email') && Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function orderHistoryData(Request $request): array
    {
        $profileOrders = $request->user()
            ->orders()
            ->with(['orderItems.product.category'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function (Order $order) {
                $statusKey = OrderStatus::normalize((string) $order->status);
                $displayTotal = $this->resolveOrderDisplayTotal($order);

                $order->setAttribute('status_display_key', $statusKey);
                $order->setAttribute('display_total', $displayTotal);

                return $order;
            });

        // Attach a simple map of reviewed products for the current user per order
        $orderIds = $profileOrders->pluck('id')->filter()->all();
        $reviewedMap = [];

        if (! empty($orderIds)) {
            $userId = $request->user()->id;
            $reviews = Review::query()
                ->where('user_id', $userId)
                ->whereIn('order_id', $orderIds)
                ->get();

            foreach ($reviews as $r) {
                $reviewedMap[$r->order_id][$r->product_id] = true;
            }

            foreach ($profileOrders as $order) {
                $order->setAttribute('reviewed_products', $reviewedMap[$order->id] ?? []);
            }
        }

        return [
            'profileOrders' => $profileOrders,
            'orderStatusLabels' => OrderStatus::userBadgeStyles(),
            'paymentLabels' => [
                'cod' => 'Tiền mặt (COD)',
                'bank_transfer' => 'Chuyển khoản',
                'momo' => 'MoMo',
                'vnpay' => 'VNPay',
                'card' => 'Thẻ',
                'wallet' => 'Ví điện tử',
            ],
        ];
    }

    private function orderNotificationUrl(mixed $orderId): string
    {
        if (! is_numeric($orderId)) {
            return route('orders.index');
        }

        return route('orders.index', ['order' => (int) $orderId]);
    }

    private function normalizeOrderStatus(string $status): string
    {
        return OrderStatus::normalize($status);
    }

    private function resolveOrderDisplayTotal(Order $order): int
    {
        if (is_numeric($order->total ?? null)) {
            return (int) $order->total;
        }

        if (is_numeric($order->total_price ?? null)) {
            return (int) $order->total_price;
        }

        return (int) $order->orderItems->sum(fn($item) => (int) $item->getSubtotal());
    }
}
