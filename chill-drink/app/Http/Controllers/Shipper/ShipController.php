<?php

namespace App\Http\Controllers\Shipper;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipController extends Controller
{
    /**
     * Lấy thông tin shipper đang đăng nhập
     */
    private function getShipper()
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Bạn chưa đăng nhập.');
        }

        return Shipper::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * Dashboard Shipper
     */
    public function dashboard()
    {
        $user = Auth::user();

        $shipperInfo = Shipper::where(
            'user_id',
            $user->id
        )->first();

        if (!$shipperInfo) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Tài khoản này chưa được tạo hồ sơ shipper.',
                ]);
        }

        $shipperUser = $user;

        /*
        |--------------------------------------------------------------------------
        | THỐNG KÊ
        |--------------------------------------------------------------------------
        */

        // Đơn hôm nay
        $todayOrders = Order::whereDate(
            'created_at',
            today()
        )
            ->where(function ($query) use ($shipperInfo) {
                $query->where(
                    'shipper_id',
                    $shipperInfo->id
                )->orWhereNull('shipper_id');
            })
            ->count();

        // Đang giao
        $shippingOrders = Order::where(
            'shipper_id',
            $shipperInfo->id
        )
            ->where('status', 'shipping')
            ->count();

        // Hoàn thành
        $completedOrders = Order::where(
            'shipper_id',
            $shipperInfo->id
        )
            ->where('status', 'completed')
            ->count();

        // Thu nhập
        $income = Order::where(
            'shipper_id',
            $shipperInfo->id
        )
            ->where('status', 'completed')
            ->sum('shipping_fee');

        /*
        |--------------------------------------------------------------------------
        | DANH SÁCH ĐƠN
        |--------------------------------------------------------------------------
        */

        $orders = Order::where(function ($query) use ($shipperInfo) {
            $query->whereNull('shipper_id')
                ->orWhere(
                    'shipper_id',
                    $shipperInfo->id
                );
        })
            ->whereNotIn('status', [
                'cancelled',
                'completed',
            ])
            ->latest()
            ->paginate(10);

        return view(
            'shipper.dashboard',
            compact(
                'todayOrders',
                'shippingOrders',
                'completedOrders',
                'income',
                'shipperUser',
                'shipperInfo',
                'orders'
            )
        );
    }

    /**
     * Danh sách đơn hàng
     */
    public function orders()
    {
        $shipperInfo = $this->getShipper();

        $orders = Order::where(function ($query) use ($shipperInfo) {
            $query->whereNull('shipper_id')
                ->orWhere(
                    'shipper_id',
                    $shipperInfo->id
                );
        })
            ->whereNotIn('status', [
                'cancelled',
                'completed',
            ])
            ->latest()
            ->paginate(10);

        return view(
            'shipper.orders.index',
            compact(
                'orders',
                'shipperInfo'
            )
        );
    }

    /**
     * Chi tiết đơn hàng
     */
    public function showOrder($id)
    {
        $shipperInfo = $this->getShipper();

        $order = Order::where('id', $id)
            ->where(function ($query) use ($shipperInfo) {
                $query->whereNull('shipper_id')
                    ->orWhere(
                        'shipper_id',
                        $shipperInfo->id
                    );
            })
            ->firstOrFail();

        return view(
            'shipper.orders.show',
            compact(
                'order',
                'shipperInfo'
            )
        );
    }

    /**
     * Shipper nhận đơn
     */
    public function acceptOrder($id)
    {
        $shipperInfo = $this->getShipper();

        if ($shipperInfo->status === 'busy') {
            return back()->with(
                'error',
                'Bạn đang giao một đơn hàng khác.'
            );
        }

        $order = Order::where('id', $id)
            ->whereNull('shipper_id')
            ->whereNotIn('status', [
                'cancelled',
                'completed',
            ])
            ->firstOrFail();

        $order->update([
            'shipper_id' => $shipperInfo->id,
            'status' => 'processing',
        ]);

        return back()->with(
            'success',
            'Nhận đơn hàng thành công!'
        );
    }

    /**
     * Bắt đầu giao hàng
     *
     * Sau khi bấm bắt đầu giao:
     * - Đơn chuyển sang shipping
     * - Shipper chuyển sang busy
     * - Chuyển sang trang bản đồ của đơn
     */
    public function startDelivery($id)
    {
        $shipperInfo = $this->getShipper();

        $order = Order::where('id', $id)
            ->where(
                'shipper_id',
                $shipperInfo->id
            )
            ->where(
                'status',
                'processing'
            )
            ->firstOrFail();

        $order->update([
            'status' => 'shipping',
        ]);

        $shipperInfo->update([
            'status' => 'busy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CHUYỂN SANG BẢN ĐỒ
        |--------------------------------------------------------------------------
        |
        | Quan trọng:
        | Phải truyền $order->id vào route map.
        |
        */

        return redirect()
            ->route(
                'shipper.map',
                ['id' => $order->id]
            )
            ->with(
                'success',
                'Đã bắt đầu giao hàng!'
            );
    }

    /**
     * Bản đồ giao hàng
     *
     * Route:
     * /shipper/map/{id}
     */
    public function map($id)
    {
        $shipper = $this->getShipper();

        $order = Order::with('customer')
            ->where('id', $id)
            ->where('shipper_id', $shipper->id)
            ->firstOrFail();

        return view('shipper.map', [
            'order' => $order,
            'shipper' => $shipper,
        ]);
    }
    /**
     * Hoàn thành đơn hàng
     */
    public function completeOrder($id)
    {
        $shipperInfo = $this->getShipper();

        $order = Order::where('id', $id)
            ->where(
                'shipper_id',
                $shipperInfo->id
            )
            ->where(
                'status',
                'shipping'
            )
            ->firstOrFail();

        $order->update([
            'status' => 'completed',
        ]);

        $shipperInfo->update([
            'status' => 'online',
        ]);

        return redirect()
            ->route('shipper.orders')
            ->with(
                'success',
                'Giao hàng thành công!'
            );
    }

    /**
     * Hủy đơn
     */
    public function cancelOrder($id)
    {
        $shipperInfo = $this->getShipper();

        $order = Order::where('id', $id)
            ->where(
                'shipper_id',
                $shipperInfo->id
            )
            ->whereIn('status', [
                'processing',
                'shipping',
            ])
            ->firstOrFail();

        $order->update([
            'shipper_id' => null,
            'status' => 'pending',
        ]);

        $hasShippingOrder = Order::where(
            'shipper_id',
            $shipperInfo->id
        )
            ->where(
                'status',
                'shipping'
            )
            ->exists();

        if (!$hasShippingOrder) {
            $shipperInfo->update([
                'status' => 'online',
            ]);
        }

        return back()->with(
            'success',
            'Đã hủy nhận đơn hàng.'
        );
    }

    /**
     * Trang cá nhân shipper
     */
    public function profile()
    {
        $user = Auth::user();

        // Lấy thông tin shipper
        $shipperInfo = Shipper::where('user_id', $user->id)->first();

        return view('shipper.profile', [
            'shipperUser' => $user,
            'shipperInfo' => $shipperInfo,
        ]);
    }


    /**
     * Cập nhật thông tin cá nhân shipper
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $shipper = Shipper::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'license_plate' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Cập nhật tên trong bảng users
        $user->name = $request->name;
        $user->save();

        // Cập nhật thông tin trong bảng shippers
        $shipper->phone = $request->phone;
        $shipper->vehicle_type = $request->vehicle_type;
        $shipper->license_plate = $request->license_plate;

        // Upload avatar
        if ($request->hasFile('avatar')) {

            $path = $request->file('avatar')->store(
                'shippers',
                'public'
            );

            $shipper->avatar = $path;
        }

        $shipper->save();

        return redirect()
            ->route('shipper.profile')
            ->with('success', 'Cập nhật thông tin shipper thành công!');
    }
    /**
     * Cập nhật vị trí shipper
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $shipperInfo = $this->getShipper();

        $shipperInfo->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vị trí thành công.',
        ]);
    }
}
