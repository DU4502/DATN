<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
                $statusKey = $this->normalizeOrderStatus((string) $order->status);
                $displayTotal = $this->resolveOrderDisplayTotal($order);

                $order->setAttribute('status_display_key', $statusKey);
                $order->setAttribute('display_total', $displayTotal);

                return $order;
            });

        return [
            'profileOrders' => $profileOrders,
            'orderStatusLabels' => [
                'pending' => ['label' => 'Chờ xử lý', 'class' => 'order-status-pending'],
                'processing' => ['label' => 'Đang xử lý', 'class' => 'order-status-processing'],
                'shipping' => ['label' => 'Đang giao', 'class' => 'order-status-shipping'],
                'completed' => ['label' => 'Hoàn tất', 'class' => 'order-status-completed'],
                'cancelled' => ['label' => 'Đã hủy', 'class' => 'order-status-cancelled'],
            ],
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

    private function normalizeOrderStatus(string $status): string
    {
        return match ($status) {
            'preparing' => 'processing',
            'shipped', 'delivering' => 'shipping',
            default => $status,
        };
    }

    private function resolveOrderDisplayTotal(Order $order): int
    {
        if (is_numeric($order->total ?? null)) {
            return (int) $order->total;
        }

        if (is_numeric($order->total_price ?? null)) {
            return (int) $order->total_price;
        }

        return (int) $order->orderItems->sum(fn ($item) => (int) $item->getSubtotal());
    }
}
