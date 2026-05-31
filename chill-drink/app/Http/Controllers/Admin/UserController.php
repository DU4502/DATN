<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLE_CUSTOMER = 1;
    private const ROLE_ADMIN = 2;

    public function index(Request $request): View
    {
        $roleOptions = $this->roleOptions();
        $query = User::query();

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
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'customers' => User::where('role_id', self::ROLE_CUSTOMER)->count(),
            'admins' => User::where('role_id', self::ROLE_ADMIN)->count(),
            'active' => User::where('is_active', true)->count(),
            'locked' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'roleOptions'));
    }

    public function show(User $user): View
    {
        $user->loadCount($this->countableRelations());

        return view('admin.users.show', [
            'user' => $user,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatedRoleData($request);
        $roleId = (int) $validated['role_id'];

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

        $user->update(['role_id' => $roleId]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã cập nhật vai trò người dùng.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'Không thể khóa tài khoản đang đăng nhập.');
        }

        $newStatus = ! (bool) $user->is_active;

        if ($this->wouldRemoveLastActiveAdmin($user, (int) $user->role_id, $newStatus)) {
            return back()->with('error', 'Cần giữ lại ít nhất một quản trị viên đang hoạt động.');
        }

        $user->forceFill(['is_active' => $newStatus])->save();

        return back()->with(
            'success',
            $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.'
        );
    }

    private function validatedRoleData(Request $request): array
    {
        return $request->validate([
            'role_id' => ['required', Rule::in(array_keys($this->roleOptions()))],
        ], [
            'role_id.required' => 'Vui lòng chọn vai trò.',
            'role_id.in' => 'Vai trò không hợp lệ.',
        ]);
    }

    private function roleOptions(): array
    {
        return [
            self::ROLE_CUSTOMER => 'Người dùng',
            self::ROLE_ADMIN => 'Quản trị viên',
        ];
    }

    private function wouldRemoveLastActiveAdmin(User $user, int $newRoleId, bool $newActiveStatus): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($newRoleId === self::ROLE_ADMIN && $newActiveStatus) {
            return false;
        }

        return User::where('role_id', self::ROLE_ADMIN)
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
