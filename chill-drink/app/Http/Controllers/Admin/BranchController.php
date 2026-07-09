<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\TransferStats;

class BranchController extends Controller
{
    /**
     * Display a listing of branches (Super Admin only)
     */
    public function index(Request $request)
    {
        return redirect()->to(route('admin.super-admin') . '#branch-ranking');
    }

    /**
     * Store a newly created branch (Super Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('createBranch', [
            'name'                     => 'required|string|max:255',
            'code'                     => 'required|string|max:100|unique:branches,code',
            'phone'                    => 'nullable|string|max:20',
            'email'                    => 'nullable|email|max:255',
            'address'                  => 'nullable|string|max:500',
            'latitude'                 => 'required|numeric|between:-90,90',
            'longitude'                => 'required|numeric|between:-180,180',
            'status'                   => 'nullable|boolean',
            'admin_email'              => 'required|email|max:255|unique:users,email',
            'admin_password'           => 'required|string|min:8',
        ], [
            'name.required'            => 'Tên chi nhánh là bắt buộc.',
            'code.required'            => 'Mã chi nhánh là bắt buộc.',
            'code.unique'              => 'Mã chi nhánh đã tồn tại.',
            'email.email'              => 'Email không đúng định dạng.',
            'latitude.required'        => 'Vui lòng chọn vị trí trên bản đồ.',
            'longitude.required'       => 'Vui lòng chọn vị trí trên bản đồ.',
            'admin_email.required'     => 'Email đăng nhập admin là bắt buộc.',
            'admin_email.email'        => 'Email admin không đúng định dạng.',
            'admin_email.unique'       => 'Email admin này đã được sử dụng bởi tài khoản khác.',
            'admin_password.required'  => 'Mật khẩu admin là bắt buộc.',
            'admin_password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        $validated['status'] = $request->boolean('status', true);

        DB::transaction(function () use ($validated, $request) {
            // 1. Create the branch
            $branch = Branch::create([
                'name'      => $validated['name'],
                'code'      => $validated['code'],
                'phone'     => $validated['phone'] ?? null,
                'email'     => $validated['email'] ?? null,
                'address'   => $validated['address'] ?? null,
                'latitude'  => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status'    => $validated['status'],
            ]);

            // 2. Create the branch admin account (role_id=2 = Admin)
            \App\Models\User::create([
                'name'           => 'Admin ' . $branch->name,
                'email'          => $validated['admin_email'],
                'password'       => $validated['admin_password'],
                'plain_password' => $validated['admin_password'],
                'role_id'        => 2,
                'branch_id'      => $branch->id,
                'is_active'      => true,
                'email_verified_at' => now(),
            ]);
        });

        $redirectRoute = $request->input('return_to') === 'super-admin'
            ? 'admin.super-admin'
            : 'admin.branches.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Thêm chi nhánh thành công! Tài khoản admin đã được tạo với email: ' . $validated['admin_email']);
    }


    /**
     * Update the specified branch (Super Admin only)
     */
    public function update(Request $request, Branch $branch)
    {
        $admin = \App\Models\User::where('branch_id', $branch->id)->where('role_id', 2)->first();

        $validated = $request->validateWithBag('editBranch', [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:branches,code,' . $branch->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'status' => 'nullable|boolean',
            'admin_email' => 'required|email|max:255|unique:users,email,' . ($admin ? $admin->id : 'NULL'),
            'admin_password' => 'nullable|string|min:8',
        ], [
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'latitude.required' => 'Vui lòng chọn vị trí trên bản đồ.',
            'longitude.required' => 'Vui lòng chọn vị trí trên bản đồ.',
            'admin_email.required' => 'Email đăng nhập admin là bắt buộc.',
            'admin_email.email' => 'Email admin không đúng định dạng.',
            'admin_email.unique' => 'Email admin này đã được sử dụng bởi tài khoản khác.',
            'admin_password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        $validated['status'] = $request->boolean('status', true);

        DB::transaction(function () use ($branch, $validated, $admin) {
            $branch->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['admin_email'],
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status' => $validated['status'],
            ]);

            $adminData = [
                'name' => 'Admin ' . $branch->name,
                'email' => $validated['admin_email'],
            ];

            if (!empty($validated['admin_password'])) {
                $adminData['password'] = $validated['admin_password'];
                $adminData['plain_password'] = $validated['admin_password'];
            }

            if ($admin) {
                $admin->update($adminData);
            } else {
                \App\Models\User::create($adminData + [
                    'role_id' => 2,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật chi nhánh thành công!',
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'email' => $branch->email,
                    'phone' => $branch->phone,
                    'address' => $branch->address,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                    'status' => (bool) $branch->status,
                ],
            ]);
        }

        $redirectRoute = $request->input('return_to') === 'super-admin' || $request->input('form_type') === 'branch-edit'
            ? 'admin.super-admin'
            : 'admin.branches.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Cập nhật chi nhánh thành công!');
    }

