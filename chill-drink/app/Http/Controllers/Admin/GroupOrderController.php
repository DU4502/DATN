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

        $groups = GroupOrder::query()
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
            'all' => GroupOrder::count(),
            'open' => GroupOrder::where('status', 'open')->count(),
            'closed' => GroupOrder::where('status', 'closed')->count(),
            'ordered' => GroupOrder::where('status', 'ordered')->count(),
        ];

        return view('admin.group-orders.index', compact('groups', 'filters', 'stats'));
    }

    public function show(GroupOrder $groupOrder)
    {
        $groupOrder->closeIfExpired();
        $groupOrder->load(['owner', 'order', 'members.items.product.category']);

        return view('admin.group-orders.show', compact('groupOrder'));
    }
}
