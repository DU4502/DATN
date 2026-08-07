<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\GuestOrderAccess;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class GuestConvertController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $convert = session('guest_convert');

        if (empty($convert)) {
            throw ValidationException::withMessages([
                'password' => 'Không tìm thấy thông tin đơn hàng để tạo tài khoản.',
            ]);
        }

        $order = Order::query()->find($convert['order_id'] ?? 0);

        if (! $order || ! $order->isGuest()) {
            throw ValidationException::withMessages([
                'password' => 'Đơn hàng không còn khả dụng để chuyển đổi tài khoản.',
            ]);
        }

        abort_unless(GuestOrderAccess::canView($order), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        $email = strtolower((string) ($convert['email'] ?? ''));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'password' => 'Email này đã có tài khoản. Vui lòng đăng nhập để liên kết đơn hàng.',
            ]);
        }

        $user = DB::transaction(function () use ($convert, $validated, $order, $email) {
            $userData = [
                'name' => (string) ($convert['name'] ?? ''),
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role_id' => 1,
                'is_active' => 1,
            ];

            if (Schema::hasColumn('users', 'phone')) {
                $userData['phone'] = (string) ($convert['phone'] ?? '');
            }

            $user = User::create($userData);

            $order->update([
                'user_id' => $user->id,
            ]);

            $this->awardLoyaltyPoints($user->id, $order);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        session()->forget(['guest_convert', "guest_order_tokens.{$order->id}"]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tài khoản đã được tạo thành công.',
                'redirect' => route('orders.index'),
            ]);
        }

        return redirect()
            ->route('orders.index')
            ->with('success', 'Tài khoản đã được tạo! Điểm tích lũy từ đơn hàng vừa rồi đã được cộng vào tài khoản.');
    }

    private function awardLoyaltyPoints(int $userId, Order $order): void
    {
        if (! Schema::hasTable('loyalty_points')) {
            return;
        }

        $points = $order->pointsEarnable();

        if ($points <= 0) {
            return;
        }

        \App\Models\LoyaltyPoint::getOrCreateForUser($userId)->addPoints(
            points: $points,
            type: 'earn',
            description: "Tích điểm từ đơn hàng chuyển đổi #{$order->id}",
            referenceType: 'order',
            referenceId: $order->id
        );
    }
}
