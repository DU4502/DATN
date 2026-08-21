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
use App\Services\OrderCancellationService;

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
                'order_code' => $notification->data['order_code'] ?? null,
                'url' => $this->orderNotificationUrl($notification->data['order_id'] ?? null),
                'status' => $notification->data['status'] ?? null,
                'status_label' => $notification->data['status_label'] ?? null,
                'updated_at' => $notification->data['updated_at'] ?? null,
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
     * Customer cancels their own order (only in pending status)
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        // Verify ownership
        if ($order->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền hủy đơn hàng này.');
        }

        // Only allow cancellation in PENDING status
        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::PENDING) {
            return redirect()->back()->with('error', 'Chỉ được hủy đơn hàng khi đang ở trạng thái "Chờ xác nhận".');
        }

        // Validate cancellation reason
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        // Dùng cùng một service hủy đơn với Admin/Staff để tồn kho, voucher,
        // shipment và trạng thái shipper luôn được dọn đồng bộ.
        $result = app(OrderCancellationService::class)->cancel(
            $order,
            (string) $request->cancellation_reason,
            $user
        );
        $order = $result['order'];

        // Send notification (through RealtimeOrderNotifier)
        \App\Support\RealtimeOrderNotifier::orderStatusUpdated($order);

        return redirect()->back()->with('success', 'Đơn hàng đã được hủy thành công.');
    }

    /**
     * Customer confirms order has been received (delivered → completed)
     */
    public function confirmReceived(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        // Verify ownership
        if ($order->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xác nhận đơn hàng này.');
        }

        // Only allow confirmation in DELIVERED status
        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::DELIVERED) {
            return redirect()->back()->with('error', 'Chỉ có thể xác nhận đơn hàng khi đang ở trạng thái "Đã giao".');
        }

        // Update order status to completed
        $order->status = OrderStatus::COMPLETED;
        $order->status_changed_at = now();
        $order->status_changed_by = $user->id;
        
        // Update payment status for COD orders
        if ($order->payment_method === 'cod' && $order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
        }
        
        $order->save();

        if ($order->payment_method === 'cod' && $order->shipper_id) {
            $shipper = \App\Models\Shipper::query()->find($order->shipper_id);
            if ($shipper) {
                app(\App\Services\ShipperCodService::class)->recordCollection($order->fresh(), $shipper);
            }
        }
        
        // Award loyalty points when customer confirms order
        $order->awardLoyaltyPoints();

        // Send notification (through RealtimeOrderNotifier)
        \App\Support\RealtimeOrderNotifier::orderStatusUpdated($order);

        return redirect()->back()->with('success', 'Cảm ơn bạn đã xác nhận! Đơn hàng đã hoàn thành.');
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
