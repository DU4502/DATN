<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use Illuminate\Http\Request;

class GroupOrderController extends Controller
{
    public function index(Request $request)
    {
        GroupOrder::closeExpiredOrders();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $groups = $this->applyBranchScope(GroupOrder::query())
            ->with('owner')
            ->withCount(['members', 'items'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('code', 'like', '%'.$keyword.'%')
                        ->orWhereHas('owner', fn ($owner) => $owner
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('email', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(in_array($filters['status'], ['open', 'closed', 'ordered', 'cancelled'], true),
                fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'all' => $this->applyBranchScope(GroupOrder::query())->count(),
            'open' => $this->applyBranchScope(GroupOrder::query()->where('status', 'open'))->count(),
            'closed' => $this->applyBranchScope(GroupOrder::query()->where('status', 'closed'))->count(),
            'ordered' => $this->applyBranchScope(GroupOrder::query()->where('status', 'ordered'))->count(),
        ];

        return view('admin.group-orders.index', compact('groups', 'filters', 'stats'));
    }

    public function show(GroupOrder $groupOrder)
    {
        $groupOrder->closeIfExpired();
        
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            if (! $user->branch_id || (int) $groupOrder->branch_id !== (int) $user->branch_id) {
                abort(403, 'Bạn không có quyền xem đơn nhóm này.');
            }
        }

        // Super Admin chỉ giám sát: xem được lịch sử chat nhưng không có
        // endpoint gửi tin nhắn trong khu vực quản trị.
        $groupOrder->load([
            'owner',
            'order',
            'members.items.product.category',
            'messages.sender',
            'messages.recipient',
        ]);

        return view('admin.group-orders.show', compact('groupOrder'));
    }

    public function updateStatus(Request $request, GroupOrder $groupOrder)
    {
        $request->validate([
            'status' => ['required', 'in:open,closed,ordered,cancelled'],
        ]);

        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            if (!$user->branch_id || (int) $groupOrder->branch_id !== (int) $user->branch_id) {
                abort(403, 'Bạn không có quyền cập nhật đơn nhóm này.');
            }
        }

        $allowedTransitions = [
            'open' => ['closed', 'cancelled'],
            'closed' => ['ordered', 'cancelled'],
            'ordered' => [],
            'cancelled' => [],
        ];

        // Admin thường đi đúng state-machine; Super Admin được override mọi
        // trạng thái hợp lệ của đơn nhóm, kể cả quay ngược để sửa dữ liệu vận hành.
        if (! $user->isSuperAdmin()
            && ! in_array($request->status, $allowedTransitions[$groupOrder->status] ?? [], true)) {
            return redirect()->back()->with('error', 'Không thể chuyển sang trạng thái này.');
        }

        $data = [
            'status' => $request->status,
            'status_changed_at' => now(),
            'status_changed_by' => $user->id,
        ];

        if ($request->status === 'cancelled') {
            $data['cancelled_at'] = now();
        }
        if ($request->status === 'closed') {
            $data['locked_at'] = now();
        }

        $groupOrder->update($data);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái đơn nhóm thành công.');
    }

    /**
     * Apply branch scope to query:
     * - Super Admin sees all group orders.
     * - Regular Admin only sees group orders linked to an order belonging to their branch.
     */
    private function applyBranchScope($query)
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if (! $user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('branch_id', $user->branch_id);
    }
}
