<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipper;
use App\Services\DeliveryRoutingService;
use App\Services\ShipperIncidentService;
use App\Services\ShipperBundleService;
use App\Support\GuestOrderAccess;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DeliveryTrackingController extends Controller
{
    private const TRACKABLE_STATUSES = [
        OrderStatus::PENDING,
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
        OrderStatus::DELIVERED,
        OrderStatus::COMPLETED,
    ];

    public function show(Request $request, Order $order): View
    {
        abort_unless($request->user() && (int) $order->user_id === (int) $request->user()->id, 403);

        $order->loadMissing(['branch', 'address', 'orderItems.product']);
        $journey = $this->journeyState($order);
        $shipper = $order->shipper_id ? Shipper::with('user')->find($order->shipper_id) : null;
        $shipperAccepted = $shipper
            ? $this->hasShipperAccepted($order, $shipper, OrderStatus::normalize((string) $order->status))
            : false;

        return view('client.orders.tracking', [
            'order' => $order,
            'liveUrl' => route('orders.delivery-tracking', $order),
            'journey' => $journey,
            'shipperAccepted' => $shipperAccepted,
            'shipperInfo' => $shipperAccepted && $shipper ? $this->shipperPayload($shipper) : null,
        ]);
    }

    public function authenticated(Request $request, Order $order, DeliveryRoutingService $routing): JsonResponse
    {
        abort_unless($request->user() && (int) $order->user_id === (int) $request->user()->id, 403);

        return $this->payload($request, $order, $routing);
    }

    public function guest(Request $request, Order $order, DeliveryRoutingService $routing): JsonResponse
    {
        abort_unless(GuestOrderAccess::canView($order, $request), 403);
        GuestOrderAccess::remember($order);

        return $this->payload($request, $order, $routing);
    }

    private function payload(Request $request, Order $order, DeliveryRoutingService $routing): JsonResponse
    {
        $order->loadMissing(['branch', 'address']);
        $status = OrderStatus::normalize((string) $order->status);
        $isDelivery = ($order->fulfillment_type ?? 'delivery') === 'delivery';
        $journey = $this->journeyState($order);
        $branch = $this->branchPayload($order);
        $customer = $this->customerPayload($order);

        if (! $isDelivery || ! in_array($status, self::TRACKABLE_STATUSES, true)) {
            return response()->json([
                'success' => true,
                'available' => false,
                'status' => $status,
                'status_label' => OrderStatus::label($status),
                'stage' => $journey['stage'],
                'timeline_state' => $journey['state'],
                'timeline_label' => $journey['label'],
                'message' => 'Đơn chưa ở giai đoạn theo dõi giao hàng.',
            ]);
        }

        if (! $branch || ! $customer) {
            return response()->json([
                'success' => true,
                'available' => false,
                'status' => $status,
                'status_label' => OrderStatus::label($status),
                'stage' => $journey['stage'],
                'timeline_state' => $journey['state'],
                'timeline_label' => $journey['label'],
                'message' => 'Chưa đủ tọa độ quán hoặc điểm giao để hiển thị bản đồ theo dõi.',
            ]);
        }

        $shipper = $order->shipper_id
            ? Shipper::with('user')->find($order->shipper_id)
            : null;
        $handoverPending = $shipper
            ? app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper)
            : null;
        $bundleNotice = app(ShipperBundleService::class)->customerNotice($order);

        $latest = $shipper ? $this->latestTrackingPoint($order, $shipper) : null;
        $arrivedCustomer = $shipper
            && in_array($status, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING], true)
            && $this->hasArrivalEvent($order, $shipper, 'arrived_customer');

        // Chỉ dùng GPS đã ghi cho đúng shipment/order hiện tại.
        // Không fallback sang current_latitude/current_longitude toàn cục của shipper vì có thể là tọa độ cũ của chuyến trước.
        $liveLat = $latest['latitude'] ?? null;
        $liveLng = $latest['longitude'] ?? null;
        $updatedAt = $latest['recorded_at'] ?? null;

        $hasLiveGps = is_numeric($liveLat) && is_numeric($liveLng);
        $hasAssignedShipper = $shipper !== null;
        $shipperAccepted = $shipper ? $this->hasShipperAccepted($order, $shipper, $status) : false;
        $trackingMode = match (true) {
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => $hasLiveGps ? 'shipper_finished' : ($shipperAccepted ? 'shipper_assigned' : 'preparing'),
            $hasLiveGps && $shipperAccepted => 'shipper_live',
            $shipperAccepted => 'shipper_assigned',
            default => 'preparing',
        };

        if ($handoverPending) {
            return response()->json([
                'success' => true,
                'available' => false,
                'status' => $status,
                'status_label' => OrderStatus::label($status),
                'stage' => $shipperAccepted
                    ? 'Tài xế thay thế đang tới điểm bàn giao'
                    : 'Chill Drink đã điều phối tài xế thay thế',
                'timeline_state' => $status === OrderStatus::DELIVERING ? 'delivering' : 'shipper_picked_up',
                'timeline_label' => 'Đang chuyển giao tài xế',
                'message' => $shipperAccepted
                    ? 'Tài xế thay thế đang tới nhận bàn giao hàng từ tài xế cũ. Vị trí bàn giao không hiển thị cho khách; bản đồ giao hàng sẽ tự mở lại sau khi bàn giao xong.'
                    : 'Hệ thống đã gán tài xế thay thế và đang điều hướng người này tới điểm bàn giao.',
                'arrived_customer' => false,
                'mode' => 'handover',
                'branch' => $branch,
                'customer' => $customer,
                'shipper' => $shipperAccepted && $shipper ? $this->shipperPayload($shipper) : null,
                'shipper_assigned' => $shipper !== null,
                'shipper_accepted' => $shipperAccepted,
                'current' => null,
                'destination' => null,
                'distance_m' => null,
                'duration_s' => null,
                'route' => null,
            ]);
        }

        $current = $hasLiveGps
            ? [
                'latitude' => (float) $liveLat,
                'longitude' => (float) $liveLng,
                'updated_at' => $updatedAt,
                'type' => 'shipper',
            ]
            : [
                'latitude' => (float) $branch['latitude'],
                'longitude' => (float) $branch['longitude'],
                'updated_at' => $order->updated_at?->toIso8601String(),
                'type' => 'branch',
            ];

        $routeOriginLat = (float) $current['latitude'];
        $routeOriginLng = (float) $current['longitude'];
        $routeDestination = $customer;
        $isPrePickup = in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true);

        // Không trả Haversine xen kẽ giữa các lần poll vì sẽ làm km/ETA
        // nhảy khác với tuyến đường thật. Frontend giữ số road-route gần nhất
        // và chỉ refresh route định kỳ.
        $distanceMeters = null;
        $durationSeconds = null;
        $route = null;

        if ($request->boolean('route')) {
            if ($isPrePickup && $hasLiveGps && $shipperAccepted) {
                $toBranchRoute = $routing->route(
                    $routeOriginLat,
                    $routeOriginLng,
                    (float) $branch['latitude'],
                    (float) $branch['longitude']
                );
                $toCustomerRoute = $routing->route(
                    (float) $branch['latitude'],
                    (float) $branch['longitude'],
                    (float) $customer['latitude'],
                    (float) $customer['longitude']
                );
                $route = $this->mergeRoutes($toBranchRoute, $toCustomerRoute);
            } else {
                $route = $routing->route(
                    $routeOriginLat,
                    $routeOriginLng,
                    (float) $routeDestination['latitude'],
                    (float) $routeDestination['longitude']
                );
            }

            if (is_array($route)) {
                $distanceMeters = isset($route['distance_m']) ? (float) $route['distance_m'] : null;
                $durationSeconds = isset($route['duration_s']) ? (float) $route['duration_s'] : null;
            }
        }

        $stage = match (true) {
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => 'Đơn hàng đã giao thành công',
            $arrivedCustomer => 'Tài xế đã đến điểm giao',
            $hasLiveGps && $shipperAccepted => 'Tài xế đang di chuyển trên hành trình',
            $shipperAccepted => 'Tài xế đã nhận đơn',
            $hasAssignedShipper => 'Hệ thống đã gán tài xế, đang chuẩn bị di chuyển',
            in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true) => 'Quán đang pha chế và chuẩn bị giao',
            default => $journey['stage'],
        };

        $message = match (true) {
            $status === OrderStatus::PENDING => 'Đơn đã được tạo. Bản đồ hiển thị sẵn tuyến đường từ quán tới địa chỉ của bạn.',
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => 'Đơn đã giao thành công. Vị trí cuối cùng của chuyến được giữ lại trên bản đồ.',
            $arrivedCustomer => 'Tài xế đã vào phạm vi gần điểm giao. Bạn vui lòng chuẩn bị nhận hàng.',
            $hasLiveGps && $shipperAccepted => 'Vị trí tài xế đang được cập nhật theo GPS của chính chuyến này.',
            $shipperAccepted => 'Tài xế đã nhận đơn. Quán đang pha chế song song và tài xế có thể di chuyển tới cửa hàng.',
            $hasAssignedShipper => 'Hệ thống đã điều phối tài xế cho đơn.',
            default => 'Quán đang chuẩn bị đơn. Bạn vẫn có thể xem trước quãng đường từ quán tới nhà.',
        };

        if ($bundleNotice && ! in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
            $message .= ' '.$bundleNotice['message'];
        }

        return response()->json([
            'success' => true,
            'available' => true,
            'status' => $status,
            'status_label' => OrderStatus::label($status),
            'stage' => $stage,
            'timeline_state' => $journey['state'],
            'timeline_label' => $journey['label'],
            'message' => $message,
            'arrived_customer' => $arrivedCustomer,
            'shared_trip' => (bool) ($bundleNotice['shared_trip'] ?? false),
            'hidden_stops_before' => (int) ($bundleNotice['hidden_stops_before'] ?? 0),
            'mode' => $trackingMode,
            'branch' => $branch,
            'customer' => $customer,
            // Tài xế được coi là đã tiếp nhận ngay khi engine điều phối gán shipper.
            'shipper' => $shipperAccepted && $shipper ? $this->shipperPayload($shipper) : null,
            'shipper_assigned' => $hasAssignedShipper,
            'shipper_accepted' => $shipperAccepted,
            'current' => $current,
            'destination' => $customer,
            'distance_m' => $distanceMeters,
            'duration_s' => $durationSeconds,
            'route' => $route,
        ]);
    }

    private function latestTrackingPoint(Order $order, Shipper $shipper): ?array
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_tracking')) {
            return null;
        }

        $row = DB::table('shipment_tracking as tracking')
            ->join('shipments', 'shipments.id', '=', 'tracking.shipment_id')
            ->where('shipments.order_id', $order->id)
            ->where('shipments.shipper_id', $shipper->id)
            ->orderByDesc('tracking.recorded_at')
            ->orderByDesc('tracking.id')
            ->select(['tracking.latitude', 'tracking.longitude', 'tracking.recorded_at'])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'latitude' => (float) $row->latitude,
            'longitude' => (float) $row->longitude,
            'recorded_at' => $row->recorded_at ? Carbon::parse($row->recorded_at)->toIso8601String() : null,
        ];
    }

    private function branchPayload(Order $order): ?array
    {
        if (! $order->branch) {
            return null;
        }

        $lat = is_numeric($order->branch->latitude) ? (float) $order->branch->latitude : null;
        $lng = is_numeric($order->branch->longitude) ? (float) $order->branch->longitude : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => $order->branch->name ?: 'Cửa hàng',
            'address' => $order->branch->address ?: 'Điểm lấy hàng',
            'type' => 'branch',
        ];
    }

    private function customerPayload(Order $order): ?array
    {
        $lat = is_numeric($order->shipping_latitude) ? (float) $order->shipping_latitude : null;
        $lng = is_numeric($order->shipping_longitude) ? (float) $order->shipping_longitude : null;

        if ($lat === null || $lng === null) {
            $address = $order->address;
            if ($address) {
                $lat = is_numeric($address->latitude ?? null) ? (float) $address->latitude : null;
                $lng = is_numeric($address->longitude ?? null) ? (float) $address->longitude : null;
            }
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => 'Điểm giao hàng',
            'address' => $order->getShippingAddress(),
            'type' => 'customer',
        ];
    }

    private function hasArrivalEvent(Order $order, Shipper $shipper, string $event): bool
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return false;
        }

        return DB::table('shipment_history as history')
            ->join('shipments', 'shipments.id', '=', 'history.shipment_id')
            ->where('shipments.order_id', $order->id)
            ->where('shipments.shipper_id', $shipper->id)
            ->where('history.status', $event)
            ->exists();
    }

    private function hasShipperAccepted(Order $order, Shipper $shipper, string $status): bool
    {
        if ((int) ($order->shipper_id ?? 0) === (int) $shipper->id
            && in_array($status, [
                OrderStatus::CONFIRMED,
                OrderStatus::PREPARING,
                OrderStatus::READY_FOR_DELIVERY,
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
                OrderStatus::DELIVERED,
                OrderStatus::COMPLETED,
            ], true)) {
            return true;
        }

        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return in_array($status, [
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
                OrderStatus::DELIVERED,
                OrderStatus::COMPLETED,
            ], true);
        }

        $shipmentId = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $shipper->id)
            ->latest('id')
            ->value('id');

        if (! $shipmentId) {
            return false;
        }

        return DB::table('shipment_history')
            ->where('shipment_id', $shipmentId)
            ->where('status', 'accepted')
            ->exists();
    }

    private function shipperPayload(Shipper $shipper): array
    {
        return [
            'name' => $shipper->user?->name ?: 'Tài xế',
            'phone' => $shipper->phone ?: $shipper->user?->phone,
            'vehicle_type' => $shipper->vehicle_type,
            'license_plate' => $shipper->license_plate,
            'avatar' => $shipper->avatar ?: $shipper->user?->avatar,
        ];
    }

    private function journeyState(Order $order): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $hasShipper = (int) ($order->shipper_id ?? 0) > 0;
        $assignedShipper = $hasShipper ? Shipper::find($order->shipper_id) : null;
        $shipperAccepted = $assignedShipper
            ? $this->hasShipperAccepted($order, $assignedShipper, $status)
            : false;
        $handoverPending = $assignedShipper
            ? app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $assignedShipper)
            : null;

        return match (true) {
            $handoverPending && $status === OrderStatus::DELIVERING => [
                'state' => 'delivering',
                'label' => 'Đang chuyển giao tài xế',
                'stage' => $shipperAccepted ? 'Tài xế thay thế đang tới điểm bàn giao' : 'Đã gán tài xế thay thế',
                'message' => 'Chill Drink đang xử lý bàn giao chuyến do tài xế trước gặp sự cố.',
            ],
            $handoverPending && $status === OrderStatus::SHIPPER_PICKED_UP => [
                'state' => 'shipper_picked_up',
                'label' => 'Đang chuyển giao tài xế',
                'stage' => $shipperAccepted ? 'Tài xế thay thế đang tới điểm bàn giao' : 'Đã gán tài xế thay thế',
                'message' => 'Đồ uống đã được lấy khỏi quán và đang được bàn giao cho tài xế thay thế.',
            ],
            $status === OrderStatus::PENDING => [
                'state' => 'pending_confirmation',
                'label' => 'Chờ quán xác nhận',
                'stage' => 'Đơn đang chờ quán xác nhận',
                'message' => 'Quán đang xem và xác nhận đơn hàng của bạn.',
            ],
            in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true) && ! $hasShipper => [
                'state' => 'finding_shipper',
                'label' => 'Đang tìm tài xế',
                'stage' => 'Quán đã xác nhận, đang tìm tài xế',
                'message' => 'Quán đã xác nhận đơn. Hệ thống đang tìm shipper rảnh phù hợp.',
            ],
            in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true) && $shipperAccepted => [
                'state' => 'shipper_assigned',
                'label' => 'Tài xế đã nhận đơn',
                'stage' => $status === OrderStatus::READY_FOR_DELIVERY
                    ? 'Tài xế đang tới cửa hàng'
                    : 'Tài xế đã nhận đơn, quán đang chuẩn bị',
                'message' => $status === OrderStatus::READY_FOR_DELIVERY
                    ? 'Tài xế đang di chuyển tới cửa hàng để lấy đơn.'
                    : 'Tài xế đã nhận đơn. Quán đang pha chế song song và chuẩn bị bàn giao.',
            ],
            $status === OrderStatus::SHIPPER_PICKED_UP => [
                'state' => 'shipper_picked_up',
                'label' => 'Tài xế đã lấy hàng',
                'stage' => 'Tài xế đã lấy hàng từ quán',
                'message' => 'Đồ uống đã được tài xế nhận từ quán.',
            ],
            $status === OrderStatus::DELIVERING => [
                'state' => 'delivering',
                'label' => 'Đang giao đến bạn',
                'stage' => 'Đơn hàng đang trên đường tới bạn',
                'message' => 'Tài xế đang giao đơn đến địa chỉ của bạn.',
            ],
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => [
                'state' => 'delivered',
                'label' => 'Đã giao hàng',
                'stage' => 'Đơn hàng đã giao thành công',
                'message' => 'Tài xế đã hoàn tất giao hàng.',
            ],
            default => [
                'state' => 'pending_confirmation',
                'label' => OrderStatus::label($status),
                'stage' => OrderStatus::label($status),
                'message' => 'Đơn hàng đang được xử lý.',
            ],
        };
    }

    private function stageLabel(Order $order, string $status): string
    {
        return $this->journeyState($order)['stage'];
    }

    private function mergeRoutes(array $first, array $second): array
    {
        $firstGeometry = is_array($first['geometry'] ?? null) ? $first['geometry'] : [];
        $secondGeometry = is_array($second['geometry'] ?? null) ? $second['geometry'] : [];

        if ($firstGeometry && $secondGeometry && end($firstGeometry) === reset($secondGeometry)) {
            array_shift($secondGeometry);
        }

        return [
            'source' => (($first['fallback'] ?? false) || ($second['fallback'] ?? false)) ? 'mixed_route' : 'routing_server',
            'fallback' => (bool) (($first['fallback'] ?? false) || ($second['fallback'] ?? false)),
            'distance_m' => (float) ($first['distance_m'] ?? 0) + (float) ($second['distance_m'] ?? 0),
            'duration_s' => (float) ($first['duration_s'] ?? 0) + (float) ($second['duration_s'] ?? 0),
            'geometry' => array_values(array_merge($firstGeometry, $secondGeometry)),
            'steps' => array_values(array_merge($first['steps'] ?? [], $second['steps'] ?? [])),
            'alternatives_count' => 0,
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
