<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use Illuminate\Http\Request;

class StaffGroupOrderController extends Controller
{
    private function applyBranchScope($query)
    {
        $user = auth()->user();

        if (!$user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        // Đơn nhóm thuộc chi nhánh (qua order.branch_id) hoặc chưa có order
        return $query->where(function ($q) use ($user) {
            $q->whereDoesntHave('order')
              ->orWhereHas('order', fn ($o) => $o->where('branch_id', $user->branch_id));
        });
    }

    public function index(Request $request)
    {
        GroupOrder::closeExpiredOrders();

        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $groups = $this->applyBranchScope(GroupOrder::query())
            ->with('owner')
            ->withCount(['members', 'items'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%'));
                });
            })
            ->when(in_array($filters['status'], ['open', 'closed', 'ordered', 'cancelled'], true),
                fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'all'     => $this->applyBranchScope(GroupOrder::query())->count(),
            'open'    => $this->applyBranchScope(GroupOrder::query()->where('status', 'open'))->count(),
            'closed'  => $this->applyBranchScope(GroupOrder::query()->where('status', 'closed'))->count(),
            'ordered' => $this->applyBranchScope(GroupOrder::query()->where('status', 'ordered'))->count(),
        ];

        return view('staff.group-orders.index', compact('groups', 'filters', 'stats'));
    }

    public function show(GroupOrder $groupOrder)
    {
        $groupOrder->closeIfExpired();

        $user = auth()->user();
        if ($user->branch_id && $groupOrder->order && $groupOrder->order->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền xem đơn nhóm này.');
        }

        $groupOrder->load(['owner', 'order', 'members.items.product.category']);

        return view('staff.group-orders.show', compact('groupOrder'));
    }

    /**
     * Nhân viên có thể đổi trạng thái đơn nhóm (closed → ordered, cancelled)
     */
    public function updateStatus(Request $request, GroupOrder $groupOrder)
    {
        $request->validate([
            'status' => ['required', 'in:open,closed,ordered,cancelled'],
        ]);

        $user = auth()->user();

        if ($user->branch_id && $groupOrder->order && $groupOrder->order->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền cập nhật đơn nhóm này.');
        }

        $allowedTransitions = [
            'open'    => ['closed', 'cancelled'],
            'closed'  => ['ordered', 'cancelled'],
            'ordered' => [],
        ];

        $currentStatus = $groupOrder->status;
        $newStatus     = $request->status;

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            return redirect()->back()->with('error', 'Không thể chuyển sang trạng thái này.');
        }

        $data = [
            'status'            => $newStatus,
            'status_changed_at' => now(),
            'status_changed_by' => $user->id,
        ];

        if ($newStatus === 'cancelled') {
            $data['cancelled_at'] = now();
        }
        if ($newStatus === 'closed') {
            $data['locked_at'] = now();
        }

        $groupOrder->update($data);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái đơn nhóm.');
    }
}
