<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:branches,code',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|boolean',
        ], [
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $coordinates = $this->coordinatesFromMapLink($request->input('map_link'));
        if ($request->filled('map_link') && ! $coordinates) {
            throw ValidationException::withMessages([
                'map_link' => 'Không đọc được tọa độ từ link Google Maps. Hãy dán link có chứa tọa độ.',
            ])->errorBag('createBranch');
        }

        if ($coordinates) {
            $validated['latitude'] = $coordinates['latitude'];
            $validated['longitude'] = $coordinates['longitude'];
        }

        if (
            ! isset($validated['latitude'], $validated['longitude'])
            || $validated['latitude'] === null
            || $validated['longitude'] === null
        ) {
            throw ValidationException::withMessages([
                'map_link' => 'Vui lòng dán link Google Maps có chứa tọa độ để lấy vị trí chi nhánh.',
            ])->errorBag('createBranch');
        }

        $validated['status'] = $request->boolean('status', true);

        Branch::create($validated);

        $redirectRoute = $request->input('return_to') === 'super-admin'
            ? 'admin.super-admin'
            : 'admin.branches.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Thêm chi nhánh thành công!');
    }

    /**
     * Update the specified branch (Super Admin only)
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validateWithBag('editBranch', [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:branches,code,' . $branch->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|boolean',
        ], [
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $coordinates = $this->coordinatesFromMapLink($request->input('map_link'));
        if ($request->filled('map_link') && ! $coordinates && (! $request->filled('latitude') || ! $request->filled('longitude'))) {
            throw ValidationException::withMessages([
                'map_link' => 'Không đọc được tọa độ từ link Google Maps. Hãy dán link có chứa tọa độ.',
            ])->errorBag('editBranch');
        }

        if ($coordinates) {
            $validated['latitude'] = $coordinates['latitude'];
            $validated['longitude'] = $coordinates['longitude'];
        }

        $validated['status'] = $request->boolean('status', true);

        $branch->update($validated);

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
                    'map_link' => $this->googleMapsLink($branch->latitude, $branch->longitude),
                    'status' => (bool) $branch->status,
                ],
            ]);
        }

        return redirect()->route('admin.branches.index')
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
        // Check if branch has users assigned
        if ($branch->users()->exists()) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Không thể xóa! Chi nhánh này đang có nhân viên được gán.');
        }

        // Check if branch has orders
        if ($branch->orders()->exists()) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Không thể xóa! Chi nhánh này đang có đơn hàng liên quan.');
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Xóa chi nhánh thành công!');
    }

    /**
     * Toggle branch status (Super Admin only) - Ajax or redirect
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update(['status' => ! $branch->status]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'status' => $branch->status]);
        }

        return redirect()->route('admin.branches.index')
            ->with('success', ($branch->status ? 'Kích hoạt' : 'Vô hiệu hóa') . ' chi nhánh thành công!');
    }
}
