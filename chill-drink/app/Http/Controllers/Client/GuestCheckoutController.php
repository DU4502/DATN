<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\GuestOrderEmailConfirmationMail;
use App\Models\Branch;
use App\Models\Order;
use App\Services\OrderCodeGenerator;
use App\Services\ProductAvailabilityService;
use App\Support\GuestOrderAccess;
use App\Support\RealtimeOrderNotifier;
use App\Support\ShippingFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\ScheduledDelivery;
use Throwable;

class GuestCheckoutController extends CheckoutController
{
    public function index(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('checkout.index', $request->query());
        }

        $cart = session()->get('cart', []);
        $cart = $this->normalizeCartForCheckout($cart);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm.');
        }

        if (! empty($this->invalidCheckoutCartKeys($cart))) {
            return redirect()->route('cart.index')->with(
                'error',
                'Giỏ hàng có sản phẩm demo chưa thể thanh toán. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách sản phẩm thật.'
            );
        }

        if ($request->query->has('items')) {
            $selectedKeys = $this->selectedCartKeys($request->query('items', []), $cart);

            if (empty($selectedKeys)) {
                return redirect()->route('cart.index')->with('error', 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
            }

            $cart = array_intersect_key($cart, array_flip($selectedKeys));
            session(['checkout_cart_keys' => $selectedKeys]);
        } else {
            session()->forget('checkout_cart_keys');
        }

        $cart = $this->hydrateCheckoutCart($cart);
        $subtotal = $this->cartSubtotal($cart);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get();
        $guestInfo = session('guest_checkout', []);
        $shippingDistanceOptions = ShippingFee::distanceOptions();

        return view('client.checkout.guest.index', compact(
            'cart',
            'subtotal',
            'branches',
            'guestInfo',
            'shippingDistanceOptions'
        ));
    }

    public function storeInfo(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('checkout.index');
        }

        if (! $request->filled('fulfillment_type') && in_array($request->input('delivery_type'), ['delivery', 'pickup'], true)) {
            $request->merge(['fulfillment_type' => $request->input('delivery_type'), 'delivery_type' => 'now']);
        }

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30', 'regex:/^0[0-9]{9,10}$/'],
            'guest_email' => ['required', 'string', 'email', 'max:255'],
            'fulfillment_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'shipping_address_ui' => ['nullable', 'string', 'max:255', 'required_if:fulfillment_type,delivery'],
            'branch_id' => [
                'nullable',
                'required_if:fulfillment_type,pickup',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
            'delivery_type' => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_delivery_time' => [
                'nullable', 'date', 'required_if:delivery_type,scheduled',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('delivery_type') !== 'scheduled') return;
                    if ($message = ScheduledDelivery::validate($value)) $fail($message);
                }
            ],
            'note' => ['nullable', 'string', 'max:500'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'guest_name.required' => 'Vui lòng nhập họ tên.',
            'guest_phone.required' => 'Vui lòng nhập số điện thoại.',
            'guest_phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số.',
            'guest_email.required' => 'Vui lòng nhập email.',
            'guest_email.email' => 'Email không đúng định dạng.',
            'shipping_address_ui.required_if' => 'Vui lòng nhập địa chỉ giao hàng.',
            'branch_id.required_if' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh được chọn không tồn tại.',
            'scheduled_delivery_time.required_if' => 'Vui lòng chọn ngày và giờ muốn nhận hàng.',
        ]);

        session(['guest_checkout' => $validated]);

        return redirect()->route('checkout.guest.payment');
    }

    public function payment()
    {
        if (auth()->check()) {
            return redirect()->route('checkout.index');
        }

        $guestInfo = session('guest_checkout');

        if (empty($guestInfo)) {
            return redirect()->route('checkout.guest.index')->with('error', 'Vui lòng nhập thông tin nhận hàng trước.');
        }

        $fullCart = session()->get('cart', []);
        $selectedKeys = session()->get('checkout_cart_keys');
        $cart = $this->normalizeCartForCheckout($this->cartForCheckout($fullCart, $selectedKeys));

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm.');
        }

        $cart = $this->hydrateCheckoutCart($cart);
        $subtotal = $this->cartSubtotal($cart);
        $paymentOptions = $this->paymentOptions();
        $deliveryType = $guestInfo['fulfillment_type'] ?? 'delivery';

        if ($deliveryType === 'pickup') {
            $shippingFee = 0;
        } else {
            $shippingQuote = ShippingFee::quoteForAddress(
                $guestInfo['shipping_address_ui'] ?? null,
                $guestInfo['shipping_area_ui'] ?? null,
                'standard'
            );
            $shippingFee = $shippingQuote['total_fee'];
        }

        $grandTotal = max(0, $subtotal + $shippingFee);
        $branch = null;

        if (($guestInfo['fulfillment_type'] ?? '') === 'pickup' && ! empty($guestInfo['branch_id'])) {
            $branch = Branch::query()->find($guestInfo['branch_id']);
        }

        return view('client.checkout.guest.payment', compact(
            'cart',
            'subtotal',
            'shippingFee',
            'grandTotal',
            'paymentOptions',
            'guestInfo',
            'branch'
        ));
    }

    public function process(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('checkout.index');
        }

        $guestInfo = session('guest_checkout');

        if (empty($guestInfo)) {
            return redirect()->route('checkout.guest.index')->with('error', 'Vui lòng nhập thông tin nhận hàng trước.');
        }

        $request->validate([
            'payment_method' => [
                'required',
                Rule::in(array_keys($this->paymentOptions())),
                function ($attribute, $value, $fail) use ($guestInfo) {
                    if (($guestInfo['delivery_type'] ?? 'now') === 'scheduled' && $value === 'cod') {
                        $fail('Đơn đặt giao sau phải thanh toán trước. Vui lòng chọn VNPay để tiếp tục.');
                    }
                },
            ],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ]);

        $fullCart = session()->get('cart', []);
        $selectedKeys = session()->get('checkout_cart_keys');
        $cart = $this->normalizeCartForCheckout($this->cartForCheckout($fullCart, $selectedKeys));

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm.');
        }

        if (! empty($this->invalidCheckoutCartKeys($cart))) {
            return redirect()->route('cart.index')->with(
                'error',
                'Giỏ hàng có sản phẩm demo chưa thể thanh toán. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách sản phẩm thật.'
            );
        }

        try {
            DB::beginTransaction();

            $deliveryType = $guestInfo['fulfillment_type'] ?? 'delivery';
            $branchId = $guestInfo['branch_id'] ?? null;
            if (! $branchId && $deliveryType === 'delivery') {
                $branchId = Branch::query()->where('status', true)->orderBy('id')->value('id');
            }

            $branch = Branch::query()->whereKey($branchId)->where('status', true)->first();
            if (! $branch) {
                throw new \RuntimeException('Vui lòng chọn một chi nhánh đang hoạt động trước khi đặt hàng.');
            }

            app(ProductAvailabilityService::class)->assertCartAvailable($cart, $branch, true);
            $orderItems = $this->prepareOrderItems($cart);
            $subtotal = collect($orderItems)->sum('total_price');

            if ($deliveryType === 'pickup') {
                $shippingFee = 0;
                $addressText = Branch::query()->find($guestInfo['branch_id'] ?? 0)?->name ?? 'Chi nhánh';
                $shippingNote = "Lấy tại chi nhánh: {$addressText}";
            } else {
                $shippingQuote = ShippingFee::quoteForAddress(
                    $guestInfo['shipping_address_ui'] ?? null,
                    $guestInfo['shipping_area_ui'] ?? null,
                    'standard'
                );
                $shippingFee = $shippingQuote['total_fee'];
                $addressText = trim(collect([
                    $guestInfo['shipping_address_ui'] ?? null,
                    $guestInfo['shipping_area_ui'] ?? null,
                ])->filter()->implode(', '));
                $shippingNote = sprintf(
                    'Giao hàng: phí cố định %s%s',
                    ShippingFee::formatCurrency($shippingFee),
                    $addressText ? ", địa chỉ: {$addressText}" : ''
                );
            }

            $grandTotal = max(0, $subtotal + $shippingFee);
            $note = trim((string) ($guestInfo['note'] ?? ''));
            $note = trim($note ? "{$note}\n{$shippingNote}" : $shippingNote);
            $note = mb_substr($note, 0, 500);
            $guestToken = Str::random(48);

            if (! $branchId && $deliveryType === 'pickup') {
                throw new \RuntimeException('Vui lòng chọn chi nhánh trước khi đặt hàng.');
            }

            $orderData = [
                'order_code'     => OrderCodeGenerator::generate($branchId, $deliveryType),
                'user_id'        => null,
                'guest_name'     => $guestInfo['guest_name'],
                'guest_phone'    => $guestInfo['guest_phone'],
                'guest_email'    => strtolower($guestInfo['guest_email']),
                'guest_token'    => $guestToken,
                'fulfillment_type' => $deliveryType,
                'branch_id'      => $branchId,
                'delivery_type'  => $guestInfo['delivery_type'] ?? 'now',
                'scheduled_delivery_time' => ($guestInfo['delivery_type'] ?? 'now') === 'scheduled' ? ($guestInfo['scheduled_delivery_time'] ?? null) : null,
                'scheduled_at' => ($guestInfo['delivery_type'] ?? 'now') === 'scheduled' ? ($guestInfo['scheduled_delivery_time'] ?? null) : null,
                'delivery_note' => $guestInfo['delivery_note'] ?? null,
                'payment_method' => $request->payment_method,
                // Đơn hàng ẩn với admin cho đến khi guest xác nhận email
                'status'                         => 'awaiting_email_confirmation',
                'confirmation_token'             => Str::random(48),
                'confirmation_token_expires_at'  => now()->addMinutes(15),
                'note' => $note,
            ];

            if (Schema::hasColumn('orders', 'total_price')) {
                $orderData['total_price'] = $grandTotal;
            }

            if (Schema::hasColumn('orders', 'subtotal')) {
                $orderData['subtotal'] = $subtotal;
            }

            if (Schema::hasColumn('orders', 'shipping_fee')) {
                $orderData['shipping_fee'] = $shippingFee;
            }

            if (Schema::hasColumn('orders', 'discount')) {
                $orderData['discount'] = 0;
            }

            if (Schema::hasColumn('orders', 'total')) {
                $orderData['total'] = $grandTotal;
            }

            if (Schema::hasColumn('orders', 'payment_status')) {
                $orderData['payment_status'] = 'pending';
            }

            $order = Order::create($orderData);

            foreach ($orderItems as $item) {
                $orderItem = \App\Models\OrderItem::create($this->orderItemData($order->id, $item));
                $this->recordOrderItemToppings((int) $orderItem->id, $item['toppings'] ?? []);
            }

            $this->removeCheckedOutItems($fullCart, $selectedKeys);

            DB::commit();

            session()->forget('guest_checkout');
            GuestOrderAccess::remember($order);
            GuestOrderAccess::storeConvertPayload($order);

            // Chỉ gửi email xác nhận cho COD orders
            // VNPay orders sẽ gửi email sau khi thanh toán thành công
            if ($order->payment_method !== 'vnpay') {
                $this->sendEmailConfirmationRequest($order);
            }

            // Chưa notify admin — đơn chỉ được chuyển lên admin sau khi guest xác nhận email
            // RealtimeOrderNotifier chỉ chạy sau khi confirmEmail()

            if ($order->payment_method === 'vnpay') {
                return redirect()
                    ->route('vnpay.payment', $order)
                    ->with('success', 'Đơn hàng đã được tạo. Vui lòng thanh toán qua VNPay.');
            }

            return redirect()->route('checkout.guest.pending-confirmation', $order);

        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Guest checkout failed.', [
                'message' => $e->getMessage(),
            ]);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Có lỗi xảy ra, vui lòng thử lại!';

            return redirect()->back()->withInput()->with('error', $message);
        }
    }

    public function success(Order $order)
    {
        abort_unless(GuestOrderAccess::canView($order), 403);

        $order->load('orderItems.product', 'branch');

        return view('client.checkout.success', [
            'order' => $order,
            'result' => 'success',
            'title' => 'Cảm ơn bạn! Đơn hàng đã được tiếp nhận',
            'message' => 'Đơn hàng đã được tiếp nhận và sẽ sớm được chuẩn bị.',
            'guestConvert' => session('guest_convert'),
        ]);
    }

    public function track(Request $request, Order $order)
    {
        abort_unless(GuestOrderAccess::canView($order, $request), 403);

        GuestOrderAccess::remember($order);
        $order->load('orderItems.product', 'branch');

        return view('client.checkout.success', [
            'order' => $order,
            'result' => 'success',
            'title' => 'Theo dõi đơn hàng',
            'message' => 'Trạng thái đơn hàng được cập nhật theo thời gian thực.',
            'guestConvert' => session('guest_convert'),
        ]);
    }

    /**
     * Hiển thị trang thông báo cho khách biết cần vào email để xác nhận đơn.
     */
    public function pendingConfirmation(Order $order)
    {
        abort_unless(GuestOrderAccess::canView($order), 403);

        // Nếu đã xác nhận rồi thì chuyển thẳng về success
        if (! $order->isAwaitingEmailConfirmation()) {
            return redirect()->route('checkout.success', $order);
        }

        return view('client.checkout.guest.pending-confirmation', compact('order'));
    }

    /**
     * Xử lý khi khách click link xác nhận trong email.
     */
    public function confirmEmail(Request $request, Order $order)
    {
        $token = (string) $request->query('token', '');

        // Đã xác nhận trước đó
        if (! $order->isAwaitingEmailConfirmation()) {
            return view('client.checkout.guest.confirm-email-result', [
                'status'  => 'already_confirmed',
                'order'   => $order,
                'message' => 'Đơn hàng này đã được xác nhận trước đó.',
            ]);
        }

        // Token không hợp lệ hoặc hết hạn
        if (! $order->isConfirmationTokenValid($token)) {
            $expired = $order->confirmation_token_expires_at?->isPast();

            return view('client.checkout.guest.confirm-email-result', [
                'status'  => $expired ? 'expired' : 'invalid',
                'order'   => $order,
                'message' => $expired
                    ? 'Link xác nhận đã hết hạn (15 phút). Đơn hàng đã bị huỷ tự động.'
                    : 'Token xác nhận không hợp lệ hoặc đã được sử dụng.',
            ]);
        }

        try {
            DB::beginTransaction();

            // If already paid via VNPay, change to in_progress, otherwise pending
            $newStatus = $order->payment_status === 'paid' ? 'in_progress' : 'pending';
            
            $order->update([
                'status'                         => $newStatus,
                'confirmation_token'             => null,   // Dùng một lần, xoá sau khi dùng
                'confirmation_token_expires_at'  => null,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Guest email confirmation failed.', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);

            return view('client.checkout.guest.confirm-email-result', [
                'status'  => 'error',
                'order'   => $order,
                'message' => 'Có lỗi xảy ra khi xác nhận. Vui lòng thử lại hoặc liên hệ hỗ trợ.',
            ]);
        }

        // Sau khi xác nhận thành công: notify admin & realtime
        try {
            RealtimeOrderNotifier::orderStatusUpdated($order);
            RealtimeOrderNotifier::orderCreated($order);
        } catch (Throwable $e) {
            Log::warning('Realtime notify after email confirm failed.', ['order_id' => $order->id]);
        }

        GuestOrderAccess::remember($order);

        return view('client.checkout.guest.confirm-email-result', [
            'status'  => 'success',
            'order'   => $order,
            'message' => 'Đơn hàng đã được xác nhận thành công! Chúng tôi sẽ bắt đầu chuẩn bị ngay.',
        ]);
    }

    /**
     * Gửi email yêu cầu xác nhận đơn hàng guest.
     * Gửi synchronous — không dùng queue để tránh phụ thuộc queue worker.
     */
    private function sendEmailConfirmationRequest(Order $order): void
    {
        if (blank($order->guest_email)) {
            Log::warning('Guest order email skipped: no email address.', ['order_id' => $order->id]);
            return;
        }

        try {
            Mail::to($order->guest_email)
                ->send(new GuestOrderEmailConfirmationMail($order));

            Log::info('Guest order confirmation email sent.', [
                'order_id' => $order->id,
                'to'       => $order->guest_email,
                'mailer'   => config('mail.default'),
                'host'     => config('mail.mailers.smtp.host'),
            ]);

        } catch (Throwable $e) {
            // Log đầy đủ để dễ debug: class lỗi + message + mailer config
            Log::error('Guest order confirmation email FAILED.', [
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

    private function sendConfirmationEmail(Order $order): void
    {
        if (blank($order->guest_email)) {
            return;
        }

        try {
            Mail::to($order->guest_email)->send(new \App\Mail\GuestOrderConfirmationMail($order));
        } catch (Throwable $e) {
            Log::warning('Guest order confirmation email failed.', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);
        }
    }
}
