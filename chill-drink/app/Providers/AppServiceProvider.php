<?php

namespace App\Providers;

use App\Models\GroupOrder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;

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
        // Super Admin là quyền cao nhất toàn hệ thống. Mọi Policy/Gate khai báo
        // hiện tại hoặc bổ sung sau này đều mặc định cho phép Super Admin.
        // Các controller vẫn chịu trách nhiệm đồng bộ dữ liệu nghiệp vụ khi override.
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                ? true
                : null;
        });

        Paginator::useBootstrapFive();

        View::composer('layouts.client', function ($view) {
            $pendingCheckoutGroup = auth()->check() && session()->has('checkout_group_order_id')
                ? GroupOrder::query()
                    ->whereKey(session('checkout_group_order_id'))
                    ->where('owner_id', auth()->id())
                    ->where('status', 'closed')
                    ->whereNull('order_id')
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

        // Super Admin dùng chung layout ở nhiều controller khác nhau (Đơn hàng, Sự cố, Nhân viên...).
        // Cấp notification ở layout-level để chuông luôn có dữ liệu, không phụ thuộc từng controller.
        View::composer('layouts.super-admin', function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }

            try {
                $view->with([
                    'notifications' => $user->notifications()->latest()->limit(8)->get(),
                    'unreadNotificationCount' => $user->unreadNotifications()->count(),
                ]);
            } catch (\Throwable $exception) {
                $view->with([
                    'notifications' => collect(),
                    'unreadNotificationCount' => 0,
                ]);
            }
        });
    }
}
