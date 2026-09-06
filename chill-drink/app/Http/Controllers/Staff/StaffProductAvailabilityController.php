<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffProductAvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $this->staffBranch($request);
        $search = trim((string) $request->query('q', ''));
        $availability = (string) $request->query('availability', '');

        $products = Product::query()
            ->where('status', true)
            ->whereHas('branchStatuses', fn ($query) => $query->where('branch_id', $branch->id))
            ->with([
                'category',
                'branchStatuses' => fn ($query) => $query->where('branch_id', $branch->id),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(in_array($availability, ['0', '1'], true), function ($query) use ($availability, $branch) {
                $query->whereHas('branchStatuses', fn ($status) => $status
                    ->where('branch_id', $branch->id)
                    ->where('is_available', $availability === '1'));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('staff.products.availability', compact('branch', 'products', 'search', 'availability'));
    }

    public function update(
        Request $request,
        Product $product,
        ProductAvailabilityService $availabilityService
    ): JsonResponse {
        $branch = $this->staffBranch($request);
        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
            'branch_id' => ['prohibited'],
        ], [
            'branch_id.prohibited' => 'Chi nhánh được xác định từ tài khoản Nhân viên và không thể thay đổi từ request.',
        ]);

        abort_unless($product->status, 404);
        abort_unless(BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->exists(), 404);

        $status = $availabilityService->update($product, $branch, (bool) $validated['is_available']);

        return response()->json([
            'message' => $status->is_available ? 'Đã mở bán lại sản phẩm.' : 'Đã đánh dấu sản phẩm tạm hết hàng.',
            'product_id' => (int) $product->id,
            'branch_id' => (int) $branch->id,
            'branch_name' => $branch->name,
            'is_available' => (bool) $status->is_available,
        ]);
    }

    private function staffBranch(Request $request): Branch
    {
        $branchId = $request->user()?->branch_id;
        abort_unless(is_numeric($branchId), 403, 'Tài khoản Nhân viên chưa được gán chi nhánh.');

        return Branch::query()->findOrFail((int) $branchId);
    }
}
