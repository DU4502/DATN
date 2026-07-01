<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\GuestOrderConfirmationMail;
use App\Models\Branch;
use App\Models\Order;
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

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30', 'regex:/^0[0-9]{9,10}$/'],
            'guest_email' => ['required', 'string', 'email', 'max:255'],
            'delivery_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'shipping_address_ui' => ['nullable', 'string', 'max:255', 'required_if:delivery_type,delivery'],
            'shipping_area_ui' => ['nullable', 'string', 'max:255', 'required_if:delivery_type,delivery'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id', 'required_if:delivery_type,pickup'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'guest_name.required' => 'Vui lòng nhập họ tên.',
            'guest_phone.required' => 'Vui lòng nhập số điện thoại.',
            'guest_phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số.',
            'guest_email.required' => 'Vui lòng nhập email.',
            'guest_email.email' => 'Email không đúng định dạng.',
            'shipping_address_ui.required_if' => 'Vui lòng nhập địa chỉ giao hàng.',
            'shipping_area_ui.required_if' => 'Vui lòng chọn khu vực giao hàng.',
            'branch_id.required_if' => 'Vui lòng chọn chi nhánh lấy hàng.',
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
        $deliveryType = $guestInfo['delivery_type'] ?? 'delivery';

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

        if (($guestInfo['delivery_type'] ?? '') === 'pickup' && ! empty($guestInfo['branch_id'])) {
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
            'payment_method' => ['required', Rule::in(array_keys($this->paymentOptions()))],
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

            $orderItems = $this->prepareOrderItems($cart);
            $subtotal = collect($orderItems)->sum('total_price');
            $deliveryType = $guestInfo['delivery_type'] ?? 'delivery';

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

            $orderData = [
                'user_id' => null,
                'guest_name' => $guestInfo['guest_name'],
                'guest_phone' => $guestInfo['guest_phone'],
                'guest_email' => strtolower($guestInfo['guest_email']),
                'guest_token' => $guestToken,
                'delivery_type' => $deliveryType,
                'branch_id' => $deliveryType === 'pickup' ? ($guestInfo['branch_id'] ?? null) : null,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
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

            $this->sendConfirmationEmail($order);

            RealtimeOrderNotifier::orderStatusUpdated($order);

            if ($order->payment_method !== 'vnpay') {
                RealtimeOrderNotifier::orderCreated($order);
            }

            if ($order->payment_method === 'vnpay') {
                return redirect()
                    ->route('vnpay.payment', $order)
                    ->with('success', 'Đơn hàng đã được tạo. Vui lòng thanh toán qua VNPay.');
            }

            return redirect()->route('checkout.success', $order);

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

    private function sendConfirmationEmail(Order $order): void
    {
        if (blank($order->guest_email)) {
            return;
        }

        try {
            Mail::to($order->guest_email)->send(new GuestOrderConfirmationMail($order));
        } catch (Throwable $e) {
            Log::warning('Guest order confirmation email failed.', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
