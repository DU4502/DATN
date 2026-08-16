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
    }
}
