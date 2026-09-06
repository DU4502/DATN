<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipper;
use App\Notifications\OrderArrivingSoonNotification;
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

    private const LIVE_GPS_STALE_SECONDS = 45;
    private const TRACKING_MAX_SPEED_MPS = 24.0;
    private const TRACKING_MIN_ACCEPTED_JUMP_M = 40.0;
    private const TRACKING_MAX_ACCEPTED_JUMP_M = 260.0;
    private const ARRIVING_SOON_RADIUS_M = 500.0;

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
        $order->loadMissing(['branch', 'address', 'user']);
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
        $pendingIncident = $shipper
            ? app(ShipperIncidentService::class)->pendingIncident($order)
            : null;
        $driverIncidentPending = ($pendingIncident['incident_type'] ?? null) === 'driver_issue';
        $bundleNotice = app(ShipperBundleService::class)->customerNotice($order);

        $tracking = $shipper ? $this->trackingSnapshot($order, $shipper) : null;
        $arrivedCustomer = $shipper
            && in_array($status, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING], true)
            && $this->hasArrivalEvent($order, $shipper, 'arrived_customer');

        // Chỉ dùng GPS đã ghi cho đúng shipment/order hiện tại.
        // Không fallback sang current_latitude/current_longitude toàn cục của shipper vì có thể là tọa độ cũ của chuyến trước.
        $liveLat = $tracking['latitude'] ?? null;
        $liveLng = $tracking['longitude'] ?? null;
        $updatedAt = $tracking['recorded_at'] ?? null;

        $hasLiveGps = is_numeric($liveLat) && is_numeric($liveLng);
        $hasAssignedShipper = $shipper !== null;
        $shipperAccepted = $shipper ? $this->hasShipperAccepted($order, $shipper, $status) : false;
        $gpsFresh = ($tracking['stale_seconds'] ?? PHP_INT_MAX) <= self::LIVE_GPS_STALE_SECONDS;
        $trackingMode = match (true) {
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => $hasLiveGps ? 'shipper_finished' : ($shipperAccepted ? 'shipper_assigned' : 'preparing'),
            $hasLiveGps && $shipperAccepted && $gpsFresh => 'shipper_live',
            $hasLiveGps && $shipperAccepted => 'shipper_delayed',
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
                'heading' => $tracking['heading'] ?? null,
                'filtered' => (bool) ($tracking['filtered'] ?? false),
                'samples' => (int) ($tracking['samples'] ?? 1),
                'stale_seconds' => isset($tracking['stale_seconds']) ? (int) $tracking['stale_seconds'] : null,
                'raw_latitude' => isset($tracking['raw_latitude']) ? (float) $tracking['raw_latitude'] : null,
                'raw_longitude' => isset($tracking['raw_longitude']) ? (float) $tracking['raw_longitude'] : null,
            ]
            : [
                'latitude' => (float) $branch['latitude'],
                'longitude' => (float) $branch['longitude'],
                'updated_at' => $order->updated_at?->toIso8601String(),
                'type' => 'branch',
            ];

        $routeOriginLat = $hasLiveGps ? round((float) $current['latitude'], 4) : (float) $current['latitude'];
        $routeOriginLng = $hasLiveGps ? round((float) $current['longitude'], 4) : (float) $current['longitude'];
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

        $distanceToCustomerMeters = $hasLiveGps
            ? $this->distanceMeters(
                (float) $current['latitude'],
                (float) $current['longitude'],
                (float) $customer['latitude'],
                (float) $customer['longitude']
            )
            : null;
        $arrivingSoon = $shipperAccepted
            && $gpsFresh
            && is_numeric($distanceToCustomerMeters)
            && $distanceToCustomerMeters <= self::ARRIVING_SOON_RADIUS_M
            && in_array($status, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING], true);

        if ($arrivingSoon) {
            $this->notifyCustomerArrivingSoon($order, (float) $distanceToCustomerMeters);
        }

        $stage = match (true) {
            in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) => 'Đơn hàng đã giao thành công',
            $arrivedCustomer => 'Tài xế đã đến điểm giao',
            $arrivingSoon => 'Tài xế sắp đến điểm giao',
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
            $arrivingSoon => 'Tài xế còn trong bán kính 500m từ điểm giao. Bạn vui lòng chuẩn bị nhận hàng.',
            $hasLiveGps && $shipperAccepted => 'Vị trí tài xế đang được cập nhật theo GPS của chính chuyến này.',
            $shipperAccepted => 'Tài xế đã nhận đơn. Quán đang pha chế song song và tài xế có thể di chuyển tới cửa hàng.',
            $hasAssignedShipper => 'Hệ thống đã điều phối tài xế cho đơn.',
            default => 'Quán đang chuẩn bị đơn. Bạn vẫn có thể xem trước quãng đường từ quán tới nhà.',
        };

        if ($hasLiveGps && $shipperAccepted && ! $gpsFresh) {
            $message = 'GPS tài xế đang cập nhật chậm. Bản đồ giữ vị trí ổn định gần nhất của chính chuyến này để tránh nhảy sai.';
        }

        if ($driverIncidentPending) {
            $message = 'Xin lỗi bạn, chuyến giao đang gặp trở ngại nên có thể chậm hơn dự kiến. Chill Drink đang hỗ trợ tài xế để tiếp tục giao hàng sớm nhất.';
        }

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
            'arriving_soon' => $arrivingSoon,
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
            'distance_to_customer_m' => $distanceToCustomerMeters,
            'duration_s' => $durationSeconds,
            'route' => $route,
            'route_refresh_after_ms' => match ($trackingMode) {
                'shipper_live' => 12000,
                'shipper_delayed' => 18000,
                default => 25000,
            },
        ]);
    }

    private function trackingSnapshot(Order $order, Shipper $shipper): ?array
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_tracking')) {
            return null;
        }

        $rows = DB::table('shipment_tracking as tracking')
            ->join('shipments', 'shipments.id', '=', 'tracking.shipment_id')
            ->where('shipments.order_id', $order->id)
            ->where('shipments.shipper_id', $shipper->id)
            ->orderByDesc('tracking.recorded_at')
            ->orderByDesc('tracking.id')
            ->limit(8)
            ->get(['tracking.latitude', 'tracking.longitude', 'tracking.recorded_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        $points = $rows
            ->map(function ($row) {
                $lat = is_numeric($row->latitude ?? null) ? (float) $row->latitude : null;
                $lng = is_numeric($row->longitude ?? null) ? (float) $row->longitude : null;
                $at = $row->recorded_at ? Carbon::parse($row->recorded_at) : null;

                if ($lat === null || $lng === null || ! $at) {
                    return null;
                }

                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'recorded_at' => $at,
                ];
            })
            ->filter()
            ->sortBy(fn (array $point) => $point['recorded_at']->getTimestamp())
            ->values();

        if ($points->isEmpty()) {
            return null;
        }

        $latest = $points->last();
        $filteredPoints = $this->filterTrackingNoise($points);
        $anchor = $filteredPoints->last() ?: $latest;
        $smoothed = $this->smoothedTrackingPoint($filteredPoints);

        return [
            'latitude' => (float) ($smoothed['latitude'] ?? $anchor['latitude']),
            'longitude' => (float) ($smoothed['longitude'] ?? $anchor['longitude']),
            'raw_latitude' => (float) $latest['latitude'],
            'raw_longitude' => (float) $latest['longitude'],
            'recorded_at' => $anchor['recorded_at']->toIso8601String(),
            'heading' => $this->trackingHeading($filteredPoints),
            'filtered' => $filteredPoints->count() !== $points->count(),
            'samples' => $filteredPoints->count(),
            'stale_seconds' => max(0, $anchor['recorded_at']->diffInSeconds(now())),
        ];
    }

    private function filterTrackingNoise($points)
    {
        if ($points->count() <= 2) {
            return $points->values();
        }

        $accepted = collect([$points->first()]);

        foreach ($points->slice(1) as $point) {
            $previous = $accepted->last();
            $elapsedSeconds = max(1, $previous['recorded_at']->diffInSeconds($point['recorded_at']));
            $distance = $this->distanceMeters(
                (float) $previous['latitude'],
                (float) $previous['longitude'],
                (float) $point['latitude'],
                (float) $point['longitude']
            );

            $allowedJump = max(
                self::TRACKING_MIN_ACCEPTED_JUMP_M,
                min(self::TRACKING_MAX_ACCEPTED_JUMP_M, ($elapsedSeconds * self::TRACKING_MAX_SPEED_MPS) + 18.0)
            );

            if ($distance <= $allowedJump) {
                $accepted->push($point);
            }
        }

        return ($accepted->count() >= 2 ? $accepted : $points->take(-2))->values();
    }

    private function smoothedTrackingPoint($points): ?array
    {
        if ($points->isEmpty()) {
            return null;
        }

        if ($points->count() === 1) {
            return $points->last();
        }

        $recent = $points->take(-4)->values();
        $sumWeight = 0.0;
        $avgLat = 0.0;
        $avgLng = 0.0;

        foreach ($recent as $index => $point) {
            $weight = 1.0 + ($index * 1.35);
            $sumWeight += $weight;
            $avgLat += (float) $point['latitude'] * $weight;
            $avgLng += (float) $point['longitude'] * $weight;
        }

        $avgLat = $sumWeight > 0 ? $avgLat / $sumWeight : (float) $recent->last()['latitude'];
        $avgLng = $sumWeight > 0 ? $avgLng / $sumWeight : (float) $recent->last()['longitude'];
        $latest = $recent->last();
        $variance = $this->distanceMeters(
            (float) $latest['latitude'],
            (float) $latest['longitude'],
            $avgLat,
            $avgLng
        );
        $latestWeight = $variance > 22 ? 0.58 : 0.76;

        return [
            'latitude' => ((float) $latest['latitude'] * $latestWeight) + ($avgLat * (1 - $latestWeight)),
            'longitude' => ((float) $latest['longitude'] * $latestWeight) + ($avgLng * (1 - $latestWeight)),
            'recorded_at' => $latest['recorded_at'],
        ];
    }

    private function trackingHeading($points): ?float
    {
        if ($points->count() < 2) {
            return null;
        }

        $latest = $points->last();

        foreach ($points->slice(0, -1)->reverse() as $previous) {
            $distance = $this->distanceMeters(
                (float) $previous['latitude'],
                (float) $previous['longitude'],
                (float) $latest['latitude'],
                (float) $latest['longitude']
            );

            if ($distance >= 4.0) {
                return $this->bearingDegrees(
                    (float) $previous['latitude'],
                    (float) $previous['longitude'],
                    (float) $latest['latitude'],
                    (float) $latest['longitude']
                );
            }
        }

        return null;
    }

    private function bearingDegrees(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $lat1 = deg2rad($fromLat);
        $lat2 = deg2rad($toLat);
        $deltaLng = deg2rad($toLng - $fromLng);
        $y = sin($deltaLng) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLng);

        return fmod((rad2deg(atan2($y, $x)) + 360.0), 360.0);
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

    private function notifyCustomerArrivingSoon(Order $order, float $distanceMeters): void
    {
        if (! $order->user_id || ! Schema::hasTable('notifications')) {
            return;
        }

        $user = $order->user;

        if (! $user) {
            return;
        }

        $alreadyNotified = $user->notifications()
            ->where('data->type', 'order_arriving_soon')
            ->where('data->order_id', (int) $order->id)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $user->notify(new OrderArrivingSoonNotification($order, $distanceMeters));
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
            in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING], true) && ! $hasShipper => [
                'state' => 'pending_confirmation',
                'label' => OrderStatus::label($status),
                'stage' => 'Quán đang chuẩn bị đơn',
                'message' => 'Tài xế sẽ được gán khi quán chuyển đơn sang Sẵn sàng giao.',
            ],
            $status === OrderStatus::READY_FOR_DELIVERY && ! $hasShipper => [
                'state' => 'finding_shipper',
                'label' => 'Đang tìm tài xế',
                'stage' => 'Đơn đã sẵn sàng, đang tìm tài xế',
                'message' => 'Đồ uống đã sẵn sàng. Hệ thống đang tìm shipper rảnh phù hợp.',
            ],
            $status === OrderStatus::READY_FOR_DELIVERY && $shipperAccepted => [
                'state' => 'shipper_assigned',
                'label' => 'Tài xế đã nhận đơn',
                'stage' => 'Tài xế đang tới cửa hàng',
                'message' => 'Tài xế đang di chuyển tới cửa hàng để lấy đơn.',
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
