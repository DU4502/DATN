<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardDrilldownService
{
    public function detail(Request $request, array $input): array
    {
        [$from, $to] = $this->range($input);
        $branchId = $this->authorizedBranchId($request, $input);
        $metric = (string) $input['metric'];
        $search = (string) ($input['search'] ?? '');
        $perPage = (int) ($input['per_page'] ?? 20);

        return match ($metric) {
            'new_customers' => $this->newCustomers($from, $to, $branchId, $search, $perPage),
            'products' => $this->products($from, $to, $search, $perPage),
            'items_sold', 'product_sales', 'product_revenue' => $this->itemSales($request, $from, $to, $branchId, $input, $search, $perPage),
            'product_cancellation_rate' => $this->productCancellation($request, $from, $to, $branchId, $input, $search, $perPage),
            'product_reviews' => $this->productReviews($request, $from, $to, $branchId, $input, $search, $perPage),
            'customers' => $this->customers($request, $from, $to, $branchId, $search, $perPage),
            'cancelled_orders', 'cancellation_rate' => $this->cancelled($request, $from, $to, $branchId, $metric, $search, $perPage),
            'total_orders' => $this->allOrders($request, $from, $to, $branchId, $search, $perPage),
            default => $this->completedOrders($request, $from, $to, $branchId, $metric, $search, $perPage),
        };
    }

    private function allOrders(Request $request, Carbon $from, Carbon $to, ?int $branchId, string $search, int $perPage): array
    {
        $base = $this->orders($from, $to, $branchId);
        $count = (clone $base)->count();
        $query = $this->searchOrders($base, $search)
            ->with(['user:id,name', 'branch:id,name,code', 'voucher:id,code'])
            ->select(['orders.id', 'orders.order_code', 'orders.user_id', 'orders.guest_name', 'orders.branch_id', 'orders.coupon_id', 'orders.created_at', 'orders.subtotal', 'orders.discount', 'orders.shipping_fee', 'orders.total', 'orders.status'])
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id');

        return $this->payload(
            'Chi tiết tổng đơn hàng',
            $from,
            $to,
            $branchId,
            $count,
            "Có {$count} đơn hàng được tạo trong khoảng thời gian đang xem, không phân biệt trạng thái.",
            ['order_count' => $count],
            $this->orderPaginator($request, $query, $perPage)
        );
    }

    private function completedOrders(Request $request, Carbon $from, Carbon $to, ?int $branchId, string $metric, string $search, int $perPage): array
    {
        $base = $this->orders($from, $to, $branchId)->where('orders.status', OrderStatus::COMPLETED);
        $summary = (clone $base)->selectRaw('COUNT(*) as row_count, COALESCE(SUM(orders.total), 0) as amount')->first();
        $count = (int) ($summary?->row_count ?? 0);
        $revenue = (int) round((float) ($summary?->amount ?? 0));
        $value = match ($metric) {
            'revenue' => $revenue,
            'average_order_value' => $count > 0 ? (int) round($revenue / $count) : 0,
            default => $count,
        };

        $query = $this->searchOrders(clone $base, $search)
            ->with(['user:id,name', 'branch:id,name,code', 'voucher:id,code'])
            ->select(['orders.id', 'orders.order_code', 'orders.user_id', 'orders.guest_name', 'orders.branch_id', 'orders.coupon_id', 'orders.created_at', 'orders.subtotal', 'orders.discount', 'orders.shipping_fee', 'orders.total', 'orders.status'])
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id');

        return $this->payload(
            $metric === 'average_order_value' ? 'Chi tiết giá trị đơn hàng trung bình' : ($metric === 'revenue' ? 'Chi tiết doanh thu' : 'Chi tiết đơn hàng hoàn thành'),
            $from,
            $to,
            $branchId,
            $value,
            match ($metric) {
                'average_order_value' => $count > 0
                    ? "{$count} đơn hàng hoàn thành tạo ra tổng doanh thu {$this->money($revenue)}. Lấy {$this->money($revenue)} chia cho {$count} đơn hàng = {$this->money($value)}/đơn."
                    : 'Không có đơn hàng hoàn thành trong khoảng thời gian này nên chưa phát sinh giá trị đơn hàng trung bình.',
                'revenue' => "Cộng giá trị của {$count} đơn hàng ở trạng thái Hoàn thành trong khoảng thời gian này = {$this->money($revenue)}.",
                default => "Có {$count} đơn hàng ở trạng thái Hoàn thành trong khoảng thời gian đang xem.",
            },
            ['revenue' => $revenue, 'order_count' => $count, 'eligible_statuses' => [OrderStatus::COMPLETED]],
            $this->orderPaginator($request, $query, $perPage)
        );
    }

    private function cancelled(Request $request, Carbon $from, Carbon $to, ?int $branchId, string $metric, string $search, int $perPage): array
    {
        $all = $this->orders($from, $to, $branchId);
        $denominator = (clone $all)->count();
        $cancelled = (clone $all)->where('orders.status', OrderStatus::CANCELLED);
        $cancelledCount = (clone $cancelled)->count();
        $rate = $denominator > 0 ? round(($cancelledCount / $denominator) * 100, 1) : 0.0;
        $query = $this->searchOrders($cancelled, $search)
            ->with(['user:id,name', 'branch:id,name,code', 'statusChangedBy:id,name'])
            ->select(['orders.id', 'orders.order_code', 'orders.user_id', 'orders.guest_name', 'orders.branch_id', 'orders.created_at', 'orders.updated_at', 'orders.status_changed_at', 'orders.status_changed_by', 'orders.cancellation_reason', 'orders.total', 'orders.status'])
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id');

        return $this->payload(
            $metric === 'cancellation_rate' ? 'Chi tiết tỷ lệ hủy đơn' : 'Chi tiết đơn đã hủy',
            $from,
            $to,
            $branchId,
            $metric === 'cancellation_rate' ? $rate : $cancelledCount,
            $metric === 'cancellation_rate'
                ? ($denominator > 0
                    ? "Trong {$denominator} đơn hàng được tính có {$cancelledCount} đơn đã hủy. {$cancelledCount} đơn đã hủy ÷ {$denominator} đơn hàng × 100 = ".number_format($rate, 1, ',', '').'%.'
                    : 'Không có đơn hàng nào trong khoảng thời gian này nên tỷ lệ hủy đơn là 0%.')
                : "Có {$cancelledCount} đơn hàng ở trạng thái Đã hủy trong tổng số {$denominator} đơn được tạo trong khoảng thời gian này.",
            ['cancelled_count' => $cancelledCount, 'denominator_count' => $denominator, 'rate' => $rate, 'eligible_statuses' => [OrderStatus::CANCELLED]],
            $this->orderPaginator($request, $query, $perPage)
        );
    }

    private function itemSales(Request $request, Carbon $from, Carbon $to, ?int $branchId, array $input, string $search, int $perPage): array
    {
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : null;
        $context = $this->productBranchContext($productId, $branchId);
        $base = $this->completedProductItemsQuery($from, $to, $branchId, $productId);
        $quantity = (int) (clone $base)->sum('order_items.quantity');
        $itemSummary = (clone $base)
            ->selectRaw('COALESCE(SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)), 0) as revenue')
            ->first();
        $revenue = (int) round((float) ($itemSummary?->revenue ?? 0));
        $orderCount = (clone $base)->distinct()->count('orders.id');

        $query = (clone $base)
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id')
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $nested) use ($search) {
                $nested->where('orders.order_code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%");
            }))
            ->selectRaw('order_items.id, order_items.order_id, order_items.product_id, products.name as product_name, order_items.quantity, COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price) as contribution, orders.order_code, orders.created_at, orders.status, orders.branch_id, branches.name as branch_name')
            ->orderByDesc('orders.created_at')->orderByDesc('order_items.id');
        $paginator = $query->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn ($row) => [
            'id' => (int) $row->id,
            'product_id' => (int) $row->product_id,
            'product_name' => $row->product_name ?: 'Sản phẩm đã xóa',
            'quantity' => (int) $row->quantity,
            'contribution' => (int) round((float) $row->contribution),
            'order_code' => $row->order_code ?: '#'.$row->order_id,
            'created_at' => optional($row->created_at)->format('d/m/Y H:i'),
            'branch_name' => $row->branch_name,
            'status' => $row->status,
            'status_label' => OrderStatus::label((string) $row->status),
            'order_url' => $this->orderUrl($request, (int) $row->order_id, (string) ($row->order_code ?: $row->order_id)),
        ])->all();

        $isRevenue = ($input['metric'] ?? '') === 'product_revenue';
        if ($productId !== null) {
            $context['overview'] = $this->productBranchOverview($from, $to, $branchId, $productId, [
                'quantity' => $quantity,
                'revenue' => $revenue,
                'completed_order_count' => $orderCount,
            ]);
        }

        $title = $this->productBranchTitle(
            $isRevenue ? 'Chi tiết doanh thu' : ($productId !== null ? 'Chi tiết số lượng bán' : 'Chi tiết sản phẩm bán ra'),
            $context
        );
        $productName = $context['product']['name'] ?? 'các sản phẩm';
        $branchName = $context['branch']['name'] ?? 'phạm vi đang xem';

        return $this->payload($title, $from, $to, $branchId, $isRevenue ? $revenue : $quantity, $isRevenue
            ? "Cộng giá trị của {$productName} trong {$orderCount} đơn hàng hoàn thành tại {$branchName} = {$this->money($revenue)}."
            : "Có {$quantity} sản phẩm {$productName} được bán qua {$orderCount} đơn hàng hoàn thành tại {$branchName} trong khoảng thời gian này.", [
                'quantity' => $quantity, 'revenue' => $revenue, 'order_count' => $orderCount, 'product_id' => $productId, 'eligible_statuses' => [OrderStatus::COMPLETED],
            ], $this->pagination($paginator, $rows), 'items', $context);
    }

    private function productCancellation(Request $request, Carbon $from, Carbon $to, ?int $branchId, array $input, string $search, int $perPage): array
    {
        $productId = (int) ($input['product_id'] ?? 0);
        $context = $this->productBranchContext($productId, $branchId);
        $base = $this->productRelatedOrdersQuery($from, $to, $branchId, $productId);
        $denominator = (clone $base)->count();
        $cancelled = (clone $base)->where('orders.status', OrderStatus::CANCELLED);
        $cancelledCount = (clone $cancelled)->count();
        $rate = $denominator > 0 ? round(($cancelledCount / $denominator) * 100, 1) : 0.0;
        $context['overview'] = $this->productBranchOverview($from, $to, $branchId, $productId, [
            'related_order_count' => $denominator,
            'cancelled_order_count' => $cancelledCount,
            'cancellation_rate' => $rate,
        ]);
        $query = $this->searchOrders($cancelled, $search)
            ->with(['user:id,name', 'branch:id,name,code', 'statusChangedBy:id,name'])
            ->select(['orders.id', 'orders.order_code', 'orders.user_id', 'orders.guest_name', 'orders.branch_id', 'orders.created_at', 'orders.updated_at', 'orders.status_changed_at', 'orders.status_changed_by', 'orders.cancellation_reason', 'orders.total', 'orders.status'])
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id');
        $productName = $context['product']['name'] ?? 'sản phẩm đã chọn';
        $branchName = $context['branch']['name'] ?? 'phạm vi đang xem';
        $formula = $denominator > 0
            ? "Trong {$denominator} đơn hàng có {$productName} tại {$branchName}, có {$cancelledCount} đơn đã bị hủy. {$cancelledCount} đơn đã hủy ÷ {$denominator} đơn được tính × 100 = ".number_format($rate, 1, ',', '').'%.'
            : "Không có đơn hàng nào chứa {$productName} tại {$branchName} trong khoảng thời gian này nên tỷ lệ hủy là 0%.";

        return $this->payload(
            $this->productBranchTitle('Chi tiết tỷ lệ hủy', $context),
            $from,
            $to,
            $branchId,
            $rate,
            $formula,
            ['cancelled_count' => $cancelledCount, 'denominator_count' => $denominator, 'rate' => $rate, 'product_id' => $productId, 'eligible_statuses' => [OrderStatus::CANCELLED]],
            $this->orderPaginator($request, $query, $perPage),
            'orders',
            $context
        );
    }

    private function productReviews(Request $request, Carbon $from, Carbon $to, ?int $branchId, array $input, string $search, int $perPage): array
    {
        $productId = (int) ($input['product_id'] ?? 0);
        $context = $this->productBranchContext($productId, $branchId);
        $base = $this->productReviewQuery($from, $to, $branchId, $productId);
        $summary = (clone $base)->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')->first();
        $reviewCount = (int) ($summary?->review_count ?? 0);
        $averageRating = $reviewCount > 0 ? round((float) ($summary?->average_rating ?? 0), 1) : 0.0;
        $context['overview'] = $this->productBranchOverview($from, $to, $branchId, $productId, [
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
        ]);
        $query = (clone $base)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('reviews.comment', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn (Builder $order) => $order->where('order_code', 'like', "%{$search}%"));
            }))
            ->with(['user:id,name', 'order:id,order_code,branch_id', 'order.branch:id,name,code'])
            ->select(['reviews.id', 'reviews.user_id', 'reviews.order_id', 'reviews.rating', 'reviews.comment', 'reviews.created_at'])
            ->orderByDesc('reviews.created_at')->orderByDesc('reviews.id');
        $paginator = $query->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn (Review $review) => [
            'id' => (int) $review->id,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->format('d/m/Y H:i'),
            'customer_name' => $review->user?->name ?: 'Khách hàng',
            'order_code' => $review->order?->displayCode() ?: '—',
            'branch_name' => $review->order?->branch?->name,
            'order_url' => $review->order ? $this->orderUrl($request, $review->order->id, $review->order->displayCode()) : null,
        ])->all();

        return $this->payload(
            $this->productBranchTitle('Chi tiết đánh giá', $context),
            $from,
            $to,
            $branchId,
            $averageRating,
            $reviewCount > 0
                ? 'Điểm trung bình được tính từ '.number_format($reviewCount).' lượt đánh giá đã gửi trong khoảng thời gian này.'
                : 'Chưa có đánh giá phù hợp trong khoảng thời gian này.',
            ['review_count' => $reviewCount, 'average_rating' => $averageRating, 'product_id' => $productId],
            $this->pagination($paginator, $rows),
            'reviews',
            $context
        );
    }

    private function customers(Request $request, Carbon $from, Carbon $to, ?int $branchId, string $search, int $perPage): array
    {
        $base = User::query()->join('orders', 'orders.user_id', '=', 'users.id')
            ->where('orders.status', OrderStatus::COMPLETED)->whereBetween('orders.created_at', [$from, $to])
            ->when($branchId, fn (Builder $query, int $id) => $query->where('orders.branch_id', $id));
        $count = (clone $base)->distinct()->count('users.id');
        $query = (clone $base)->when($search !== '', fn (Builder $q) => $q->where('users.name', 'like', "%{$search}%"))
            ->selectRaw('users.id, users.name, MIN(orders.created_at) as first_order_at, COUNT(DISTINCT orders.id) as order_count, SUM(orders.total) as revenue')
            ->groupBy('users.id', 'users.name')->orderByDesc('revenue');
        $paginator = $query->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn ($row) => [
            'id' => (int) $row->id, 'name' => $row->name, 'first_order_at' => Carbon::parse($row->first_order_at)->format('d/m/Y H:i'),
            'order_count' => (int) $row->order_count, 'revenue' => (int) round((float) $row->revenue),
        ])->all();

        return $this->payload('Chi tiết khách hàng mua hàng', $from, $to, $branchId, $count, "Có {$count} khách hàng khác nhau có ít nhất một đơn hàng hoàn thành trong khoảng thời gian này.", ['customer_count' => $count], $this->pagination($paginator, $rows), 'customers');
    }

    private function newCustomers(Carbon $from, Carbon $to, ?int $branchId, string $search, int $perPage): array
    {
        $query = User::customers()->whereBetween('created_at', [$from, $to])
            ->when($branchId, fn (Builder $query, int $id) => $query->whereHas('orders', fn (Builder $orders) => $orders
                ->where('branch_id', $id)
                ->where('status', OrderStatus::COMPLETED)
                ->whereBetween('created_at', [$from, $to])))
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        $count = (clone $query)->count();
        $paginator = $query->select(['id', 'name', 'created_at'])->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'created_at' => $user->created_at?->format('d/m/Y H:i')])->all();

        return $this->payload('Chi tiết khách hàng mới', $from, $to, $branchId, $count, $branchId
            ? "Có {$count} khách hàng tạo tài khoản và phát sinh đơn hoàn thành tại chi nhánh này trong khoảng thời gian đang xem."
            : "Có {$count} khách hàng tạo tài khoản trong khoảng thời gian đang xem.", ['customer_count' => $count], $this->pagination($paginator, $rows), 'customers');
    }

    private function products(Carbon $from, Carbon $to, string $search, int $perPage): array
    {
        $query = Product::query()->where('created_at', '<=', $to)->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        $count = (clone $query)->count();
        $paginator = $query->select(['id', 'name', 'sku', 'status', 'created_at'])->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'status' => (bool) $product->status, 'created_at' => $product->created_at?->format('d/m/Y H:i')])->all();

        return $this->payload('Chi tiết sản phẩm trong menu', $from, $to, null, $count, "Có {$count} sản phẩm được tạo tính đến cuối khoảng thời gian đang xem.", ['product_count' => $count], $this->pagination($paginator, $rows), 'products');
    }

    private function orders(Carbon $from, Carbon $to, ?int $branchId): Builder
    {
        return Order::query()->whereBetween('orders.created_at', [$from, $to])
            ->when($branchId, fn (Builder $query, int $id) => $query->where('orders.branch_id', $id));
    }

    private function searchOrders(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', fn (Builder $q) => $q->where(function (Builder $nested) use ($search) {
            $nested->where('orders.order_code', 'like', "%{$search}%")
                ->orWhere('orders.guest_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"));
        }));
    }

    private function orderPaginator(Request $request, Builder $query, int $perPage): array
    {
        $paginator = $query->paginate($perPage)->withQueryString();
        $rows = collect($paginator->items())->map(fn (Order $order) => [
            'id' => $order->id,
            'order_code' => $order->displayCode(),
            'customer_name' => $order->customerName() ?: 'Khách vãng lai',
            'branch_name' => $order->branch?->name,
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'subtotal' => (int) ($order->subtotal ?? 0),
            'discount' => (int) ($order->discount ?? 0),
            'shipping_fee' => (int) ($order->shipping_fee ?? 0),
            'contribution' => (int) ($order->total ?? 0),
            'status' => $order->status,
            'status_label' => OrderStatus::label((string) $order->status),
            'voucher_code' => $order->voucher?->code,
            'cancellation_reason' => $order->cancellation_reason,
            'cancelled_at' => ($order->status_changed_at ?? $order->updated_at)?->format('d/m/Y H:i'),
            'cancelled_by' => $order->statusChangedBy?->name,
            'order_url' => $this->orderUrl($request, $order->id, $order->displayCode()),
        ])->all();

        return $this->pagination($paginator, $rows);
    }

    private function pagination(LengthAwarePaginator $paginator, array $rows): array
    {
        return ['rows' => $rows, 'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }

    private function payload(string $title, Carbon $from, Carbon $to, ?int $branchId, int|float $value, string $formula, array $summary, array $data, string $rowType = 'orders', array $context = []): array
    {
        $branch = $context['branch'] ?? ($branchId && $branchId > 0
            ? Branch::query()->select(['id', 'name', 'code'])->find($branchId)
            : null);
        $branchData = $branch instanceof Branch
            ? ['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code]
            : $branch;

        return [
            'title' => $title,
            'period' => ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s'), 'label' => $from->format('d/m/Y H:i').' – '.$to->format('d/m/Y H:i')],
            'branch_id' => $branchId,
            'branch' => $branchData,
            'product' => $context['product'] ?? null,
            'overview' => $context['overview'] ?? null,
            'value' => $value,
            'formula' => $formula,
            'summary' => $summary,
            'row_type' => $rowType,
            'data' => $data,
        ];
    }

    private function range(array $input): array
    {
        $timezone = (string) config('app.timezone', 'Asia/Ho_Chi_Minh');
        $firstTimestamp = null;
        if (! isset($input['from'])) {
            $firstTimestamp = match ((string) ($input['metric'] ?? '')) {
                'new_customers' => User::customers()->min('created_at'),
                'products' => Product::query()->min('created_at'),
                default => Order::query()->min('created_at'),
            };
        }
        $from = isset($input['from'])
            ? Carbon::createFromFormat('Y-m-d H:i:s', $input['from'], $timezone)
            : ($firstTimestamp ? Carbon::parse($firstTimestamp, $timezone)->startOfSecond() : Carbon::now($timezone)->startOfDay());
        $to = isset($input['to']) ? Carbon::createFromFormat('Y-m-d H:i:s', $input['to'], $timezone) : Carbon::now($timezone);
        $now = Carbon::now($timezone);

        $effectiveTo = $to->greaterThan($now) && $from->lessThanOrEqualTo($now) ? $now : $to;

        return [$from, $effectiveTo];
    }

    private function authorizedBranchId(Request $request, array $input): ?int
    {
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            return $user->branch_id ? (int) $user->branch_id : -1;
        }

        if ($user->isViewingAdminWorkspace()) {
            return $user->adminWorkspaceBranchId() ?: -1;
        }

        return isset($input['branch_id']) ? (int) $input['branch_id'] : null;
    }

    private function orderUrl(Request $request, int $id, string $code): string
    {
        $route = $request->user()->isSuperAdmin() && ! $request->user()->isViewingAdminWorkspace()
            ? 'admin.super-admin.manage.orders.index'
            : 'admin.orders.index';

        return route($route, ['q' => ltrim($code, '#'), 'trace_order' => $id], false);
    }

    private function money(int $value): string
    {
        return number_format($value, 0, ',', '.').'đ';
    }

    private function productBranchContext(?int $productId, ?int $branchId): array
    {
        $product = $productId
            ? Product::withTrashed()->select(['id', 'name', 'sku'])->find($productId)
            : null;
        $branch = $branchId && $branchId > 0
            ? Branch::query()->select(['id', 'name', 'code'])->find($branchId)
            : null;

        return [
            'product' => $product ? ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku] : null,
            'branch' => $branch,
        ];
    }

    private function productBranchTitle(string $prefix, array $context): string
    {
        $productName = $context['product']['name'] ?? null;
        $branch = $context['branch'] ?? null;
        $branchName = $branch instanceof Branch ? $branch->name : ($branch['name'] ?? null);

        return collect([$prefix, $productName, $branchName ? 'tại '.$branchName : null])
            ->filter()
            ->implode(' — ');
    }

    private function completedProductItemsQuery(Carbon $from, Carbon $to, ?int $branchId, ?int $productId): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatus::COMPLETED)
            ->whereBetween('orders.created_at', [$from, $to])
            ->when($branchId, fn (Builder $query, int $id) => $query->where('orders.branch_id', $id))
            ->when($productId, fn (Builder $query, int $id) => $query->where('order_items.product_id', $id));
    }

    private function productRelatedOrdersQuery(Carbon $from, Carbon $to, ?int $branchId, int $productId): Builder
    {
        return $this->orders($from, $to, $branchId)
            ->whereHas('orderItems', fn (Builder $query) => $query->where('product_id', $productId));
    }

    private function productReviewQuery(Carbon $from, Carbon $to, ?int $branchId, int $productId): Builder
    {
        return Review::query()
            ->where('reviews.product_id', $productId)
            ->where('reviews.status', true)
            ->whereBetween('reviews.created_at', [$from, $to])
            ->whereHas('order', function (Builder $query) use ($branchId, $productId): void {
                $query->when($branchId, fn (Builder $branchQuery, int $id) => $branchQuery->where('branch_id', $id))
                    ->whereHas('orderItems', fn (Builder $itemQuery) => $itemQuery->where('product_id', $productId));
            });
    }

    private function productBranchOverview(Carbon $from, Carbon $to, ?int $branchId, int $productId, array $known = []): array
    {
        if (! array_key_exists('quantity', $known)) {
            $sales = $this->completedProductItemsQuery($from, $to, $branchId, $productId)
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity, COALESCE(SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)), 0) as revenue, COUNT(DISTINCT orders.id) as completed_order_count')
                ->first();
            $known['quantity'] = (int) ($sales?->quantity ?? 0);
            $known['revenue'] = (int) round((float) ($sales?->revenue ?? 0));
            $known['completed_order_count'] = (int) ($sales?->completed_order_count ?? 0);
        }

        if (! array_key_exists('related_order_count', $known)) {
            $relatedOrders = $this->productRelatedOrdersQuery($from, $to, $branchId, $productId);
            $known['related_order_count'] = (clone $relatedOrders)->count();
            $known['cancelled_order_count'] = (clone $relatedOrders)->where('orders.status', OrderStatus::CANCELLED)->count();
            $known['cancellation_rate'] = $known['related_order_count'] > 0
                ? round(($known['cancelled_order_count'] / $known['related_order_count']) * 100, 1)
                : 0.0;
        }

        if (! array_key_exists('review_count', $known)) {
            $reviews = $this->productReviewQuery($from, $to, $branchId, $productId)
                ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
                ->first();
            $known['review_count'] = (int) ($reviews?->review_count ?? 0);
            $known['average_rating'] = $known['review_count'] > 0
                ? round((float) ($reviews?->average_rating ?? 0), 1)
                : null;
        }

        return $known;
    }
}
