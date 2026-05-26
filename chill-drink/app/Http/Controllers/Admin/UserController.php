<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::customers()->latest()->paginate(12);
        $totalCustomers = User::customers()->count();
        $totalAdmins = User::admins()->count();
        $activeCustomers = User::customers()->where('is_active', true)->count();
        $lockedCustomers = User::customers()->where('is_active', false)->count();

        return view('admin.users.index', compact('users', 'totalCustomers', 'totalAdmins', 'activeCustomers', 'lockedCustomers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        abort_if($user->isAdmin(), 404);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        abort_if($user->isAdmin(), 404);

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'area' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên khách hàng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được dùng bởi tài khoản khác.',
        ]);

        $data = collect($validated)
            ->only(['name', 'email', 'phone', 'area', 'address'])
            ->filter(fn ($value, $field) => Schema::hasColumn('users', $field))
            ->all();

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Đã cập nhật thông tin tài khoản.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function toggleStatus(User $user)
    {
        abort_if($user->isAdmin(), 404);
        abort_unless(Schema::hasColumn('users', 'is_active'), 400, 'Bảng users chưa có cột is_active.');

        $user->forceFill([
            'is_active' => ! (bool) $user->is_active,
        ])->save();

        return back()->with(
            'status',
            $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.'
        );
    }
}
