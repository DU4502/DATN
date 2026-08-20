<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryFeeSetting;
use App\Models\Shipper;
use App\Models\User;
use App\Models\SystemLog;
use App\Services\ShipperHomeBranchService;
use App\Support\ShippingFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class StaffManagementController extends Controller
{
    /**
     * Danh sách nhân viên — Admin/SuperAdmin chỉ thấy nhân viên thuộc chi nhánh của mình.
     * Super Admin thấy tất cả.
     */
    public function index(Request $request, ShipperHomeBranchService $homeBranches): View
    {
        $authUser = auth()->user();
        $search   = trim((string) $request->query('q', ''));
        $status   = (string) $request->query('status', 'all');

        $query = User::where('role_id', 5)->with(['branch', 'shipper'])->orderBy('name');

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

        $deliveryFeeSettings = ShippingFee::settings();
        $branchTransferStates = [];
        if ($authUser->isSuperAdmin()) {
            foreach ($staffUsers as $staffUser) {
                $branchTransferStates[(int) $staffUser->id] = $homeBranches->canTransfer($staffUser);
            }
        }

        return view('admin.staff.index', compact(
            'staffUsers',
            'branches',
            'stats',
            'search',
            'status',
            'deliveryFeeSettings',
            'branchTransferStates'
        ));
    }

    /**
     * Cập nhật chính sách phí giao hàng toàn hệ thống.
     * Chỉ Super Admin được phép sửa; Admin thường chỉ xem cấu hình hiện tại.
     */
    public function updateDeliveryFeeSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Chỉ Super Admin được thay đổi phí giao hàng.');

        $validated = $request->validateWithBag('deliveryFeeSettings', [
            'free_distance_km' => ['required', 'numeric', 'min:0', 'max:15'],
            'fast_surcharge' => ['required', 'integer', 'min:0', 'max:1000000'],
            'tier_max' => ['required', 'array', 'min:1', 'max:10'],
            'tier_max.*' => ['nullable', 'integer', 'min:1', 'max:999'],
            'tier_price' => ['required', 'array', 'min:1', 'max:10'],
            'tier_price.*' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'free_distance_km.required' => 'Vui lòng nhập số km được miễn phí ship.',
            'free_distance_km.max' => 'Khoảng cách miễn phí không được vượt quá phạm vi giao hàng 15 km.',
            'tier_max.required' => 'Cần ít nhất một bậc số lượng cốc.',
            'tier_price.required' => 'Cần nhập đơn giá/km cho từng bậc số lượng.',
            'tier_price.*.required' => 'Mỗi bậc số lượng phải có đơn giá/km.',
        ]);

        $maxValues = array_values($validated['tier_max']);
        $priceValues = array_values($validated['tier_price']);

        if (count($maxValues) !== count($priceValues)) {
            $exception = ValidationException::withMessages([
                'tier_price' => 'Dữ liệu bậc số lượng không đồng bộ. Vui lòng tải lại trang và thử lại.',
            ]);
            $exception->errorBag = 'deliveryFeeSettings';
            throw $exception;
        }

        $tiers = [];
        $nextMin = 1;
        $lastIndex = count($maxValues) - 1;

        foreach ($maxValues as $index => $maxRaw) {
            $max = ($maxRaw === null || $maxRaw === '') ? null : (int) $maxRaw;
            $price = max(0, (int) ($priceValues[$index] ?? 0));

            if ($max === null && $index !== $lastIndex) {
                $exception = ValidationException::withMessages([
                    'tier_max' => 'Chỉ bậc cuối cùng mới được để trống giới hạn cốc (không giới hạn).',
                ]);
                $exception->errorBag = 'deliveryFeeSettings';
                throw $exception;
            }

            if ($max !== null && $max < $nextMin) {
                $exception = ValidationException::withMessages([
                    'tier_max' => 'Mốc số lượng cốc phải tăng dần và không được chồng lấn.',
                ]);
                $exception->errorBag = 'deliveryFeeSettings';
                throw $exception;
            }

            $tiers[] = [
                'min_cups' => $nextMin,
                'max_cups' => $max,
                'price_per_km' => $price,
            ];

            if ($max !== null) {
                $nextMin = $max + 1;
            }
        }

        if (($tiers[array_key_last($tiers)]['max_cups'] ?? null) !== null) {
            $exception = ValidationException::withMessages([
                'tier_max' => 'Bậc cuối cùng phải để trống ô “Đến ... cốc” để áp dụng cho mọi đơn lớn hơn.',
            ]);
            $exception->errorBag = 'deliveryFeeSettings';
            throw $exception;
        }

        DB::transaction(function () use ($request, $validated, $tiers): void {
            $setting = DeliveryFeeSetting::query()->first() ?? new DeliveryFeeSetting();
            $setting->fill([
                'free_distance_km' => round((float) $validated['free_distance_km'], 2),
                'fast_surcharge' => (int) $validated['fast_surcharge'],
                'cup_tiers' => $tiers,
                'updated_by' => $request->user()->id,
            ]);
            $setting->save();

            SystemLog::record(
                $request->user(),
                'Đã cập nhật chính sách phí giao hàng',
                'admin',
                'success',
                [
                    'free_distance_km' => (float) $validated['free_distance_km'],
                    'fast_surcharge' => (int) $validated['fast_surcharge'],
                    'cup_tiers' => $tiers,
                ],
            );
        });

        ShippingFee::clearSettingsCache();

        return redirect()
            ->to(route('admin.super-admin.manage.staff.index').'#delivery-fee-settings')
            ->with('success', 'Đã lưu chính sách phí giao hàng. Checkout sẽ áp dụng ngay cho đơn mới.');
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

        // P15: mỗi shipper phải có một HOME BRANCH cố định.
        $branchId = $validated['branch_id'] ?? null;
        if ($authUser->isSuperAdmin() && ! $branchId) {
            return redirect()->back()->withInput()->withErrors([
                'branch_id' => 'Shipper phải được gán một home branch khi tạo tài khoản.',
            ], 'createStaff');
        }
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

        Shipper::query()->firstOrCreate(
            ['user_id' => $staff->id],
            [
                'code' => 'SHP-'.str_pad((string) $staff->id, 5, '0', STR_PAD_LEFT),
                'phone' => (string) ($staff->phone ?? ''),
                'vehicle_type' => 'bike',
                'status' => 'offline',
                'station_branch_id' => $branchId,
            ]
        );

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
    public function update(Request $request, User $user, ShipperHomeBranchService $homeBranches): RedirectResponse
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

        // P15: Super Admin được điều chuyển HOME BRANCH, nhưng chỉ khi shipper sạch nhiệm vụ/COD.
        $actor = auth()->user();
        if ($actor->isSuperAdmin()) {
            $newBranchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : 0;
            if ($newBranchId < 1) {
                return redirect()->back()->withInput()->withErrors([
                    'branch_id' => 'Shipper phải có một home branch.',
                ], $bag);
            }
            if ((int) ($user->branch_id ?? 0) !== $newBranchId) {
                try {
                    $homeBranches->transfer($user, $newBranchId, $actor);
                    $user->refresh();
                } catch (RuntimeException $exception) {
                    return redirect()->back()->withInput()->withErrors([
                        'branch_id' => $exception->getMessage(),
                    ], $bag);
                }
            }
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
    public function updateBranch(Request $request, User $user, ShipperHomeBranchService $homeBranches): RedirectResponse
    {
        $this->ensureCanManage($user);

        abort_unless($request->user()->isSuperAdmin(), 403, 'Chỉ Super Admin được điều chuyển home branch của shipper.');

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        try {
            $updated = $homeBranches->transfer($user, (int) $validated['branch_id'], $request->user());
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()
            ->with('success', "Đã điều chuyển {$updated->name} sang home branch {$updated->branch?->name}.");
    }

    /**
     * Xóa nhân viên
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        abort(403, 'Hệ thống không cho phép xóa nhân viên. Chỉ có thể sửa, khóa hoặc đổi chi nhánh.');
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
