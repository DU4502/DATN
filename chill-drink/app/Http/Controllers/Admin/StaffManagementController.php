<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffManagementController extends Controller
{
    /**
     * Danh sách nhân viên — Admin/SuperAdmin chỉ thấy nhân viên thuộc chi nhánh của mình.
     * Super Admin thấy tất cả.
     */
    public function index(Request $request): View
    {
        $authUser = auth()->user();
        $search   = trim((string) $request->query('q', ''));
        $status   = (string) $request->query('status', 'all');

        $query = User::where('role_id', 5)->with('branch')->orderBy('name');

        // Admin chỉ thấy nhân viên của chi nhánh mình
        if ($authUser->isAdmin() && ! $authUser->isSuperAdmin()) {
            if (!$authUser->branch_id) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('branch_id', $authUser->branch_id);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'locked') {
            $query->where('is_active', false);
        }

        $staffUsers = $query->paginate(15)->withQueryString();

        // Dropdown chi nhánh: Super Admin thấy tất cả, Admin chỉ thấy chi nhánh mình
        $branches = $authUser->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', $authUser->branch_id)->get();

        $stats = [
            'total'  => (clone $query->getQuery())->count(),
            'active' => User::where('role_id', 5)->when(
                $authUser->isAdmin() && ! $authUser->isSuperAdmin(),
                fn ($q) => $authUser->branch_id
                    ? $q->where('branch_id', $authUser->branch_id)
                    : $q->whereRaw('1 = 0')
            )->where('is_active', true)->count(),
            'locked' => User::where('role_id', 5)->when(
                $authUser->isAdmin() && ! $authUser->isSuperAdmin(),
                fn ($q) => $authUser->branch_id
                    ? $q->where('branch_id', $authUser->branch_id)
                    : $q->whereRaw('1 = 0')
            )->where('is_active', false)->count(),
        ];

        return view('admin.staff.index', compact('staffUsers', 'branches', 'stats', 'search', 'status'));
    }

    /**
     * Tạo mới nhân viên
     */
    public function store(Request $request): RedirectResponse
    {
        $authUser = auth()->user();

        // Normalize email về chữ thường TRƯỚC validate — đảm bảo unique check khớp DB
        $normalizedEmail = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $normalizedEmail]);

        $validated = $request->validateWithBag('createStaff', [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ], [
            'name.required'      => 'Vui lòng nhập tên nhân viên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không đúng định dạng.',
            'email.unique'       => 'Email đã được sử dụng.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        // Double-check: kiểm tra lại email trùng ngay trước khi insert
        if (User::where('email', $validated['email'])->exists()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => 'Email đã được sử dụng.'], 'createStaff');
        }

        // Admin thường chỉ có thể tạo nhân viên thuộc chi nhánh của mình
        $branchId = $validated['branch_id'] ?? null;
        if ($authUser->isAdmin() && ! $authUser->isSuperAdmin()) {
            abort_unless($authUser->branch_id, 403, 'Admin phÃ¡i Ä‘Æ°á»£c gÃ¡n chi nhÃ¡nh trÆ°á»›c khi táº¡o nhÃ¢n viÃªn.');
            $branchId = $authUser->branch_id;
        }

        $staff = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'], // đã lowercase từ bước merge trước validate
            'password'  => Hash::make($validated['password']),
            'role_id'   => 5,
            'branch_id' => $branchId,
            'is_active' => true,
        ]);

        SystemLog::record(
            $request->user(),
            "Đã tạo tài khoản Nhân viên {$staff->email}",
            'admin',
            'success',
            ['target_user_id' => $staff->id],
        );

        return redirect()->route('admin.staff.index')
            ->with('success', "Đã tạo nhân viên {$staff->name} thành công.");
    }

    /**
     * Cập nhật thông tin nhân viên
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        // Normalize email về chữ thường trước khi validate
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        // Dùng bag riêng cho từng staff để view biết mở đúng modal
        $bag = 'editStaff' . $user->id;

        $validated = $request->validateWithBag($bag, [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'Vui lòng nhập tên nhân viên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không đúng định dạng.',
            'email.unique'       => 'Email đã được sử dụng.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        // Chỉ Super Admin mới được thay đổi chi nhánh của nhân viên
        if (auth()->user()->isSuperAdmin()) {
            $data['branch_id'] = $validated['branch_id'] ?? null;
        }

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.staff.index')
            ->with('success', "Đã cập nhật thông tin nhân viên {$user->name}.");
    }

    /**
     * Khóa / mở khóa nhân viên
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        SystemLog::record(
            $request->user(),
            ($newStatus ? 'Đã mở khóa nhân viên ' : 'Đã khóa nhân viên ') . $user->email,
            'admin',
            'success',
            ['target_user_id' => $user->id],
        );

        return redirect()->back()
            ->with('success', "Đã " . ($newStatus ? 'mở khóa' : 'khóa') . " nhân viên {$user->name}.");
    }

    /**
     * Cập nhật chi nhánh nhân viên
     */
    public function updateBranch(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $user->update(['branch_id' => $validated['branch_id'] ?? null]);

        return redirect()->back()
            ->with('success', "Đã cập nhật chi nhánh cho nhân viên {$user->name}.");
    }

    /**
     * Xóa nhân viên
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        $name = $user->name;
        $user->delete();

        SystemLog::record(
            $request->user(),
            "Đã xóa tài khoản nhân viên {$name}",
            'admin',
            'success',
            [],
        );

        return redirect()->route('admin.staff.index')
            ->with('success', "Đã xóa nhân viên {$name}.");
    }

    private function ensureCanManage(User $user): void
    {
        if (! $user->isStaffOnly()) {
            abort(403, 'Chỉ được quản lý tài khoản nhân viên.');
        }

        $authUser = auth()->user();
        if ($authUser->isAdmin() && ! $authUser->isSuperAdmin()) {
            if (!$authUser->branch_id || (int) $user->branch_id !== (int) $authUser->branch_id) {
                abort(403, 'Bạn không có quyền quản lý nhân viên chi nhánh khác.');
            }
        }
    }
}
