<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VnpayController extends Controller
{
    public function payment(Order $order): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        if ($order->payment_method !== 'vnpay') {
            return redirect()->route('profile.orders')
                ->with('error', 'Đơn hàng này không sử dụng phương thức thanh toán VNPay.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('profile.orders')
                ->with('success', 'Đơn hàng này đã được thanh toán.');
        }

        if (! $this->isConfigured()) {
            return redirect()->route('profile.orders')
                ->with('error', 'VNPay chưa được cấu hình đầy đủ. Vui lòng thử lại sau.');
        }

        $total = (int) $order->total;

        if ($total <= 0) {
            return redirect()->route('profile.orders')
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
            return $this->resultView(
                $order,
                'success',
                'Cảm ơn bạn đã thanh toán',
                'Đơn hàng đã được thanh toán thành công trước đó.'
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
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'vnpay_transaction_id' => $request->input('vnp_TransactionNo'),
            ]);

            return $this->resultView(
                $order->fresh(),
                'success',
                'Cảm ơn bạn đã thanh toán',
                'Thanh toán VNPay thành công. Đơn hàng đang được xử lý.'
            );
        }

        $order->update(['payment_status' => 'failed']);

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

        return DB::transaction(function () use ($request, $orderId) {
            $order = Order::query()->lockForUpdate()->find($orderId);

            if (! $order || $order->payment_method !== 'vnpay') {
                return $this->ipnResponse('01', 'Order not found');
            }

            if ((int) $request->input('vnp_Amount') !== (int) $order->total * 100) {
                return $this->ipnResponse('04', 'Invalid amount');
            }

            if ($order->payment_status === 'paid') {
                return $this->ipnResponse('02', 'Order already confirmed');
            }

            if ($this->isSuccessful($request)) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'vnpay_transaction_id' => $request->input('vnp_TransactionNo'),
                ]);
            } else {
                $order->update(['payment_status' => 'failed']);
            }

            return $this->ipnResponse('00', 'Confirm Success');
        });
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
        $order?->load('orderItems.product');

        return view('client.checkout.success', compact('order', 'result', 'title', 'message'));
    }
}
