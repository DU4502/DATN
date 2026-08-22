<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ShipperHomeBranchService
{
    private const ACTIVE_ORDER_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
        'processing',
        'in_progress',
        'shipping',
        'shipped',
        'shipper_accepted',
    ];

    public function __construct(private readonly ShipperCodService $cod)
    {
    }

    public function homeBranchId(Shipper|User $subject): ?int
    {
        $user = $subject instanceof Shipper ? $subject->user : $subject;
        $branchId = $user?->branch_id;

        return is_numeric($branchId) && (int) $branchId > 0 ? (int) $branchId : null;
    }

    public function belongsToOrderBranch(Shipper $shipper, Order $order): bool
    {
        $homeBranchId = $this->homeBranchId($shipper);

        return $homeBranchId !== null
            && is_numeric($order->branch_id)
            && $homeBranchId === (int) $order->branch_id;
    }

    /** @return array{allowed:bool,reason:?string} */
    public function canTransfer(User $user): array
    {
        if (! $user->isShipper()) {
            return ['allowed' => false, 'reason' => 'Tài khoản này không phải shipper.'];
        }

        $shipper = $user->shipper;
        if (! $shipper) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($shipper->status === 'busy') {
            return ['allowed' => false, 'reason' => 'Shipper đang bận, chưa thể điều chuyển chi nhánh.'];
        }

        // [V26] Cho phep Super Admin doi Home Branch ngay; khong bat shipper ve chi nhanh cu truoc.

        $hasActiveOrder = Order::query()
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();
        if ($hasActiveOrder) {
            return ['allowed' => false, 'reason' => 'Shipper còn đơn đang hoạt động. Hãy hoàn tất đơn trước khi điều chuyển.'];
        }

        if (Schema::hasTable('delivery_bundle_trips')) {
            $hasBundle = DB::table('delivery_bundle_trips')
                ->where('shipper_id', $shipper->id)
                ->where('status', 'active')
                ->exists();
            if ($hasBundle) {
                return ['allowed' => false, 'reason' => 'Shipper còn chuyến ghép đang hoạt động.'];
            }
        }

        if (Schema::hasTable('shipper_cod_receivables') && $this->cod->pendingAmount($shipper) > 0) {
            return ['allowed' => false, 'reason' => 'Shipper còn tiền COD phải nộp. Hãy đối soát COD trước khi chuyển chi nhánh.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function transfer(User $user, int $branchId, User $actor): User
    {
        if (! $actor->isSuperAdmin()) {
            throw new RuntimeException('Chỉ Super Admin được điều chuyển home branch của shipper.');
        }

        if ((int) ($user->branch_id ?? 0) === $branchId) {
            return $user->fresh(['branch', 'shipper']);
        }

        $branch = Branch::query()->active()->whereKey($branchId)->first();
        if (! $branch) {
            throw new RuntimeException('Chi nhánh đích không tồn tại hoặc đã ngừng hoạt động.');
        }

        return DB::transaction(function () use ($user, $branch, $actor) {
            /** @var User $lockedUser */
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->load('shipper');

            $check = $this->canTransfer($lockedUser);
            if (! $check['allowed']) {
                throw new RuntimeException((string) $check['reason']);
            }

            $oldBranchId = $lockedUser->branch_id ? (int) $lockedUser->branch_id : null;
            $lockedUser->forceFill(['branch_id' => (int) $branch->id])->save();

            $shipper = $lockedUser->shipper;
            if ($shipper) {
                $values = [];
                if (Schema::hasColumn('shippers', 'station_branch_id')) {
                    $values['station_branch_id'] = (int) $branch->id;
                }
                if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                    $values['returning_to_branch_id'] = null;
                    $values['returning_started_at'] = null;
                }
                if ($values) {
                    $shipper->forceFill($values)->save();
                }
            }

            SystemLog::record(
                $actor,
                'Đã điều chuyển home branch của shipper '.$lockedUser->email,
                'admin',
                'success',
                [
                    'target_user_id' => (int) $lockedUser->id,
                    'old_branch_id' => $oldBranchId,
                    'new_branch_id' => (int) $branch->id,
                ]
            );

            return $lockedUser->fresh(['branch', 'shipper']);
        }, 3);
    }
}
