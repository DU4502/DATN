<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\ShipperCodReceivable;
use App\Models\ShipperCodSettlement;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ShipperCodService
{
    /** @var array<int, bool> */
    private array $syncedShippers = [];

    /**
     * Ghi nhận tiền mặt COD đang nằm ở shipper sau khi giao thành công.
     * Idempotent theo order_id để mọi đường hoàn tất đơn có thể gọi an toàn.
     */
    public function recordCollection(Order $order, Shipper $shipper): ?ShipperCodReceivable
    {
        if (strtolower((string) $order->payment_method) !== 'cod') {
            return null;
        }

        $amount = max(0, (int) ($order->total ?? $order->total_price ?? 0));
        if ($amount <= 0) {
            return null;
        }

        return ShipperCodReceivable::query()->firstOrCreate(
            ['order_id' => (int) $order->id],
            [
                'order_code' => $order->displayCode(),
                'shipper_id' => (int) $shipper->id,
                'order_branch_id' => $shipper->homeBranchId() ?: ($order->branch_id ? (int) $order->branch_id : null),
                'amount' => $amount,
                'collected_at' => $order->delivered_at ?: now(),
            ]
        );
    }

    /**
     * Đồng bộ các đơn COD lịch sử đã giao/hoàn thành nhưng chưa có receivable.
     *
     * Quy tắc nghiệp vụ: một đơn COD đã đi tới DELIVERED/COMPLETED và có shipper
     * thì tiền mặt được xem là đã được shipper thu từ khách. Nếu chưa có bản ghi
     * đối soát cho order đó, số tiền vẫn là công nợ phải nộp về công ty.
     *
     * Idempotent nhờ unique(order_id) trên shipper_cod_receivables.
     */
    public function syncHistoricalReceivablesForShipper(int|Shipper $shipper): int
    {
        $shipperModel = $shipper instanceof Shipper
            ? $shipper
            : Shipper::query()->find((int) $shipper);

        if (! $shipperModel || ! Schema::hasTable('shipper_cod_receivables')) {
            return 0;
        }

        $shipperId = (int) $shipperModel->id;
        if (isset($this->syncedShippers[$shipperId])) {
            return 0;
        }
        $this->syncedShippers[$shipperId] = true;

        $existingOrderIds = ShipperCodReceivable::query()
            ->where('shipper_id', $shipperId)
            ->pluck('order_id');

        $orders = Order::query()
            ->where('shipper_id', $shipperId)
            ->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', ['cod'])
            ->where(function ($query) {
                $query->whereNull('fulfillment_type')
                    ->orWhere('fulfillment_type', 'delivery');
            })
            ->whereIn('status', [
                'delivered',
                'completed',
                'arrived', // trạng thái legacy được normalize thành delivered
            ])
            ->when($existingOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $existingOrderIds))
            ->orderBy('id')
            ->get();

        $created = 0;

        foreach ($orders as $order) {
            $amount = max(0, (int) round((float) ($order->total ?? $order->total_price ?? 0)));
            if ($amount <= 0) {
                continue;
            }

            $receivable = ShipperCodReceivable::query()->firstOrCreate(
                ['order_id' => (int) $order->id],
                [
                    'order_code' => $order->displayCode(),
                    'shipper_id' => $shipperId,
                    'order_branch_id' => $shipperModel->homeBranchId() ?: ($order->branch_id ? (int) $order->branch_id : null),
                    'amount' => $amount,
                    'collected_at' => $order->delivered_at ?: $order->updated_at ?: now(),
                ]
            );

            if ($receivable->wasRecentlyCreated) {
                $created++;
            }

            // Với COD đã giao/hoàn thành, "paid" chỉ có nghĩa khách đã trả tiền mặt
            // cho shipper. Việc công ty đã nhận hay chưa được quyết định riêng bằng
            // settlement_id của shipper_cod_receivables.
            if (strtolower((string) $order->payment_status) !== 'paid') {
                $order->forceFill(['payment_status' => 'paid'])->saveQuietly();
            }
        }

        return $created;
    }

    /**
     * Đồng bộ hàng loạt để trang Admin/Super Admin luôn nhìn thấy cả công nợ COD
     * phát sinh trước khi P14 được cài.
     *
     * @param array<int>|null $shipperIds null = toàn bộ shipper
     */
    public function syncHistoricalReceivables(?array $shipperIds = null): int
    {
        if (! Schema::hasTable('shipper_cod_receivables')) {
            return 0;
        }

        $query = Shipper::query()->select('id');
        if (is_array($shipperIds)) {
            if ($shipperIds === []) {
                return 0;
            }
            $query->whereIn('id', $shipperIds);
        }

        $created = 0;
        foreach ($query->cursor() as $shipper) {
            $created += $this->syncHistoricalReceivablesForShipper($shipper);
        }

        return $created;
    }

    public function pendingAmount(int|Shipper $shipper): int
    {
        $this->syncHistoricalReceivablesForShipper($shipper);
        $shipperId = $shipper instanceof Shipper ? (int) $shipper->id : (int) $shipper;

        return (int) round((float) ShipperCodReceivable::query()
            ->where('shipper_id', $shipperId)
            ->whereNull('settlement_id')
            ->sum('amount'));
    }

    public function pendingCount(int|Shipper $shipper): int
    {
        $this->syncHistoricalReceivablesForShipper($shipper);

        $shipperId = $shipper instanceof Shipper ? (int) $shipper->id : (int) $shipper;

        return ShipperCodReceivable::query()
            ->where('shipper_id', $shipperId)
            ->whereNull('settlement_id')
            ->count();
    }

    public function pendingItems(int|Shipper $shipper, int $limit = 10): Collection
    {
        $this->syncHistoricalReceivablesForShipper($shipper);

        $shipperId = $shipper instanceof Shipper ? (int) $shipper->id : (int) $shipper;

        return ShipperCodReceivable::query()
            ->with(['order.branch'])
            ->where('shipper_id', $shipperId)
            ->whereNull('settlement_id')
            ->orderBy('collected_at')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Admin xác nhận đã nhận TOÀN BỘ số COD shipper đang giữ tại thời điểm khóa sổ.
     */
    public function settleAll(Shipper $shipper, int $branchId, User $actor, ?string $note = null): ShipperCodSettlement
    {
        $shipper->loadMissing('user.branch');
        $homeBranchId = $shipper->homeBranchId();
        if (! $homeBranchId) {
            throw new RuntimeException('Shipper chưa có home branch nên chưa thể đối soát COD.');
        }
        if ((int) $branchId !== $homeBranchId) {
            throw new RuntimeException('COD chỉ được nộp và xác nhận tại home branch của shipper.');
        }

        return DB::transaction(function () use ($shipper, $branchId, $actor, $note) {
            $pending = ShipperCodReceivable::query()
                ->where('shipper_id', $shipper->id)
                ->whereNull('settlement_id')
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($pending->isEmpty()) {
                throw new RuntimeException('Shipper hiện không còn tiền COD phải nộp.');
            }

            $amount = (int) round((float) $pending->sum('amount'));

            $settlement = ShipperCodSettlement::query()->create([
                'shipper_id' => (int) $shipper->id,
                'branch_id' => $branchId,
                'amount' => $amount,
                'order_count' => $pending->count(),
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
                'note' => trim((string) $note) ?: null,
            ]);

            ShipperCodReceivable::query()
                ->whereIn('id', $pending->pluck('id'))
                ->update([
                    'settlement_id' => $settlement->id,
                    'settled_at' => now(),
                    'updated_at' => now(),
                ]);

            SystemLog::record(
                $actor,
                'Đã xác nhận nhận tiền COD từ shipper '.$shipper->code,
                'admin',
                'success',
                [
                    'shipper_id' => (int) $shipper->id,
                    'settlement_id' => (int) $settlement->id,
                    'branch_id' => $branchId,
                    'amount' => $amount,
                    'order_count' => $pending->count(),
                ]
            );

            return $settlement->fresh(['shipper.user', 'branch', 'confirmer']);
        }, 3);
    }
}
