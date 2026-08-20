<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\BranchShippingFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BranchShippingFeeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'branches' => Branch::query()
                ->select(['id', 'name', 'code'])
                ->orderBy('name')
                ->get()
                ->map(fn (Branch $branch) => [
                    'id' => (int) $branch->id,
                    'name' => (string) $branch->name,
                    'code' => (string) ($branch->code ?? ''),
                ])
                ->values(),
            'max_distance_km' => BranchShippingFee::MAX_DISTANCE_KM,
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $settings = BranchShippingFee::settingsForBranch((int) $branch->id)
            ?? array_merge(BranchShippingFee::defaults(), ['branch_id' => (int) $branch->id]);

        return response()->json([
            'branch' => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'code' => (string) ($branch->code ?? ''),
            ],
            'settings' => $settings,
            'configured' => BranchShippingFee::settingsForBranch((int) $branch->id) !== null,
        ]);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Chỉ Super Admin được cấu hình phí giao hàng.');

        $validated = $request->validate([
            'free_km' => ['required', 'numeric', 'min:0', 'max:15'],
            'fast_surcharge' => ['required', 'integer', 'min:0', 'max:500000'],
            'tiers' => ['required', 'array', 'min:1', 'max:10'],
            'tiers.*.max_cups' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'tiers.*.per_km_fee' => ['required', 'integer', 'min:0', 'max:500000'],
        ], [
            'free_km.required' => 'Vui lòng nhập số km miễn phí.',
            'free_km.max' => 'Số km miễn phí không được vượt quá 15 km.',
            'fast_surcharge.required' => 'Vui lòng nhập phụ phí giao nhanh.',
            'tiers.required' => 'Cần ít nhất một bậc số lượng cốc.',
        ]);

        $tiers = BranchShippingFee::normalizeTiers($validated['tiers']);
        $previousMax = 0;

        foreach ($tiers as $index => $tier) {
            $isLast = $index === array_key_last($tiers);
            $maxCups = $tier['max_cups'];

            if (! $isLast && $maxCups === null) {
                throw ValidationException::withMessages([
                    "tiers.{$index}.max_cups" => 'Chỉ bậc cuối cùng mới được để không giới hạn.',
                ]);
            }

            if ($maxCups !== null && $maxCups <= $previousMax) {
                throw ValidationException::withMessages([
                    "tiers.{$index}.max_cups" => 'Mốc số cốc phải tăng dần.',
                ]);
            }

            if ($maxCups !== null) {
                $previousMax = $maxCups;
            }
        }

        // Bậc cuối luôn là bậc mở để mọi số lượng cốc đều có mức giá.
        $tiers[array_key_last($tiers)]['max_cups'] = null;

        if (! Schema::hasTable('branch_shipping_fee_settings')) {
            abort(500, 'Chưa có bảng branch_shipping_fee_settings. Hãy chạy php artisan migrate.');
        }

        $payload = [
            'free_km' => round((float) $validated['free_km'], 2),
            'fast_surcharge' => (int) $validated['fast_surcharge'],
            'tiers' => json_encode($tiers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_by' => (int) $request->user()->id,
            'updated_at' => now(),
        ];

        $existing = DB::table('branch_shipping_fee_settings')
            ->where('branch_id', (int) $branch->id)
            ->exists();

        if ($existing) {
            DB::table('branch_shipping_fee_settings')
                ->where('branch_id', (int) $branch->id)
                ->update($payload);
        } else {
            DB::table('branch_shipping_fee_settings')->insert(array_merge($payload, [
                'branch_id' => (int) $branch->id,
                'created_at' => now(),
            ]));
        }

        BranchShippingFee::forgetBranch((int) $branch->id);
        $settings = BranchShippingFee::settingsForBranch((int) $branch->id);

        return response()->json([
            'message' => "Đã lưu phí giao hàng cho {$branch->name}.",
            'branch' => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'code' => (string) ($branch->code ?? ''),
            ],
            'settings' => $settings,
        ]);
    }

    public function preview(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'distance_km' => ['required', 'numeric', 'min:0', 'max:15'],
            'cup_count' => ['required', 'integer', 'min:1', 'max:9999'],
            'method' => ['nullable', 'string'],
        ]);

        return response()->json([
            'quote' => BranchShippingFee::preview(
                (int) $branch->id,
                (float) $validated['distance_km'],
                (int) $validated['cup_count'],
                (string) ($validated['method'] ?? 'standard'),
            ),
        ]);
    }
}
