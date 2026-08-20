<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrderMessage;
use App\Models\Order;
use App\Models\Shipper;
use App\Support\GuestOrderAccess;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderDeliveryChatController extends Controller
{
    private const STORAGE_UNAVAILABLE_MESSAGE = 'Chat theo chuyến đang được khởi tạo dữ liệu. Vui lòng thử lại sau ít phút.';

    public function customerMessages(Request $request, Order $order): JsonResponse
    {
        $this->authorizeCustomer($request, $order);
        return $this->messages($request, $order, 'customer');
    }

    public function customerSend(Request $request, Order $order): JsonResponse
    {
        $this->authorizeCustomer($request, $order);
        return $this->send($request, $order, 'customer');
    }

    public function guestMessages(Request $request, Order $order): JsonResponse
    {
        $this->authorizeGuest($request, $order);
        return $this->messages($request, $order, 'customer');
    }

    public function guestSend(Request $request, Order $order): JsonResponse
    {
        $this->authorizeGuest($request, $order);
        return $this->send($request, $order, 'customer', null);
    }

    public function shipperMessages(Request $request, Order $order): JsonResponse
    {
        $this->authorizeShipper($request, $order);
        return $this->messages($request, $order, 'shipper');
    }

    public function shipperSend(Request $request, Order $order): JsonResponse
    {
        $this->authorizeShipper($request, $order);
        return $this->send($request, $order, 'shipper');
    }

    private function messages(Request $request, Order $order, string $viewerType): JsonResponse
    {
        if (! $this->deliveryChatStorageReady()) {
            return response()->json([
                'success' => true,
                'available' => false,
                'locked' => true,
                'message' => self::STORAGE_UNAVAILABLE_MESSAGE,
                'messages' => [],
            ]);
        }

        $state = $this->chatState($order);
        if (! $state['available']) {
            return response()->json([
                'success' => true,
                'available' => false,
                'locked' => true,
                'message' => $state['message'],
                'messages' => [],
            ]);
        }

        $this->purgeExpired($order);

        $markRead = $request->boolean('mark_read', true);
        if ($markRead) {
            DeliveryOrderMessage::query()
                ->where('order_id', $order->id)
                ->where('sender_type', '!=', $viewerType)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $rows = DeliveryOrderMessage::query()
            ->with('sender:id,name')
            ->where('order_id', $order->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->oldest('id')
            ->limit(150)
            ->get()
            ->map(fn (DeliveryOrderMessage $message) => $this->messagePayload($message, $viewerType))
            ->values();

        return response()->json([
            'success' => true,
            'available' => true,
            'locked' => $state['locked'],
            'message' => $state['message'],
            'messages' => $rows,
        ]);
    }

    private function send(Request $request, Order $order, string $senderType, ?int $senderUserId = null): JsonResponse
    {
        if (! $this->deliveryChatStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => self::STORAGE_UNAVAILABLE_MESSAGE,
            ], 503);
        }

        $state = $this->chatState($order);
        if (! $state['available']) {
            return response()->json(['success' => false, 'message' => $state['message']], 409);
        }
        if ($state['locked']) {
            return response()->json(['success' => false, 'message' => 'Chuyến đã kết thúc. Cuộc trò chuyện chỉ còn để xem lại trong 24 giờ.'], 409);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:500'],
        ]);

        $content = trim((string) $data['content']);
        if ($content === '') {
            return response()->json(['success' => false, 'message' => 'Tin nhắn không được để trống.'], 422);
        }

        $this->purgeExpired($order);

        $message = DeliveryOrderMessage::create([
            'order_id' => $order->id,
            'sender_user_id' => $senderUserId ?? $request->user()?->id,
            'sender_type' => $senderType,
            'content' => $content,
        ]);
        $message->load('sender:id,name');

        return response()->json([
            'success' => true,
            'message' => $this->messagePayload($message, $senderType),
        ]);
    }

    private function authorizeCustomer(Request $request, Order $order): void
    {
        abort_unless($request->user() && (int) $order->user_id === (int) $request->user()->id, 403);
    }

    private function authorizeGuest(Request $request, Order $order): void
    {
        abort_unless($order->isGuest() && GuestOrderAccess::canView($order, $request), 403);
        GuestOrderAccess::remember($order);
    }

    private function authorizeShipper(Request $request, Order $order): void
    {
        $shipper = Shipper::where('user_id', $request->user()?->id)->first();
        abort_unless($shipper && (int) $order->shipper_id === (int) $shipper->id, 403);
    }

    private function chatState(Order $order): array
    {
        if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
            return ['available' => false, 'locked' => true, 'message' => 'Đơn này không sử dụng giao hàng.'];
        }

        $status = OrderStatus::normalize((string) $order->status);
        if ($status === OrderStatus::CANCELLED) {
            return ['available' => false, 'locked' => true, 'message' => 'Đơn đã hủy.'];
        }

        $shipper = $order->shipper_id ? Shipper::find($order->shipper_id) : null;
        if (! $shipper || ! $this->hasShipperAccepted($order, $shipper, $status)) {
            return ['available' => false, 'locked' => true, 'message' => 'Chat sẽ mở sau khi tài xế bấm Nhận đơn.'];
        }

        $locked = in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true);

        return [
            'available' => true,
            'locked' => $locked,
            'message' => $locked
                ? 'Chuyến đã kết thúc. Tin nhắn chỉ được lưu/xem lại trong 24 giờ.'
                : 'Chat ngắn theo chuyến. Tin nhắn tự hết hạn sau 24 giờ.',
        ];
    }

    private function hasShipperAccepted(Order $order, Shipper $shipper, string $status): bool
    {
        if (in_array($status, [
            OrderStatus::SHIPPER_PICKED_UP,
            OrderStatus::DELIVERING,
            OrderStatus::DELIVERED,
            OrderStatus::COMPLETED,
        ], true)) {
            return true;
        }

        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return false;
        }

        return DB::table('shipment_history as history')
            ->join('shipments', 'shipments.id', '=', 'history.shipment_id')
            ->where('shipments.order_id', $order->id)
            ->where('shipments.shipper_id', $shipper->id)
            ->where('history.status', 'accepted')
            ->exists();
    }

    private function purgeExpired(Order $order): void
    {
        if (! $this->deliveryChatStorageReady()) {
            return;
        }

        DeliveryOrderMessage::query()
            ->where('order_id', $order->id)
            ->where('created_at', '<', now()->subHours(24))
            ->delete();
    }

    private function deliveryChatStorageReady(): bool
    {
        return Schema::hasTable('delivery_order_messages');
    }

    private function messagePayload(DeliveryOrderMessage $message, string $viewerType): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender?->name ?: ($message->sender_type === 'shipper' ? 'Tài xế' : 'Khách hàng'),
            'content' => $message->content,
            'mine' => $message->sender_type === $viewerType,
            'created_at' => $message->created_at?->toIso8601String(),
            'time' => $message->created_at?->format('H:i'),
        ];
    }
}
