<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $statusOptions = [
            '' => 'Tất cả trạng thái',
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'shipped' => 'Đã gửi hàng',
            'delivering' => 'Đang giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];

        $orders = Order::query()
            ->with(['user', 'orderItems'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($subQuery) use ($keyword) {
                    if (is_numeric($keyword)) {
                        $subQuery->orWhereKey((int) $keyword);
                    }

                    $subQuery->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('email', 'like', '%'.$keyword.'%');
                    });
                });
            })
            ->when(isset($statusOptions[$filters['status']]) && $filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            });

        if (Schema::hasColumn('orders', 'created_at')) {
            if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
                $orders->whereBetween('created_at', [
                    $filters['date_from'].' 00:00:00',
                    $filters['date_to'].' 23:59:59',
                ]);
            } elseif ($filters['date_from'] !== '') {
                $orders->where('created_at', '>=', $filters['date_from'].' 00:00:00');
            } elseif ($filters['date_to'] !== '') {
                $orders->where('created_at', '<=', $filters['date_to'].' 23:59:59');
            }
        }

        $orders = Schema::hasColumn('orders', 'created_at')
            ? $orders->latest()
            : $orders->orderByDesc('id');

        $orders = $orders->paginate(12)->withQueryString();

        return view('admin.orders.index', compact('orders', 'filters', 'statusOptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
