<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipper;
use App\Models\ShipperCodReceivable;
use App\Models\ShipperCodSettlement;
use App\Services\CodSettlementPinSetupService;
use App\Services\ShipperCodService;
use Illuminate\Http\JsonResponse;
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

    public function sendPin(
        Request $request,
        CodSettlementPinSetupService $pinSetupService
    ): JsonResponse {
        if (blank($request->user()->email)) {
            return response()->json([
                'message' => 'Tài khoản admin chưa có email nên không thể gửi mã xác minh Gmail.',
            ], 422);
        }

        $pinSetupService->send($request->user());

        return response()->json([
            'message' => 'Đã gửi mã xác minh Gmail 6 số tới '.$this->maskEmail((string) $request->user()->email).'.',
            'ttl_minutes' => $pinSetupService->ttlMinutes(),
        ]);
    }

    public function savePin(
        Request $request,
        CodSettlementPinSetupService $pinSetupService
    ): RedirectResponse {
        if (blank($request->user()->email)) {
            return back()->with('error', 'Tài khoản admin chưa có email nên không thể tạo PIN đối soát COD.');
        }

        $validated = $request->validate([
            'verification_code' => ['required', 'digits:6'],
            'new_pin' => ['required', 'digits:4', 'confirmed'],
        ], [
            'verification_code.required' => 'Bạn cần nhập mã xác minh Gmail.',
            'verification_code.digits' => 'Mã xác minh Gmail phải gồm đúng 6 chữ số.',
            'new_pin.required' => 'Bạn cần nhập PIN đối soát mới.',
            'new_pin.digits' => 'PIN đối soát phải gồm đúng 4 chữ số.',
            'new_pin.confirmed' => 'PIN nhập lại chưa khớp.',
        ]);

        if (! $pinSetupService->verify($request->user(), (string) $validated['verification_code'])) {
            return back()
                ->withInput($request->except(['new_pin', 'new_pin_confirmation']))
                ->with('error', 'Mã xác minh Gmail không hợp lệ hoặc đã hết hạn. Hãy gửi lại mã mới.');
        }

        $request->user()->setCodSettlementPin((string) $validated['new_pin']);

        return back()->with('success', 'Đã lưu PIN đối soát COD mới. Từ giờ xác nhận nhận tiền chỉ cần nhập PIN này.');
    }

    public function confirm(
        Request $request,
        Shipper $shipper,
        ShipperCodService $service
    ): RedirectResponse
    {
        try {
            $context = $this->buildSettlementContext($request, $shipper, $service);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'pin' => ['required', 'digits:4'],
        ], [
            'pin.required' => 'Bạn cần nhập PIN đối soát COD.',
            'pin.digits' => 'PIN đối soát phải gồm đúng 4 chữ số.',
        ]);

        if ($context['pending_order_count'] < 1 || $context['pending_amount'] < 1) {
            return back()->with('error', 'Không còn đơn COD chờ đối soát cho shipper này.');
        }

        if (! $request->user()->hasCodSettlementPin()) {
            return back()
                ->withInput($request->except('pin'))
                ->with('error', 'Bạn chưa tạo PIN đối soát COD. Hãy tạo PIN trước khi xác nhận nhận tiền.');
        }

        if (! $request->user()->verifyCodSettlementPin((string) $validated['pin'])) {
            return back()
                ->withInput($request->except('pin'))
                ->with('error', 'PIN đối soát COD không đúng.');
        }

        try {
            $settlement = $service->settleAll(
                $context['shipper'],
                $context['home_branch_id'],
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            'Đã xác nhận nhận '.number_format((int) $settlement->amount, 0, ',', '.').'đ COD từ '
            .$context['shipper']->user?->name.' tại home branch '.$context['shipper']->user?->branch?->name.'.'
        );
    }

    /**
     * @return array{shipper: Shipper, home_branch_id: int, pending_amount: int, pending_order_count: int}
     */
    private function buildSettlementContext(Request $request, Shipper $shipper, ShipperCodService $service): array
    {
        $shipper->loadMissing('user.branch');
        $scopeBranchId = $this->scopeBranchId($request);
        $rootMode = $request->user()->isSuperAdmin() && ! $request->user()->isViewingAdminWorkspace();
        $homeBranchId = $shipper->homeBranchId();

        if (! $homeBranchId) {
            throw new RuntimeException('Shipper chưa có home branch nên chưa thể đối soát COD.');
        }

        if (! $rootMode && (int) $scopeBranchId !== $homeBranchId) {
            abort(403, 'COD của shipper chỉ được đối soát tại home branch của shipper.');
        }

        $service->syncHistoricalReceivables([$shipper->id]);

        return [
            'shipper' => $shipper,
            'home_branch_id' => $homeBranchId,
            ...$this->pendingSnapshot($shipper),
        ];
    }

    /**
     * @return array{pending_amount: int, pending_order_count: int}
     */
    private function pendingSnapshot(Shipper $shipper): array
    {
        $summary = ShipperCodReceivable::query()
            ->selectRaw('COALESCE(SUM(amount), 0) as pending_amount, COUNT(*) as pending_order_count')
            ->where('shipper_id', $shipper->id)
            ->whereNull('settlement_id')
            ->first();

        return [
            'pending_amount' => (int) round((float) ($summary?->pending_amount ?? 0)),
            'pending_order_count' => (int) ($summary?->pending_order_count ?? 0),
        ];
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return $email;
        }

        if (mb_strlen($local) <= 2) {
            $maskedLocal = mb_substr($local, 0, 1).'*';
        } else {
            $visibleStart = mb_substr($local, 0, 2);
            $visibleEnd = mb_substr($local, -1);
            $maskedLocal = $visibleStart.str_repeat('*', max(mb_strlen($local) - 3, 1)).$visibleEnd;
        }

        return $maskedLocal.'@'.$domain;
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
