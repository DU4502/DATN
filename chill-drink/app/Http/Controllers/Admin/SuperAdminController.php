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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        $adminQuery = User::admins();
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');
        $role = (string) $request->query('role', 'all');
        $created = (string) $request->query('created', 'all');
        $rankingPeriod = in_array($request->query('ranking_period'), ['all', 'week', 'month', 'year'], true)
            ? $request->query('ranking_period')
            : 'all';

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
            ->with('branch')
            ->orderByDesc('is_active');
        
        if (Schema::hasColumn('users', 'last_login_at')) {
            $adminUsers = $adminUsers->orderByDesc('last_login_at');
        }
        
        $adminUsers = $adminUsers
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAdmins = User::admins()->get();

        // Danh sách nhân viên (role_id = 5)
        $staffUsers = User::where('role_id', 5)->with('branch')->orderBy('name')->get();
        $loginHistoryByAdmin = $this->loginHistoryByAdmin($adminUsers);
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
            'branches' => Schema::hasTable('branches')
                ? Branch::with(['users'])->withCount(['users', 'orders'])->latest()->get()
                : collect(),
            'orderStats' => $orderStats,
            'revenueChart' => $this->revenueChart(),
            'userChart' => $this->userChart(),
            'activityLogs' => Schema::hasTable('system_logs')
                ? SystemLog::latest()->limit(8)->get()
                : collect(),
            'loginHistoryByAdmin' => $loginHistoryByAdmin,
            'securityStats' => $this->securityStats(),
            'systemHealth' => $this->systemHealth(),
            'notifications' => $request->user()->notifications()->latest()->limit(5)->get(),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            'filters' => compact('search', 'status', 'role', 'created'),
            'staffUsers' => $staffUsers,
            // Branch Statistics - Phase 1 Data
            'branchSummaryStats' => $this->branchSummaryStats(),
            'branchInsightStats' => $this->branchInsightStats(),
            'branchRevenueChart' => $this->branchRevenueChart(),
            'branchOrderChart' => $this->branchOrderChart(),
            'branchRankingStats' => $this->branchRankingStats($rankingPeriod),
            'rankingPeriod' => $rankingPeriod,
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createAdmin', [
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

        try {
            DB::beginTransaction();

            // Create admin user
            $admin = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'role_id' => 2,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Auto-create branch for this admin
            $branch = Branch::create([
                'name' => "Chi nhánh - {$admin->name}",
                'code' => "ADM{$admin->id}",
                'email' => $admin->email,
                'phone' => null,
                'address' => 'Không áp dụng',
                'status' => $admin->is_active,
            ]);

            // Assign branch to admin
            $admin->update(['branch_id' => $branch->id]);

            DB::commit();

            SystemLog::record(
                $request->user(),
                "Đã tạo tài khoản Admin {$admin->email} và chi nhánh {$branch->name}",
                'admin',
                'success',
                ['target_user_id' => $admin->id, 'target_branch_id' => $branch->id],
            );

            return redirect()
                ->route('admin.super-admin', ['q' => $admin->email])
                ->with('success', 'Đã tạo tài khoản Admin và chi nhánh quản lý mới.');
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Admin creation failed', [
                'email' => $validated['email'],
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo Admin. Vui lòng thử lại.');
        }
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

    private function branchSummaryStats(): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return [
                'total_branches' => 0,
                'active_branches' => 0,
                'total_orders' => 0,
                'total_revenue' => 0,
                'today_orders' => 0,
                'today_revenue' => 0,
                'month_revenue' => 0,
                'total_branch_staff' => 0,
            ];
        }

        $paidOrders = fn ($query) => $query->where(function ($builder) {
            $builder->where('payment_status', 'paid')->orWhere('status', 'completed');
        });

        return [
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('status', true)->count(),
            'total_orders' => Order::whereNotNull('branch_id')->count(),
            'total_revenue' => $paidOrders(Order::whereNotNull('branch_id'))->sum('total'),
            'today_orders' => Order::whereNotNull('branch_id')->whereDate('created_at', today())->count(),
            'today_revenue' => $paidOrders(Order::whereNotNull('branch_id')->whereDate('created_at', today()))->sum('total'),
            'month_revenue' => $paidOrders(Order::whereNotNull('branch_id')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]))->sum('total'),
            'total_branch_staff' => User::whereNotNull('branch_id')->count(),
        ];
    }

    private function branchInsightStats(): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return [
                'top_revenue_branch' => null,
                'top_order_branch' => null,
                'highest_cancelled_branch' => null,
                'average_revenue_per_branch' => 0,
            ];
        }

        $paidOrders = fn ($query) => $query->where(function ($builder) {
            $builder->where('payment_status', 'paid')->orWhere('status', 'completed');
        });

        // Top revenue branch
        $topRevenueResult = DB::table('orders')
            ->whereNotNull('branch_id')
            ->where(function ($q) {
                $q->where('payment_status', 'paid')->orWhere('status', 'completed');
            })
            ->selectRaw('branch_id, SUM(total) as revenue')
            ->groupBy('branch_id')
            ->orderByDesc('revenue')
            ->first();

        $topRevenueBranch = null;
        $totalRevenue = 0;
        if ($topRevenueResult) {
            $topRevenueBranch = Branch::find($topRevenueResult->branch_id);
            $totalRevenue = $topRevenueResult->revenue;
        }

        // Top order branch
        $topOrderResult = DB::table('orders')
            ->whereNotNull('branch_id')
            ->selectRaw('branch_id, COUNT(*) as order_count')
            ->groupBy('branch_id')
            ->orderByDesc('order_count')
            ->first();

        $topOrderBranch = null;
        $totalOrders = Order::whereNotNull('branch_id')->count();
        if ($topOrderResult) {
            $topOrderBranch = Branch::find($topOrderResult->branch_id);
        }

        // Highest cancelled branch
        $highestCancelledResult = DB::table('orders')
            ->whereNotNull('branch_id')
            ->where('status', 'cancelled')
            ->selectRaw('branch_id, COUNT(*) as cancelled_count')
            ->groupBy('branch_id')
            ->orderByDesc('cancelled_count')
            ->first();

        $highestCancelledBranch = null;
        if ($highestCancelledResult) {
            $highestCancelledBranch = Branch::find($highestCancelledResult->branch_id);
        }

        // Average revenue per branch
        $activeBranchCount = Branch::where('status', true)->count();
        $averageRevenue = $activeBranchCount > 0 ? (int) ($totalRevenue / $activeBranchCount) : 0;

        return [
            'top_revenue_branch' => $topRevenueBranch ? [
                'id' => $topRevenueBranch->id,
                'name' => $topRevenueBranch->name,
                'revenue' => $totalRevenue,
                'percentage' => $totalRevenue > 0 ? round(($totalRevenue / DB::table('orders')->whereNotNull('branch_id')->where(function ($q) { $q->where('payment_status', 'paid')->orWhere('status', 'completed'); })->sum('total')) * 100, 1) : 0,
            ] : null,
            'top_order_branch' => $topOrderBranch ? [
                'id' => $topOrderBranch->id,
                'name' => $topOrderBranch->name,
                'order_count' => $topOrderResult->order_count,
                'percentage' => $totalOrders > 0 ? round(($topOrderResult->order_count / $totalOrders) * 100, 1) : 0,
            ] : null,
            'highest_cancelled_branch' => $highestCancelledBranch ? [
                'id' => $highestCancelledBranch->id,
                'name' => $highestCancelledBranch->name,
                'cancelled_count' => $highestCancelledResult->cancelled_count,
                'percentage' => $totalOrders > 0 ? round(($highestCancelledResult->cancelled_count / $totalOrders) * 100, 1) : 0,
            ] : null,
            'average_revenue_per_branch' => $averageRevenue,
        ];
    }

    private function branchRevenueChart(): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return ['labels' => [], 'data' => [], 'heights' => []];
        }

        $branchRevenue = DB::table('orders')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->whereNotNull('orders.branch_id')
            ->where(function ($q) {
                $q->where('orders.payment_status', 'paid')->orWhere('orders.status', 'completed');
            })
            ->selectRaw('branches.name, SUM(orders.total) as revenue')
            ->groupBy('orders.branch_id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        $labels = $branchRevenue->pluck('name');
        $values = $branchRevenue->pluck('revenue');

        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'data' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function branchOrderChart(): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return ['labels' => [], 'data' => [], 'heights' => []];
        }

        $branchOrders = DB::table('orders')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->whereNotNull('orders.branch_id')
            ->selectRaw('branches.name, COUNT(*) as order_count')
            ->groupBy('orders.branch_id', 'branches.name')
            ->orderByDesc('order_count')
            ->get();

        $labels = $branchOrders->pluck('name');
        $values = $branchOrders->pluck('order_count');

        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'data' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function branchRankingStats(string $rankingPeriod = 'all'): Collection
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return collect();
        }

        $branches = Branch::with('users')->get();
        [$from, $to] = $this->rankingPeriodRange($rankingPeriod);

        $totalNetworkRevenueQuery = DB::table('orders')
            ->whereNotNull('branch_id')
            ->where(function ($q) {
                $q->where('payment_status', 'paid')->orWhere('status', 'completed');
            });

        if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
            $totalNetworkRevenueQuery->whereBetween('created_at', [$from, $to]);
        }

        $totalNetworkRevenue = $totalNetworkRevenueQuery->sum('total');

        $stats = $branches->map(function ($branch) use ($totalNetworkRevenue, $from, $to) {
            $allOrders = Order::where('branch_id', $branch->id);
            $paidOrders = Order::where('branch_id', $branch->id)
                ->where(function ($q) {
                    $q->where('payment_status', 'paid')->orWhere('status', 'completed');
                });

            if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
                $allOrders->whereBetween('created_at', [$from, $to]);
                $paidOrders->whereBetween('created_at', [$from, $to]);
            }

            $totalOrders = $allOrders->count();
            $completedOrders = Order::where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->when($from && $to && Schema::hasColumn('orders', 'created_at'), fn ($query) => $query->whereBetween('created_at', [$from, $to]))
                ->count();
            $cancelledOrders = Order::where('branch_id', $branch->id)
                ->where('status', 'cancelled')
                ->when($from && $to && Schema::hasColumn('orders', 'created_at'), fn ($query) => $query->whereBetween('created_at', [$from, $to]))
                ->count();
            $revenue = $paidOrders->sum('total');
            $averageOrderValue = $totalOrders > 0 ? (int) ($revenue / $totalOrders) : 0;

            $admin = $branch->users()->where('role_id', 2)->first();

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'branch_email' => $branch->email,
                'branch_phone' => $branch->phone,
                'branch_address' => $branch->address,
                'branch_latitude' => $branch->latitude,
                'branch_longitude' => $branch->longitude,
                'branch_status' => $branch->status,
                'admin_id' => $admin?->id,
                'admin_name' => $admin?->name ?? 'Chưa gán',
                'admin_email' => $admin?->email,
                'admin_password' => $admin?->plain_password ?? '12345678',
                'staff_count' => $branch->users()->count(),
                'active_staff_count' => $branch->users()->where('is_active', true)->count(),
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'revenue' => $revenue,
                'average_order_value' => $averageOrderValue,
                'performance_percentage' => $totalNetworkRevenue > 0 ? round(($revenue / $totalNetworkRevenue) * 100, 1) : 0,
            ];
        })->sortByDesc('revenue')->values();

        return $stats;
    }

    private function rankingPeriodRange(string $rankingPeriod): array
    {
        $now = Carbon::now();

        return match ($rankingPeriod) {
            'week' => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };
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

    private function loginHistoryByAdmin($adminUsers): Collection
    {
        if (! Schema::hasTable('system_logs')) {
            return collect();
        }

        $adminCollection = $adminUsers instanceof Collection
            ? $adminUsers
            : (method_exists($adminUsers, 'getCollection')
                ? $adminUsers->getCollection()
                : collect($adminUsers));

        $adminIds = $adminCollection->pluck('id')->filter()->values();

        if ($adminIds->isEmpty()) {
            return collect();
        }

        $logsByUser = SystemLog::query()
            ->whereIn('user_id', $adminIds)
            ->where('category', 'auth')
            ->where('action', 'Đăng nhập hệ thống')
            ->where('created_at', '>=', now()->subMonths(3))
            ->latest('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $logs) => $logs->values());

        return $adminCollection->mapWithKeys(function (User $admin) use ($logsByUser) {
            $history = $logsByUser->get($admin->id, collect());

            if ($history->isEmpty() && $admin->last_login_at) {
                $history = collect([(object) [
                    'created_at' => $admin->last_login_at,
                    'action' => 'Đăng nhập hệ thống',
                    'ip_address' => $admin->last_login_ip,
                ]]);
            }

            return [$admin->id => $history];
        });
    }

    public function updateBranch(Request $request, User $user)
    {
        // Nếu user là admin (role 2,3), vẫn enforce unique branch
        $branchUniqueRule = $user->isAdmin()
            ? Rule::unique('users', 'branch_id')
                ->ignore($user->id)
                ->where(fn ($q) => $q->whereIn('role_id', [2, 3]))
                ->whereNotNull('branch_id')
            : null;

        $rules = ['branch_id' => array_filter([
            'nullable',
            'exists:branches,id',
            $branchUniqueRule,
        ])];

        $messages = ['branch_id.unique' => 'Chi nhánh này đã được gán cho admin khác.'];

        $validated = $request->validate($rules, $messages);

        $user->update(['branch_id' => $validated['branch_id'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã cập nhật chi nhánh cho admin {$user->name}"]);
        }

        return redirect()->route('admin.super-admin')->with('success', "Đã cập nhật chi nhánh cho admin {$user->name}");
    }

    public function updateRole(Request $request, User $user)
    {
        // Prevent changing own role
        if ($user->is(auth()->user())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.'], 422);
            }
            return back()->withErrors(['role_id' => 'Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.']);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'in:2,3,4,5'],
        ]);

        $roleId = (int) $validated['role_id'];

        // Prevent downgrading the last active Super Admin
        if ($roleId !== 3 && $user->isSuperAdmin()) {
            $activeSuperAdmins = User::where('role_id', 3)->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Không thể hạ cấp Super Admin duy nhất còn hoạt động.'], 403);
                }
                return back()->withErrors(['role_id' => 'Không thể hạ cấp Super Admin duy nhất còn hoạt động.']);
            }
        }

        $user->update(['role_id' => $roleId]);

        SystemLog::record(
            auth()->user(),
            "Đã cập nhật vai trò của {$user->email}",
            'admin',
            'success',
            ['target_user_id' => $user->id, 'role_id' => $roleId],
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã cập nhật vai trò cho {$user->name}"]);
        }

        return redirect()->route('admin.super-admin')->with('success', "Đã cập nhật vai trò cho {$user->name}");
    }

    /**
     * Tạo tài khoản nhân viên (role_id = 5)
     */
    public function storeStaff(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createStaff', [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ], [
            'name.required'     => 'Vui lòng nhập tên nhân viên.',
            'email.required'    => 'Vui lòng nhập email.',
            'email.unique'      => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed'=> 'Mật khẩu xác nhận không khớp.',
        ]);

        try {
            $staff = User::create([
                'name'      => $validated['name'],
                'email'     => strtolower($validated['email']),
                'password'  => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'role_id'   => 5, // Nhân viên
                'branch_id' => $validated['branch_id'] ?? null,
                'is_active' => true,
            ]);

            SystemLog::record(
                $request->user(),
                "Đã tạo tài khoản Nhân viên {$staff->email}",
                'admin',
                'success',
                ['target_user_id' => $staff->id],
            );

            return redirect()
                ->route('admin.super-admin')
                ->with('success', "Đã tạo tài khoản nhân viên {$staff->name}.");
        } catch (\Throwable $e) {
            \Log::error('Staff creation failed', ['email' => $validated['email'], 'message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo nhân viên. Vui lòng thử lại.');
        }
    }

    /**
     * Gán/bỏ gán chi nhánh cho nhân viên (không ràng buộc unique)
     */
    public function updateStaffBranch(Request $request, User $user): RedirectResponse
    {
        if (! $user->isStaffOnly()) {
            abort(403, 'Chỉ áp dụng cho nhân viên.');
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $user->update(['branch_id' => $validated['branch_id'] ?? null]);

        return redirect()->back()->with('success', "Đã cập nhật chi nhánh cho nhân viên {$user->name}.");
    }

    /**
     * Khóa/mở khóa nhân viên
     */
    public function toggleStaffStatus(Request $request, User $user): RedirectResponse
    {
        if (! $user->isStaffOnly()) {
            abort(403, 'Chỉ áp dụng cho nhân viên.');
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        SystemLog::record(
            $request->user(),
            ($newStatus ? 'Đã mở khóa nhân viên ' : 'Đã khóa nhân viên ') . $user->email,
            'admin',
            'success',
            ['target_user_id' => $user->id],
        );

        return redirect()->back()->with('success', "Đã " . ($newStatus ? 'mở khóa' : 'khóa') . " nhân viên {$user->name}.");
    }

    /**
     * Xóa nhân viên
     */
    public function destroyStaff(Request $request, User $user): RedirectResponse
    {
        if (! $user->isStaffOnly()) {
            abort(403, 'Chỉ áp dụng cho nhân viên.');
        }

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        SystemLog::record(
            $request->user(),
            "Đã xóa tài khoản nhân viên {$email}",
            'admin',
            'success',
            [],
        );

        return redirect()->back()->with('success', "Đã xóa nhân viên {$name}.");
    }
}
