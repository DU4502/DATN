<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeepSuperAdminContext
{
    /**
     * Map normal admin GET pages to their Super Admin workspace aliases.
     *
     * Write actions intentionally stay on the existing admin routes. After a
     * controller redirects back to one of these GET pages, this middleware
     * moves the browser back under /admin/super-admin/... automatically.
     */
    private const ROUTE_MAP = [
        'admin.vouchers.index' => 'admin.super-admin.manage.vouchers.index',
        'admin.vouchers.create' => 'admin.super-admin.manage.vouchers.create',
        'admin.vouchers.edit' => 'admin.super-admin.manage.vouchers.edit',

        'admin.toppings.index' => 'admin.super-admin.manage.toppings.index',

        'admin.products.index' => 'admin.super-admin.manage.products.index',
        'admin.products.create' => 'admin.super-admin.manage.products.create',
        'admin.products.show' => 'admin.super-admin.manage.products.show',
        'admin.products.edit' => 'admin.super-admin.manage.products.edit',
        'admin.products.trash' => 'admin.super-admin.manage.products.trash',

        'admin.categories.index' => 'admin.super-admin.manage.categories.index',
        'admin.categories.create' => 'admin.super-admin.manage.categories.create',
        'admin.categories.edit' => 'admin.super-admin.manage.categories.edit',
        'admin.categories.trash' => 'admin.super-admin.manage.categories.trash',

        'admin.slides.index' => 'admin.super-admin.manage.slides.index',
        'admin.slides.trash' => 'admin.super-admin.manage.slides.trash',

        'admin.orders.index' => 'admin.super-admin.manage.orders.index',
        'admin.shipper-incidents.index' => 'admin.super-admin.manage.shipper-incidents.index',

        'admin.group-orders.index' => 'admin.super-admin.manage.group-orders.index',
        'admin.group-orders.show' => 'admin.super-admin.manage.group-orders.show',

        'admin.reviews.index' => 'admin.super-admin.manage.reviews.index',

        'admin.users.index' => 'admin.super-admin.manage.users.index',
        'admin.users.show' => 'admin.super-admin.manage.users.show',
        'admin.users.edit' => 'admin.super-admin.manage.users.edit',

        'admin.staff.index' => 'admin.super-admin.manage.staff.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        $routeName = $route?->getName();

        /*
         * A URL under /admin/super-admin is an explicit request to work in
         * the Super Admin workspace. Clear any stale "preview Admin"
         * session left from a previous visit before the view decides which
         * layout to extend. Without this, preferredAdminLayout() can choose
         * layouts.admin even though the browser is already on a
         * /admin/super-admin/... URL.
         */
        if (
            $user
            && $user->isSuperAdmin()
            && (
                $routeName === 'admin.super-admin'
                || str_starts_with((string) $routeName, 'admin.super-admin.manage.')
            )
        ) {
            $request->session()->forget([
                'super_admin_admin_view',
                'super_admin_preview_branch_id',
            ]);

            return $next($request);
        }

        if (
            ! $request->isMethod('GET')
            || ! $user
            || ! $user->isSuperAdmin()
            || $user->isViewingAdminWorkspace()
        ) {
            return $next($request);
        }

        $targetRoute = $routeName ? (self::ROUTE_MAP[$routeName] ?? null) : null;

        if (! $targetRoute) {
            return $next($request);
        }

        $parameters = array_merge($route->parameters(), $request->query());

        return redirect()->to(route($targetRoute, $parameters));
    }
}