    private function coordinatesFromMapLink(?string $mapLink): ?array
    {
        $mapLink = trim((string) $mapLink);

        if ($mapLink === '') {
            return null;
        }

        $directCoordinates = $this->extractCoordinatesFromString($mapLink);
        if ($directCoordinates) {
            return $directCoordinates;
        }

        $resolvedUrl = $this->resolveFinalMapUrl($mapLink);
        if ($resolvedUrl) {
            $resolvedCoordinates = $this->extractCoordinatesFromString($resolvedUrl);
            if ($resolvedCoordinates) {
                return $resolvedCoordinates;
            }
        }

        return null;
    }

    private function extractCoordinatesFromString(string $value): ?array
    {
        $patterns = [
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|$)/',
            '/[?&](?:q|query|ll|center|destination)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:&|$)/',
            '/\/place\/[^\/]+\/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|\/|$)/',
            '/\/maps\/search\/(-?\d+(?:\.\d+)?),\+?(-?\d+(?:\.\d+)?)(?:[?\/]|$)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $value, $matches)) {
                continue;
            }

            $latitude = isset($matches[1]) ? (float) $matches[1] : null;
            $longitude = isset($matches[2]) ? (float) $matches[2] : null;

            if ($latitude === null || $longitude === null) {
                continue;
            }

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        return null;
    }

    private function resolveFinalMapUrl(string $mapLink): ?string
    {
        if (! filter_var($mapLink, FILTER_VALIDATE_URL)) {
            return null;
        }

        $effectiveUrl = null;

        try {
            Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (ChillDrink; map-link-resolver)',
                'Accept-Language' => 'vi-VN,vi;q=0.9,en;q=0.8',
            ])
                ->withOptions([
                    'allow_redirects' => [
                        'track_redirects' => true,
                    ],
                    'on_stats' => function (TransferStats $stats) use (&$effectiveUrl): void {
                        $uri = $stats->getEffectiveUri();
                        $effectiveUrl = $uri ? (string) $uri : null;
                    },
                ])
                ->timeout(10)
                ->acceptJson()
                ->get($mapLink);
        } catch (\Throwable $e) {
            return null;
        }

        return $effectiveUrl;
    }

    private function googleMapsLink(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
    }

    /**
     * Destroy the specified branch (Super Admin only) - with safety checks
     */
    public function destroy(Branch $branch)
    {
        return redirect()->route('admin.super-admin')
            ->with('error', 'Không được phép xóa chi nhánh! Bạn chỉ có thể đóng/tạm ngưng hoạt động của chi nhánh.');
    }

    /**
     * Toggle branch status (Super Admin only) - Ajax or redirect
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update(['status' => !$branch->status]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'status' => $branch->status]);
        }

        return redirect()->route('admin.super-admin')
            ->with('success', ($branch->status ? 'Kích hoạt' : 'Vô hiệu hóa') . ' chi nhánh thành công!');
    }
}
