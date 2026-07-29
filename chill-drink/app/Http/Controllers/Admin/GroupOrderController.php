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
            if ($groupOrder->order && $user->branch_id && $groupOrder->order->branch_id !== $user->branch_id) {
                abort(403, 'Bạn không có quyền xem đơn nhóm này.');
            }
        }

        $groupOrder->load(['owner', 'order', 'members.items.product.category']);

        return view('admin.group-orders.show', compact('groupOrder'));
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
            return $query;
        }

        return $query->where(function ($scopedQuery) use ($user) {
            $scopedQuery
                ->whereDoesntHave('order')
                ->orWhereHas('order', fn ($order) => $order->where('branch_id', $user->branch_id));
        });
    }
}
