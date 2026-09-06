<?php

namespace App\Services;

use App\Events\ProductAvailabilityUpdated;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProductAvailabilityService
{
    public function currentBranch(): ?Branch
    {
        $branchId = session('nearest_branch_id');
        $branch = $branchId
            ? Branch::query()->whereKey($branchId)->where('status', true)->first()
            : null;

        if (! $branch) {
            $branch = Branch::query()->where('status', true)->orderBy('id')->first();

            if ($branch) {
                session(['nearest_branch_id' => $branch->id]);
            }
        }

        return $branch;
    }

    public function statusFor(Product|int $product, Branch|int $branch, bool $lock = false): ?BranchProductStatus
    {
        $query = BranchProductStatus::query()
            ->where('product_id', $product instanceof Product ? $product->id : $product)
            ->where('branch_id', $branch instanceof Branch ? $branch->id : $branch);

        if ($lock) {
            $query->sharedLock();
        }

        return $query->first();
    }

    public function assertAvailable(Product $product, Branch $branch, bool $lock = false): BranchProductStatus
    {
        $status = $this->statusFor($product, $branch, $lock);

        if (! $status) {
            throw new RuntimeException("Sản phẩm {$product->name} hiện chưa được phục vụ tại Chi nhánh {$branch->name}.");
        }

        if (! $status->is_available) {
            throw new RuntimeException("Sản phẩm {$product->name} hiện đã hết hàng tại Chi nhánh {$branch->name}.");
        }

        return $status;
    }

    public function assertCartAvailable(array $cart, Branch $branch, bool $lock = false): void
    {
        foreach ($cart as $item) {
            $productId = $item['product_id'] ?? null;

            if (! is_numeric($productId)) {
                throw new RuntimeException('Giỏ hàng có sản phẩm không hợp lệ. Vui lòng cập nhật lại giỏ hàng.');
            }

            $product = Product::query()
                ->whereKey((int) $productId)
                ->where('status', true)
                ->first();

            if (! $product) {
                throw new RuntimeException('Một sản phẩm trong giỏ đã ngừng bán. Vui lòng cập nhật lại giỏ hàng.');
            }

            try {
                $this->assertAvailable($product, $branch, $lock);
            } catch (RuntimeException $exception) {
                throw new RuntimeException($exception->getMessage().' Vui lòng cập nhật lại giỏ hàng.');
            }
        }
    }

    public function syncProduct(Product $product, array $branchStatuses): void
    {
        $normalized = collect($branchStatuses)
            ->filter(fn ($value, $branchId) => is_numeric($branchId))
            ->mapWithKeys(fn ($value, $branchId) => [(int) $branchId => filter_var($value, FILTER_VALIDATE_BOOL)])
            ->all();

        BranchProductStatus::query()
            ->where('product_id', $product->id)
            ->whereNotIn('branch_id', array_keys($normalized))
            ->delete();

        foreach ($normalized as $branchId => $isAvailable) {
            BranchProductStatus::query()->updateOrCreate(
                ['branch_id' => $branchId, 'product_id' => $product->id],
                ['is_available' => $isAvailable]
            );
        }
    }

    public function update(Product $product, Branch $branch, bool $isAvailable): BranchProductStatus
    {
        $status = DB::transaction(fn () => BranchProductStatus::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            ['is_available' => $isAvailable]
        ));

        try {
            event(new ProductAvailabilityUpdated($status));
        } catch (Throwable $exception) {
            // Realtime is an enhancement: a stopped Reverb/Pusher server must not
            // turn a successful availability update into an HTTP 500 response.
            report($exception);
        }

        return $status;
    }

    public function mapFor(Collection $products, ?Branch $branch): Collection
    {
        if (! $branch || $products->isEmpty()) {
            return collect();
        }

        return BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->whereIn('product_id', $products->pluck('id')->filter())
            ->get()
            ->keyBy('product_id');
    }
}
