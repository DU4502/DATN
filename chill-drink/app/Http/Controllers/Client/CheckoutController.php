<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ShippingFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    /**
     * Display checkout page
     */
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
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
        
        $shippingDistanceOptions = ShippingFee::distanceOptions();
        $shippingMethods = ShippingFee::methods();

        return view('client.checkout.index', compact('cart', 'shippingDistanceOptions', 'shippingMethods'));
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay',
            'shipping_method_ui' => 'required|in:standard,fast',
            'shipping_address_ui' => 'nullable|string|max:255',
            'shipping_area_ui' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);

        $fullCart = session()->get('cart', []);
        $selectedKeys = session()->get('checkout_cart_keys');
        $cart = $this->cartForCheckout($fullCart, $selectedKeys);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        DB::beginTransaction();
        
        try {
            // Calculate total with shipping fee on the server so the submitted amount cannot be changed from the browser.
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $shippingQuote = ShippingFee::quoteForAddress(
                $request->shipping_address_ui,
                $request->shipping_area_ui,
                $request->shipping_method_ui
            );
            $discount = 0;
            $grandTotal = $subtotal + $shippingQuote['total_fee'] - $discount;
            $addressText = trim(collect([
                $request->shipping_address_ui,
                $request->shipping_area_ui,
            ])->filter()->implode(', '));
            $shippingNote = sprintf(
                'Giao hàng: %s, %skm (%s), phí ship %s%s',
                $shippingQuote['method_label'],
                rtrim(rtrim(number_format($shippingQuote['distance_km'], 1, '.', ''), '0'), '.'),
                $shippingQuote['distance_label'],
                ShippingFee::formatCurrency($shippingQuote['total_fee']),
                $addressText ? ", địa chỉ: {$addressText}" : ''
            );
            $note = trim((string) $request->note);
            $note = trim($note ? "{$note}\n{$shippingNote}" : $shippingNote);
            $note = mb_substr($note, 0, 500);

            // Create order
            $orderData = [
                'user_id' => auth()->id(),
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
                $orderData['shipping_fee'] = $shippingQuote['total_fee'];
            }

            if (Schema::hasColumn('orders', 'discount')) {
                $orderData['discount'] = $discount;
            }

            if (Schema::hasColumn('orders', 'total')) {
                $orderData['total'] = $grandTotal;
            }

            $order = Order::create($orderData);

            // Create order items
            foreach ($cart as $productId => $item) {
                $productId = $item['product_id'] ?? $productId;

                if (is_numeric($productId)) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            }

            // Remove only the checked items when the customer checked out a partial cart.
            if (is_array($selectedKeys) && count($selectedKeys) > 0) {
                foreach ($selectedKeys as $cartKey) {
                    unset($fullCart[$cartKey]);
                }

                if (empty($fullCart)) {
                    session()->forget('cart');
                } else {
                    session()->put('cart', $fullCart);
                }

                session()->forget('checkout_cart_keys');
            } else {
                session()->forget('cart');
            }

            DB::commit();

            return redirect()->route('home')->with('success', 'Đặt hàng thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    }

    private function selectedCartKeys(mixed $keys, array $cart): array
    {
        $keys = is_array($keys) ? $keys : [$keys];

        return array_values(array_filter(
            array_map('strval', $keys),
            fn (string $key) => array_key_exists($key, $cart)
        ));
    }

    private function cartForCheckout(array $cart, mixed $selectedKeys): array
    {
        if (! is_array($selectedKeys) || empty($selectedKeys)) {
            return $cart;
        }

        return array_intersect_key($cart, array_flip($this->selectedCartKeys($selectedKeys, $cart)));
    }
}
