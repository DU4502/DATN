<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderLookupController extends Controller
{
    /**
     * Hiển thị trang tra cứu đơn hàng.
     */
    public function index()
    {
        return view('client.order-lookup.index');
    }

    /**
     * Xử lý tra cứu đơn hàng theo mã đơn.
     */
    public function search(Request $request)
    {
        $request->validate([
            'order_code' => ['required', 'string', 'max:50'],
        ], [
            'order_code.required' => 'Vui lòng nhập mã đơn hàng.',
        ]);

        $code = trim($request->input('order_code'));

        $orderQuery = Order::with(['orderItems.product', 'branch']);

        // Tìm theo order_code nếu schema đã có cột này.
        if (Schema::hasColumn('orders', 'order_code')) {
            $order = (clone $orderQuery)
                ->whereRaw('LOWER(order_code) = ?', [strtolower($code)])
                ->first();
        } else {
            $order = null;
        }

        // Fallback: nhập #id cũ (đơn trước khi có order_code)
        if (! $order && preg_match('/^#?(\d+)$/', $code, $m)) {
            $order = (clone $orderQuery)
                ->where('id', (int) $m[1])
                ->first();
        }

        if (! $order) {
            return back()->withInput()->with('error', 'Không tìm thấy đơn hàng. Vui lòng kiểm tra lại mã đơn.');
        }

        $statusLabel = OrderStatus::label(OrderStatus::normalize((string) $order->status));
        $badgeColor  = $order->getStatusBadgeColor();

        return view('client.order-lookup.result', compact('order', 'statusLabel', 'badgeColor'));
    }

    /**
     * API endpoint — trả về trạng thái mới nhất của đơn (dùng cho polling JS).
     */
    public function status(Order $order): JsonResponse
    {
        $statusKey   = OrderStatus::normalize((string) $order->status);
        $statusLabel = OrderStatus::label($statusKey);
        $badgeColor  = OrderStatus::badgeColorMap()[$statusKey] ?? 'secondary';

        return response()->json([
            'status'       => $statusKey,
            'status_label' => $statusLabel,
            'badge_color'  => $badgeColor,
        ]);
    }
}
