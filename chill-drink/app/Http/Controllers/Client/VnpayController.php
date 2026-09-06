<?php

namespace App\Http\Controllers\Client;

use App\Mail\GuestOrderEmailConfirmationMail;
use App\Support\GuestOrderAccess;
use App\Support\RealtimeOrderNotifier;
use App\Support\OrderStatus;
use App\Support\ScheduledDelivery;
use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use App\Models\Order;
use App\Models\User;
use App\Notifications\GroupOrderCompletedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class VnpayController extends Controller
{
    public function payment(Order $order): RedirectResponse
    {
        abort_unless(GuestOrderAccess::canView($order), 403);

        if ($order->payment_method !== 'vnpay') {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)
                ->with('error', 'Đơn hàng này không sử dụng phương thức thanh toán VNPay.');
        }

        if ($order->payment_status === 'paid') {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)
                ->with('success', 'Đơn hàng này đã được thanh toán.');
        }

        if (! $this->canAcceptPayment($order)) {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)
                ->with('error', 'Đơn hàng ở trạng thái hiện tại không thể mở lại thanh toán VNPay.');
        }

        if ($order->delivery_type === 'scheduled'
            && ($message = ScheduledDelivery::validate(
                $order->scheduled_delivery_time?->toDateTimeString() ?? $order->scheduled_at?->toDateTimeString(),
                (string) ($order->fulfillment_type ?? 'delivery')
            ))) {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)->with('error', $message.' Vui lòng đặt lại đơn giao sau.');
        }

        // Reset payment status to pending when retry
        if ($order->payment_status === 'failed') {
            $order->update(['payment_status' => 'pending']);
        }

        if (! $this->isConfigured()) {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)
                ->with('error', 'VNPay chưa được cấu hình đầy đủ. Vui lòng thử lại sau.');
        }

        $total = (int) $order->total;

        if ($total <= 0) {
            $redirectRoute = auth()->check() ? 'orders.index' : 'checkout.success';

            return redirect()->route($redirectRoute, $order)
                ->with('error', 'Số tiền thanh toán không hợp lệ.');
        }

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => config('services.vnpay.tmn_code'),
            'vnp_Amount' => $total * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => request()->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => "Thanh toan don hang Chill Drink #{$order->id}",
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => config('services.vnpay.return_url'),
            'vnp_TxnRef' => "order_{$order->id}_".time(),
        ];

        if ($order->delivery_type === 'scheduled' && ($paymentDeadline = ScheduledDelivery::paymentDeadline($order))) {
            $inputData['vnp_ExpireDate'] = $paymentDeadline->format('YmdHis');
        }

        $hashData = $this->hashData($inputData);
        $secureHash = hash_hmac('sha512', $hashData, (string) config('services.vnpay.hash_secret'));
        $paymentUrl = rtrim((string) config('services.vnpay.url'), '?')
            .'?'.$hashData
            .'&vnp_SecureHash='.$secureHash;

        return redirect()->away($paymentUrl);
    }

    public function return(Request $request): View
    {
        if (! $this->hasValidSignature($request)) {
            return $this->resultView(
                null,
                'error',
                'Không thể xác nhận thanh toán',
                'Chữ ký phản hồi từ VNPay không hợp lệ.'
            );
        }

        $orderId = $this->orderIdFromTxnRef($request->string('vnp_TxnRef')->toString());
        $order = $orderId ? Order::find($orderId) : null;

        if (! $order) {
            return $this->resultView(
                null,
                'error',
                'Không tìm thấy đơn hàng',
                'Không thể xác định đơn hàng từ phản hồi VNPay.'
            );
        }

        if ($order->payment_method !== 'vnpay') {
            return $this->resultView(
                $order,
                'error',
                'Phương thức thanh toán không hợp lệ',
                'Đơn hàng này không sử dụng phương thức thanh toán VNPay.'
            );
        }

        if ($order->payment_status === 'paid') {
            $this->completePaidGroupOrder($order);

            return $this->resultView(
                $order,
                'success',
                'Cảm ơn bạn đã thanh toán',
                'Đơn hàng đã được thanh toán thành công trước đó.'
            );
        }

        if (! $this->canAcceptPayment($order)) {
            return $this->resultView(
                $order,
                'error',
                'Không thể xác nhận thanh toán',
                'Đơn hàng đã kết thúc hoặc không còn ở trạng thái chờ thanh toán.'
            );
        }

        if ((int) $request->input('vnp_Amount') !== (int) $order->total * 100) {
            return $this->resultView(
                $order,
                'error',
                'Không thể xác nhận thanh toán',
                'Số tiền VNPay trả về không khớp với đơn hàng.'
            );
        }

        if ($this->isSuccessful($request)) {
            if ($order->payment_status !== 'paid' && ScheduledDelivery::paymentWindowExpired($order)) {
                return $this->resultView(
                    $order,
                    'error',
                    'Thời gian thanh toán đã hết',
                    'Đơn giao sau không còn đủ thời gian chuẩn bị và giao đúng hẹn. Vui lòng đặt lại với giờ nhận muộn hơn.'
                );
            }

            [$order, $paymentRecorded] = $this->recordSuccessfulPayment($order->id, $request);
            if ($order->payment_status !== 'paid') {
                return $this->resultView(
                    $order,
                    'error',
                    'Không thể xác nhận thanh toán',
                    'Đơn hàng đã kết thúc trong lúc VNPay xử lý phản hồi. Vui lòng liên hệ hỗ trợ.'
                );
            }
            $this->completePaidGroupOrder($order);

            // Chỉ tiến hành tác vụ phụ ở callback đầu tiên ghi nhận thanh toán.
            // Return URL và IPN của VNPay có thể đến gần như cùng lúc.
            if ($paymentRecorded && $order->isGuest() && $order->isAwaitingEmailConfirmation()) {
                $this->sendGuestEmailConfirmation($order);
            }

            if ($paymentRecorded && (! $order->isGuest() || ! $order->isAwaitingEmailConfirmation())) {
                RealtimeOrderNotifier::orderStatusUpdated($order);
                RealtimeOrderNotifier::orderCreated($order);
            }

            // Tạo conversation chat với chi nhánh nhận đơn (chỉ user đã đăng nhập)
            if (auth()->check() && $order->branch_id) {
                \App\Support\ChatHelper::ensureChatWithOrderBranch($order);
            }

            return $this->resultView(
                $order->fresh(),
                'success',
                'Cảm ơn bạn đã thanh toán',
                $order->isGuest() && $order->isAwaitingEmailConfirmation()
                    ? 'Thanh toán VNPay thành công. Vui lòng kiểm tra email để xác nhận đơn hàng.'
                    : 'Thanh toán VNPay thành công. Đơn hàng đang được xử lý.'
            );
        }

        Order::query()
            ->whereKey($order->id)
            ->where('payment_status', '!=', 'paid')
            ->update(['payment_status' => 'failed']);

        return $this->resultView(
            $order->fresh(),
            'failed',
            'Thanh toán chưa thành công',
            'Giao dịch VNPay thất bại hoặc đã bị hủy. Bạn có thể thanh toán lại trong danh sách đơn hàng.'
        );
    }

    public function ipn(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return $this->ipnResponse('97', 'Invalid signature');
        }

        $orderId = $this->orderIdFromTxnRef($request->string('vnp_TxnRef')->toString());

        if (! $orderId) {
            return $this->ipnResponse('01', 'Order not found');
        }

        $order = Order::query()->find($orderId);

        if (! $order || $order->payment_method !== 'vnpay') {
            return $this->ipnResponse('01', 'Order not found');
        }

        if ((int) $request->input('vnp_Amount') !== (int) $order->total * 100) {
            return $this->ipnResponse('04', 'Invalid amount');
        }

        if ($order->payment_status !== 'paid' && ! $this->canAcceptPayment($order)) {
            return $this->ipnResponse('02', 'Order is not payable');
        }

        if ($this->isSuccessful($request)) {
            if ($order->payment_status !== 'paid' && ScheduledDelivery::paymentWindowExpired($order)) {
                return $this->ipnResponse('02', 'Scheduled payment window expired');
            }

            [$order, $paymentRecorded] = $this->recordSuccessfulPayment($order->id, $request);
            if ($order->payment_status !== 'paid') {
                return $this->ipnResponse('02', 'Order is not payable');
            }
            $this->completePaidGroupOrder($order);

            if (! $paymentRecorded) {
                return $this->ipnResponse('02', 'Order already confirmed');
            }

            if ($order->isGuest() && $order->isAwaitingEmailConfirmation()) {
                $this->sendGuestEmailConfirmation($order);
            }

            if (! $order->isGuest() || ! $order->isAwaitingEmailConfirmation()) {
                RealtimeOrderNotifier::orderStatusUpdated($order);
                RealtimeOrderNotifier::orderCreated($order);
            }
        } else {
            Order::query()
                ->whereKey($order->id)
                ->where('payment_status', '!=', 'paid')
                ->update(['payment_status' => 'failed']);
        }

        return $this->ipnResponse('00', 'Confirm Success');
    }

    /**
     * Ghi nhận thanh toán đúng một lần dù VNPay gọi đồng thời Return URL và IPN.
     *
     * @return array{0: Order, 1: bool} bool=true khi callback hiện tại là callback đầu tiên.
     */
    private function recordSuccessfulPayment(int $orderId, Request $request): array
    {
        return DB::transaction(function () use ($orderId, $request): array {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->payment_status === 'paid') {
                return [$order, false];
            }

            if (! $this->canAcceptPayment($order)) {
                return [$order, false];
            }

            if (ScheduledDelivery::paymentWindowExpired($order)) {
                return [$order, false];
            }

            $status = $order->isGuest() && filled($order->confirmation_token)
                ? OrderStatus::AWAITING_EMAIL_CONFIRMATION
                : OrderStatus::PENDING;

            $order->forceFill([
                'payment_status' => 'paid',
                'status' => $status,
                'vnpay_transaction_id' => $request->input('vnp_TransactionNo'),
                'status_changed_at' => now(),
            ])->save();

            return [$order->fresh(), true];
        }, 3);
    }

    private function isConfigured(): bool
    {
        return collect([
            config('services.vnpay.tmn_code'),
            config('services.vnpay.hash_secret'),
            config('services.vnpay.url'),
            config('services.vnpay.return_url'),
        ])->every(fn ($value) => filled($value));
    }

    private function canAcceptPayment(Order $order): bool
    {
        return in_array(OrderStatus::normalize((string) $order->status), [
            OrderStatus::AWAITING_PAYMENT,
            OrderStatus::AWAITING_EMAIL_CONFIRMATION,
            OrderStatus::PENDING,
        ], true);
    }

    private function completePaidGroupOrder(Order $order): void
    {
        $updated = GroupOrder::query()
            ->where('order_id', $order->id)
            ->where('status', 'closed')
            ->update([
                'status' => 'ordered',
                'status_changed_at' => now(),
                'status_changed_by' => null,
            ]);

        if ($updated === 0) {
            return;
        }

        $groupOrder = GroupOrder::query()->where('order_id', $order->id)->firstOrFail();

        $memberIds = $groupOrder->members()
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $groupOrder->owner_id)
            ->pluck('user_id')
            ->unique();

        User::query()->whereIn('id', $memberIds)->get()
            ->each(fn (User $member) => $member->notify(new GroupOrderCompletedNotification($groupOrder->fresh())));
    }

    private function hasValidSignature(Request $request): bool
    {
        $receivedHash = (string) $request->input('vnp_SecureHash', '');

        if ($receivedHash === '' || blank(config('services.vnpay.hash_secret'))) {
            return false;
        }

        $inputData = collect($request->query())
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'vnp_'))
            ->except(['vnp_SecureHash', 'vnp_SecureHashType'])
            ->all();
        $calculatedHash = hash_hmac(
            'sha512',
            $this->hashData($inputData),
            (string) config('services.vnpay.hash_secret')
        );

        return hash_equals(strtolower($calculatedHash), strtolower($receivedHash));
    }

    private function hashData(array $inputData): string
    {
        ksort($inputData);

        return collect($inputData)
            ->map(fn ($value, $key) => urlencode((string) $key).'='.urlencode((string) $value))
            ->implode('&');
    }

    private function orderIdFromTxnRef(string $txnRef): ?int
    {
        return preg_match('/^order_(\d+)_\d+$/', $txnRef, $matches)
            ? (int) $matches[1]
            : null;
    }

    private function isSuccessful(Request $request): bool
    {
        return $request->input('vnp_ResponseCode') === '00'
            && $request->input('vnp_TransactionStatus') === '00';
    }

    private function ipnResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'RspCode' => $code,
            'Message' => $message,
        ]);
    }

    private function resultView(?Order $order, string $result, string $title, string $message): View
    {
        $order?->load('orderItems.product', 'branch');

        $payload = compact('order', 'result', 'title', 'message');

        if ($order && $order->isGuest()) {
            GuestOrderAccess::remember($order);
            GuestOrderAccess::storeConvertPayload($order);
            $payload['guestConvert'] = session('guest_convert');
        }

        return view('client.checkout.success', $payload);
    }

    private function sendGuestEmailConfirmation(Order $order): void
    {
        if (blank($order->guest_email)) {
            Log::warning('Guest order email skipped: no email address.', ['order_id' => $order->id]);
            return;
        }

        try {
            Mail::to($order->guest_email)
                ->send(new GuestOrderEmailConfirmationMail($order));

            Log::info('Guest order confirmation email sent after VNPay payment.', [
                'order_id' => $order->id,
                'to'       => $order->guest_email,
                'mailer'   => config('mail.default'),
                'host'     => config('mail.mailers.smtp.host'),
            ]);

        } catch (Throwable $e) {
            Log::error('Guest order confirmation email FAILED after VNPay payment.', [
                'order_id'     => $order->id,
                'to'           => $order->guest_email,
                'mailer'       => config('mail.default'),
                'smtp_host'    => config('mail.mailers.smtp.host'),
                'smtp_port'    => config('mail.mailers.smtp.port'),
                'smtp_user'    => config('mail.mailers.smtp.username'),
                'error_class'  => get_class($e),
                'message'      => $e->getMessage(),
            ]);
        }
    }
}
