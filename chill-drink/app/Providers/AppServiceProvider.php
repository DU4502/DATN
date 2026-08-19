<?php

namespace App\Providers;

use App\Models\GroupOrder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.client', function ($view) {
            $sessionGroupId = auth()->check() ? session('checkout_group_order_id') : null;
            $sessionCheckoutGroup = $sessionGroupId
                ? GroupOrder::query()->find($sessionGroupId)
                : null;

            // Không để trạng thái checkout tạm của tab cũ tồn tại sau khi chủ
            // nhóm đã tạo đơn (hoặc khi phiên này không thuộc chủ nhóm).
            if ($sessionGroupId && (! $sessionCheckoutGroup
                || (int) $sessionCheckoutGroup->owner_id !== (int) auth()->id()
                || $sessionCheckoutGroup->status !== 'closed'
                || $sessionCheckoutGroup->order_id)) {
                session()->forget(['checkout_group_order_id', 'group_cart_keys', 'group_branch_id']);
            }

            // Đơn nhóm đã chốt là dữ liệu dùng chung cho mọi phiên đăng nhập,
            // không chỉ phiên trình duyệt vừa chốt đơn. Vì vậy nếu session chưa
            // có giỏ tạm, vẫn tìm đơn chờ thanh toán của chính chủ nhóm để hiện
            // nút "Tiếp tục thanh toán".
            $pendingCheckoutGroup = auth()->check()
                ? GroupOrder::query()
                    ->with('order')
                    ->where('owner_id', auth()->id())
                    ->where('locked_at', '>=', now()->subMinutes(15))
                    ->where(function ($query) {
                        $query->where(function ($pending) {
                            $pending->where('status', 'closed')->whereNull('order_id');
                        })->orWhere(function ($payment) {
                            $payment->where('status', 'ordered')
                                ->whereHas('order', fn ($order) => $order
                                    ->where('payment_method', 'vnpay')
                                    ->whereIn('payment_status', ['pending', 'failed'])
                                    ->whereIn('status', ['awaiting_payment', 'pending'])
                                    ->where('created_at', '>=', now()->subMinutes(15)));
                        });
                    })
                    ->when(session()->has('checkout_group_order_id'), function ($query) {
                        $query->orderByRaw('id = ? desc', [(int) session('checkout_group_order_id')]);
                    })
                    ->latest('locked_at')
                    ->latest('id')
                    ->first()
                : null;
            $activeOwnedGroup = auth()->check()
                ? GroupOrder::query()
                    ->withCount('members')
                    ->where(function ($query) {
                        $query->where('owner_id', auth()->id())
                            ->orWhereHas('members', fn ($members) => $members->where('user_id', auth()->id()));
                    })
                    ->where('status', 'open')
                    ->where('closes_at', '>', now())
                    ->latest('id')
                    ->first()
                : null;

            $view->with(compact('activeOwnedGroup', 'pendingCheckoutGroup'));
        });
    }
}
