<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shipper;
use App\Models\SystemLog;
use App\Models\User;
use App\Rules\ActiveBranchAssignment;
use App\Services\ShipperHomeBranchService;
use App\Services\ShipperRoleChangeGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    private const ROLE_CUSTOMER = 1;
    private const ROLE_ADMIN = 2;
    private const ROLE_SUPER_ADMIN = 3;

    private const ROLE_CSKH = 4;

    private const ROLE_STAFF = User::STAFF_ROLE_ID;

    private const ROLE_SHIPPER = User::SHIPPER_ROLE_ID;

    private const BRANCH_SCOPED_ROLES = [
        self::ROLE_CSKH,
        self::ROLE_STAFF,
        self::ROLE_SHIPPER,
    ];

    private const ASSIGNABLE_BRANCH_ROLES = [
        self::ROLE_STAFF,
        self::ROLE_SHIPPER,
    ];

    private const ADMIN_ASSIGNABLE_ROLES = [
        self::ROLE_CUSTOMER,
        self::ROLE_STAFF,
        self::ROLE_SHIPPER,
    ];

    public function index(Request $request): View
    {
        $actor = $request->user();
        $roleOptions = $this->roleOptions();
        $query = $this->manageableUsersQuery($actor);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (array_key_exists((int) $request->query('role'), $roleOptions)) {
            $query->where('role_id', (int) $request->query('role'));
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'locked') {
            $query->where('is_active', false);
        }

        $users = $query
            ->with('branch')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statsQuery = $this->manageableUsersQuery($actor);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'customers' => (clone $statsQuery)->where('role_id', self::ROLE_CUSTOMER)->count(),
            'admins' => (clone $statsQuery)->whereIn('role_id', [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN])->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'locked' => (clone $statsQuery)->where('is_active', false)->count(),
        ];

        $branches = $this->manageableBranches($actor);
        $branchRoleIds = self::ASSIGNABLE_BRANCH_ROLES;

        return view('admin.users.index', compact('users', 'stats', 'roleOptions', 'branches', 'branchRoleIds'));
    }

    public function show(User $user): View
    {
        $this->ensureCanManage($user, Auth::user());
        $user->loadCount($this->countableRelations());

        return view('admin.users.show', [
            'user' => $user,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function edit(User $user): View
    {
        $actor = Auth::user();
        $this->ensureCanManage($user, $actor);
        $user->loadMissing('branch');

        return view('admin.users.edit', [
            'user' => $user,
            'roleOptions' => $this->roleOptions(),
            'branches' => $this->manageableBranches($actor),
            'branchRoleIds' => self::ASSIGNABLE_BRANCH_ROLES,
        ]);
    }

    public function update(
        Request $request,
        User $user,
        ShipperHomeBranchService $homeBranches,
        ShipperRoleChangeGuard $roleChangeGuard,
    ): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanManage($user, $actor);
        $validated = $this->validatedRoleData($request, $user);
        $roleId = (int) $validated['role_id'];
        $this->ensureCanAssignRole($actor, $roleId);

        $branchId = in_array($roleId, self::ASSIGNABLE_BRANCH_ROLES, true)
            ? (int) $validated['branch_id']
            : null;
        if ($branchId !== null) {
            $this->ensureCanAssignBranch($actor, $branchId);
        }
        $isLeavingShipperRole = $user->isShipper() && $roleId !== self::ROLE_SHIPPER;

        if ($user->is(Auth::user()) && $roleId !== (int) $user->role_id) {
            return back()
                ->withInput()
                ->withErrors(['role_id' => 'Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.']);
        }

        if ($this->wouldRemoveLastActiveAdmin($user, $roleId, (bool) $user->is_active)) {
            return back()
                ->withInput()
                ->withErrors(['role_id' => 'Cần giữ lại ít nhất một quản trị viên đang hoạt động.']);
        }

        try {
            DB::transaction(function () use ($user, $roleId, $branchId, $homeBranches, $roleChangeGuard, $request): void {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $wasShipper = $lockedUser->isShipper();

                if ($wasShipper && $roleId !== self::ROLE_SHIPPER) {
                    $roleChangeGuard->assertCanLeaveRole($lockedUser);
                }

                if ($roleId === self::ROLE_SHIPPER && $wasShipper && (int) $lockedUser->branch_id !== $branchId) {
                    $homeBranches->transfer($lockedUser, $branchId, $request->user());
                    $lockedUser->refresh();
                }

                $userData = ['role_id' => $roleId];
                if ($roleId === self::ROLE_STAFF
                    || ($roleId === self::ROLE_SHIPPER && ! $wasShipper)) {
                    $userData['branch_id'] = $branchId;
                }

                $lockedUser->update($userData);

                if ($roleId === self::ROLE_SHIPPER) {
                    $shipper = Shipper::query()->firstOrCreate(
                        ['user_id' => $lockedUser->id],
                        [
                            'code' => 'SHIP'.str_pad((string) $lockedUser->id, 5, '0', STR_PAD_LEFT),
                            'phone' => (string) ($lockedUser->phone ?? ''),
                            'vehicle_type' => 'bike',
                            'status' => 'offline',
                            'station_branch_id' => $branchId,
                        ],
                    );

                    if (! $wasShipper && ! $shipper->wasRecentlyCreated) {
                        $shipper->forceFill([
                            'status' => 'offline',
                            'station_branch_id' => $branchId,
                            'returning_to_branch_id' => null,
                            'returning_started_at' => null,
                        ])->save();
                    }
                }
            }, 3);

            $user->refresh();
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            return back()
                ->withInput()
                ->withErrors([
                    ($isLeavingShipperRole ? 'role_id' : 'branch_id') => $message,
                ])
                ->with('error', $message);
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Vai trò tài khoản {$user->email} đã được cập nhật.",
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Thông báo thay đổi vai trò tài khoản');
                }
            );
        } catch (\Throwable) {}

        SystemLog::record(
            Auth::user(),
            "Đã cập nhật vai trò của {$user->email}",
            'admin',
            'success',
            ['target_user_id' => $user->id, 'role_id' => $roleId],
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã cập nhật vai trò người dùng.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $actor = $request->user();
        $this->ensureCanManage($user, $actor);

        if ($user->is($actor)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Không thể khóa tài khoản đang đăng nhập.'], 422);
            }
            return back()->with('error', 'Không thể khóa tài khoản đang đăng nhập.');
        }

        $newStatus = ! (bool) $user->is_active;

        if ($this->wouldRemoveLastActiveAdmin($user, (int) $user->role_id, $newStatus)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Cần giữ lại ít nhất một quản trị viên đang hoạt động.'], 422);
            }
            return back()->with('error', 'Cần giữ lại ít nhất một quản trị viên đang hoạt động.');
        }

        $user->forceFill(['is_active' => $newStatus])->save();

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Trạng thái tài khoản của bạn đã được thay đổi thành: " . ($newStatus ? 'Hoạt động' : 'Bị khóa'),
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Thông báo thay đổi trạng thái tài khoản');
                }
            );
        } catch (\Throwable) {}

        SystemLog::record(
            $actor,
            ($newStatus ? 'Đã mở khóa ' : 'Đã khóa ').$user->email,
            'security',
            'success',
            ['target_user_id' => $user->id],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $user->is_active,
                'user' => [
                    'id' => $user->id,
                    'is_active' => (bool) $user->is_active,
                ],
                'message' => $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.',
            ]);
        }

        return back()->with(
            'success',
            $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.'
        );
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'status' => ['required', 'boolean'],
        ]);

        $actor = $request->user();
        $userIds = array_values(array_unique(array_map('intval', $request->input('user_ids'))));
        $status = (bool) $request->input('status');
        $count = 0;

        $targets = User::query()->whereKey($userIds)->get()->keyBy('id');
        foreach ($userIds as $id) {
            $targetUser = $targets->get($id);
            abort_unless($targetUser, 404);
            $this->ensureCanManage($targetUser, $actor);
            abort_if($targetUser->is($actor), 403);
        }

        foreach ($userIds as $id) {
            $targetUser = $targets->get($id);

            if ($this->wouldRemoveLastActiveAdmin($targetUser, (int) $targetUser->role_id, $status)) {
                continue;
            }

            $targetUser->forceFill(['is_active' => $status])->save();
            $count++;
        }

        SystemLog::record(
            Auth::user(),
            "Đã cập nhật trạng thái hàng loạt cho {$count} tài khoản sang " . ($status ? 'Hoạt động' : 'Khóa'),
            'security',
            'success',
        );

        return back()->with('success', "Đã cập nhật trạng thái cho {$count} tài khoản.");
    }

    private function validatedRoleData(Request $request, User $user): array
    {
        $requestedRoleId = (int) $request->input('role_id');
        $preservedBranchId = in_array($requestedRoleId, self::ASSIGNABLE_BRANCH_ROLES, true)
            && $requestedRoleId === (int) $user->role_id
            ? ($user->branch_id ? (int) $user->branch_id : null)
            : null;

        return $request->validate([
            'role_id' => ['required', Rule::in(array_keys($this->allRoleOptions()))],
            'branch_id' => [
                Rule::requiredIf(in_array(
                    (int) $request->input('role_id'),
                    self::ASSIGNABLE_BRANCH_ROLES,
                    true,
                )),
                'nullable',
                'integer',
                new ActiveBranchAssignment($preservedBranchId),
            ],
        ], [
            'role_id.required' => 'Vui lòng chọn vai trò.',
            'role_id.in' => 'Vai trò không hợp lệ.',
            'branch_id.required' => 'Vui lòng chọn chi nhánh làm việc cho tài khoản.',
        ]);
    }

    private function roleOptions(): array
    {
        $roles = [
            self::ROLE_CUSTOMER => 'Người dùng',
            self::ROLE_ADMIN => 'Quản trị viên',
        ];

        if (! Auth::user()?->isSuperAdmin()) {
            unset($roles[self::ROLE_ADMIN]);
            $roles[self::ROLE_STAFF] = 'Nhân viên';
            $roles[self::ROLE_SHIPPER] = 'Shipper';

            return $roles;
        }

        if (Auth::user()?->isSuperAdmin()) {
            $roles[self::ROLE_STAFF] = 'Nhân viên';
            $roles[self::ROLE_SHIPPER] = 'Shipper';
            $roles[self::ROLE_SUPER_ADMIN] = 'Super Admin';
        }

        return $roles;
    }

    private function allRoleOptions(): array
    {
        return [
            self::ROLE_CUSTOMER => true,
            self::ROLE_ADMIN => true,
            self::ROLE_SUPER_ADMIN => true,
            self::ROLE_STAFF => true,
            self::ROLE_SHIPPER => true,
        ];
    }

    private function manageableUsersQuery(User $actor): Builder
    {
        $query = User::query();
        if ($actor->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($actor) {
            $scope->where('role_id', self::ROLE_CUSTOMER);

            if ($actor->branch_id) {
                $scope->orWhere(function (Builder $branchScope) use ($actor) {
                    $branchScope
                        ->whereIn('role_id', self::BRANCH_SCOPED_ROLES)
                        ->where('branch_id', $actor->branch_id);
                });
            }
        });
    }

    private function ensureCanManage(User $user, ?User $actor): void
    {
        abort_unless($actor && $this->canManageTarget($actor, $user), 403);
    }

    private function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if (! $actor->isAdmin() || $target->isAdmin() || $target->isSuperAdmin()) {
            return false;
        }

        if ((int) $target->role_id === self::ROLE_CUSTOMER) {
            return true;
        }

        return in_array((int) $target->role_id, self::BRANCH_SCOPED_ROLES, true)
            && $actor->branch_id !== null
            && (int) $target->branch_id === (int) $actor->branch_id;
    }

    private function ensureCanAssignRole(User $actor, int $roleId): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        abort_unless($actor->isAdmin() && in_array($roleId, self::ADMIN_ASSIGNABLE_ROLES, true), 403);
    }

    private function ensureCanAssignBranch(User $actor, int $branchId): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        abort_unless($actor->isAdmin() && (int) $actor->branch_id === $branchId, 403);
    }

    private function manageableBranches(User $actor): Collection
    {
        return Branch::query()
            ->active()
            ->when(! $actor->isSuperAdmin(), fn (Builder $query) => $query->whereKey($actor->branch_id ?? 0))
            ->orderBy('name')
            ->get();
    }

    private function wouldRemoveLastActiveAdmin(User $user, int $newRoleId, bool $newActiveStatus): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if (in_array($newRoleId, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true) && $newActiveStatus) {
            return false;
        }

        return User::admins()
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->doesntExist();
    }

    private function countableRelations(): array
    {
        return collect([
            'orders' => Schema::hasTable('orders'),
            'reviews' => Schema::hasTable('reviews'),
        ])->filter()->keys()->all();
    }
}
