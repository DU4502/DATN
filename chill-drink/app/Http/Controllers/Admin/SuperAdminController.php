<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        $adminQuery = User::admins();
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');
        $role = (string) $request->query('role', 'all');
        $created = (string) $request->query('created', 'all');

        if ($search !== '') {
            $adminQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $adminQuery->where('is_active', true);
        } elseif ($status === 'locked') {
            $adminQuery->where('is_active', false);
        }

        if ($role === 'super') {
            $adminQuery->where('role_id', 3);
        } elseif ($role === 'admin') {
            $adminQuery->where('role_id', 2);
        }

        if ($created === 'today') {
            $adminQuery->whereDate('created_at', today());
        } elseif ($created === 'week') {
            $adminQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($created === 'month') {
            $adminQuery->where('created_at', '>=', now()->subDays(30));
        }

        $adminUsers = $adminQuery
            ->orderByDesc('is_active')
            ->orderByDesc('last_login_at')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAdmins = User::admins()->get();
        $orderStats = $this->orderStats();

        return view('admin.super-admin', [
            'adminUsers' => $adminUsers,
            'adminCount' => $allAdmins->count(),
            'activeAdminCount' => $allAdmins->where('is_active', true)->count(),
            'totalUserCount' => User::count(),
            'customerCount' => User::customers()->count(),
            'productCount' => Schema::hasTable('products') ? Product::count() : 0,
            'categoryCount' => Schema::hasTable('categories') ? Category::count() : 0,
            'branchCount' => Schema::hasTable('branches') ? Branch::count() : 0,
            'roleCount' => Schema::hasTable('roles') ? DB::table('roles')->count() : 0,
            'orderStats' => $orderStats,
            'revenueChart' => $this->revenueChart(),
            'userChart' => $this->userChart(),
            'activityLogs' => Schema::hasTable('system_logs')
                ? SystemLog::latest()->limit(8)->get()
                : collect(),
            'securityStats' => $this->securityStats(),
            'systemHealth' => $this->systemHealth(),
            'notifications' => $request->user()->notifications()->latest()->limit(5)->get(),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            'filters' => compact('search', 'status', 'role', 'created'),
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên quản trị viên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu ban đầu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role_id' => 2,
            'is_active' => $request->boolean('is_active', true),
        ]);

        SystemLog::record(
            $request->user(),
            "Đã tạo tài khoản Admin {$admin->email}",
            'admin',
            'success',
            ['target_user_id' => $admin->id],
        );

        return redirect()
            ->route('admin.super-admin', ['q' => $admin->email])
            ->with('success', 'Đã tạo tài khoản Admin mới.');
    }

    private function orderStats(): array
    {
        if (! Schema::hasTable('orders')) {
            return ['today_count' => 0, 'today_revenue' => 0, 'month_revenue' => 0];
        }

        $paidOrders = fn ($query) => $query->where(function ($builder) {
            $builder->where('payment_status', 'paid')->orWhere('status', 'completed');
        });

        return [
            'today_count' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => $paidOrders(Order::whereDate('created_at', today()))->sum('total'),
            'month_revenue' => $paidOrders(Order::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]))->sum('total'),
        ];
    }

    private function revenueChart(): array
    {
        $days = collect(range(6, 0))->map(fn (int $offset) => today()->subDays($offset));
        $orders = Schema::hasTable('orders')
            ? Order::where('created_at', '>=', $days->first()->copy()->startOfDay())->get(['created_at', 'total', 'status', 'payment_status'])
            : collect();

        $values = $days->map(function (Carbon $day) use ($orders) {
            return (int) $orders
                ->filter(fn (Order $order) => $order->created_at->isSameDay($day)
                    && ($order->payment_status === 'paid' || $order->status === 'completed'))
                ->sum('total');
        });

        return $this->chartPayload($days->map(fn (Carbon $day) => $day->format('d/m')), $values);
    }

    private function userChart(): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $users = User::where('created_at', '>=', $months->first()->copy()->startOfMonth())->get(['created_at']);
        $values = $months->map(fn (Carbon $month) => $users->filter(
            fn (User $user) => $user->created_at->year === $month->year && $user->created_at->month === $month->month
        )->count());

        return $this->chartPayload($months->map(fn (Carbon $month) => 'T'.$month->month), $values);
    }

    private function chartPayload(Collection $labels, Collection $values): array
    {
        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'values' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function securityStats(): array
    {
        return [
            'failed_logins' => Schema::hasTable('system_logs')
                ? SystemLog::where('category', 'auth')->where('status', 'failed')->whereDate('created_at', today())->count()
                : 0,
            'locked_admins' => User::admins()->where('is_active', false)->count(),
            'pending_resets' => Schema::hasColumn('users', 'reset_token')
                ? User::whereNotNull('reset_token')->count()
                : 0,
            'unread_notifications' => auth()->user()->unreadNotifications()->count(),
        ];
    }

    private function systemHealth(): array
    {
        try {
            DB::connection()->getPdo();
            $database = ['label' => 'Online', 'tone' => 'success'];
        } catch (\Throwable) {
            $database = ['label' => 'Mất kết nối', 'tone' => 'danger'];
        }

        $storagePath = storage_path();
        $freeBytes = @disk_free_space($storagePath);

        return [
            'database' => $database,
            'storage' => $freeBytes === false ? 'Không xác định' : number_format($freeBytes / 1073741824, 1).' GB trống',
            'cache' => config('cache.default'),
            'mail' => config('mail.default') === 'log' ? 'Ghi log cục bộ' : 'Đã cấu hình '.config('mail.default'),
        ];
    }
}
