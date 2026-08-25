<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductAvailabilityController extends Controller
{
    public function update(
        Request $request,
        string $productId,
        Branch $branch,
        ProductAvailabilityService $availability
    ): JsonResponse|RedirectResponse {
        $product = Product::query()
            ->whereKey($productId)
            ->orWhere('slug', $productId)
            ->firstOrFail();
        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort_unless((int) $user->branch_id === (int) $branch->id, 403);
            abort_unless(BranchProductStatus::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->exists(), 404);
        }

        $status = $availability->update($product, $branch, (bool) $validated['is_available']);
        $message = $status->is_available ? 'Đã chuyển sang Còn hàng.' : 'Đã chuyển sang Hết hàng.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'product_id' => (int) $product->id,
                'branch_id' => (int) $branch->id,
                'is_available' => (bool) $status->is_available,
            ]);
        }

        return back()->with('success', $message);
    }
}
