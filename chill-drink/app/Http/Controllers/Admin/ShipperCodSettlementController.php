<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipper;
use App\Models\ShipperCodReceivable;
use App\Models\ShipperCodSettlement;
use App\Services\ShipperCodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ShipperCodSettlementController extends Controller
{
    public function index(Request $request, ShipperCodService $service): View
    {
        $scopeBranchId = $this->scopeBranchId($request);
        $rootMode = $request->user()->isSuperAdmin() && ! $request->user()->isViewingAdminWorkspace();
        $eligibleShipperIds = $this->eligibleShipperIds($scopeBranchId);

        $service->syncHistoricalReceivables($eligibleShipperIds);

        $summaryQuery = ShipperCodReceivable::query()
            ->select([
                'shipper_id',
                DB::raw('SUM(amount) as pending_amount'),
                DB::raw('COUNT(*) as pending_order_count'),
                DB::raw('MIN(collected_at) as oldest_collected_at'),
                DB::raw('MAX(collected_at) as latest_collected_at'),
            ])
            ->whereNull('settlement_id')
            ->groupBy('shipper_id')
            ->orderByDesc('pending_amount');

        if ($eligibleShipperIds !== null) {
            $summaryQuery->whereIn('shipper_id', $eligibleShipperIds);
        }

        $summaries = $summaryQuery->get();
        $shippers = Shipper::query()
            ->with(['user.branch'])
            ->whereIn('id', $summaries->pluck('shipper_id'))
            ->get()
            ->keyBy('id');

        $pendingRows = $summaries->map(function ($summary) use ($shippers) {
            $shipper = $shippers->get((int) $summary->shipper_id);
            if (! $shipper || ! $shipper->homeBranchId()) {
                return null;
            }

            $items = ShipperCodReceivable::query()
                ->with(['order.branch'])
                ->where('shipper_id', $shipper->id)
                ->whereNull('settlement_id')
                ->orderBy('collected_at')
                ->limit(12)
                ->get();

            return [
                'shipper' => $shipper,
                'pending_amount' => (int) round((float) $summary->pending_amount),
                'pending_order_count' => (int) $summary->pending_order_count,
                'oldest_collected_at' => $summary->oldest_collected_at,
                'latest_collected_at' => $summary->latest_collected_at,
                'items' => $items,
                'home_branch_id' => $shipper->homeBranchId(),
                'home_branch_name' => $shipper->user?->branch?->name,
            ];
        })->filter()->values();

        $historyQuery = ShipperCodSettlement::query()
            ->with(['shipper.user', 'branch', 'confirmer'])
            ->latest('confirmed_at');

        if ($scopeBranchId !== null) {
            if ($scopeBranchId < 1) {
                $historyQuery->whereRaw('1 = 0');
            } else {
                $historyQuery->where('branch_id', $scopeBranchId);
            }
        }

        return view('admin.cod-settlements.index', [
            'pendingRows' => $pendingRows,
            'history' => $historyQuery->limit(50)->get(),
            'rootMode' => $rootMode,
            'scopeBranchId' => $scopeBranchId,
            'totalPending' => (int) $pendingRows->sum('pending_amount'),
            'totalPendingOrders' => (int) $pendingRows->sum('pending_order_count'),
        ]);
    }

    public function confirm(Request $request, Shipper $shipper, ShipperCodService $service): RedirectResponse
    {
        $shipper->loadMissing('user.branch');
        $scopeBranchId = $this->scopeBranchId($request);
        $rootMode = $request->user()->isSuperAdmin() && ! $request->user()->isViewingAdminWorkspace();
        $homeBranchId = $shipper->homeBranchId();

        if (! $homeBranchId) {
            return back()->with('error', 'Shipper chưa có home branch nên chưa thể đối soát COD.');
        }

        if (! $rootMode && (int) $scopeBranchId !== $homeBranchId) {
            abort(403, 'COD của shipper chỉ được đối soát tại home branch của shipper.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $settlement = $service->settleAll(
                $shipper,
                $homeBranchId,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            'Đã xác nhận nhận '.number_format((int) $settlement->amount, 0, ',', '.').'đ COD từ '
            .$shipper->user?->name.' tại home branch '.$shipper->user?->branch?->name.'.'
        );
    }

    /** null = Super Admin root xem toàn hệ thống; -1 = admin không có chi nhánh. */
    private function scopeBranchId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            if ($user->isViewingAdminWorkspace()) {
                return $user->adminWorkspaceBranchId() ?? -1;
            }

            return null;
        }

        return is_numeric($user->branch_id) ? (int) $user->branch_id : -1;
    }

    /** @return array<int>|null */
    private function eligibleShipperIds(?int $branchId): ?array
    {
        if ($branchId === null) {
            return null;
        }
        if ($branchId < 1) {
            return [];
        }

        return Shipper::query()
            ->whereHas('user', fn ($query) => $query->where('branch_id', $branchId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
