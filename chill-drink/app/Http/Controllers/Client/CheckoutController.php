<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Support\ShippingFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

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
        $paymentOptions = $this->paymentOptions();

        return view('client.checkout.index', compact('cart', 'shippingDistanceOptions', 'shippingMethods', 'paymentOptions'));
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => ['required', Rule::in(array_keys($this->paymentOptions()))],
            'shipping_method_ui' => ['required', Rule::in(array_keys(ShippingFee::methods()))],
            'shipping_address_ui' => 'required|string|max:255',
            'shipping_area_ui' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ], [
            'shipping_address_ui.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'shipping_method_ui.required' => 'Vui lòng chọn phương thức giao hàng.',
            'shipping_method_ui.in' => 'Phương thức giao hàng không hợp lệ.',
        ]);

        $fullCart = session()->get('cart', []);
        $selectedKeys = session()->get('checkout_cart_keys');
        $cart = $this->cartForCheckout($fullCart, $selectedKeys);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        try {
            DB::beginTransaction();

            $orderItems = $this->prepareOrderItems($cart);
            $subtotal = collect($orderItems)->sum('total_price');

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

            if (Schema::hasColumn('orders', 'payment_status')) {
                $orderData['payment_status'] = 'pending';
            }

            $order = Order::create($orderData);

            // Create order items
            foreach ($orderItems as $item) {
                OrderItem::create($this->orderItemData($order->id, $item));
            }

            $this->removeCheckedOutItems($fullCart, $selectedKeys);

            DB::commit();

            return redirect()->route('home')->with('success', 'Đặt hàng thành công!');
            
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Checkout failed.', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Có lỗi xảy ra, vui lòng thử lại!';

            return redirect()->back()->with('error', $message);
        }
    }

    private function paymentOptions(): array
    {
        return [
            'cod' => [
                'title' => 'Thanh toán khi nhận hàng',
                'desc' => 'Trả tiền mặt sau khi nhận đồ uống.',
                'icon' => 'bi-cash-coin',
            ],
            'bank_transfer' => [
                'title' => 'Chuyển khoản ngân hàng',
                'desc' => 'Nhân viên xác nhận sau khi nhận chuyển khoản.',
                'icon' => 'bi-bank',
            ],
            'momo' => [
                'title' => 'Ví Momo',
                'desc' => 'Thanh toán nhanh qua ví điện tử Momo.',
                'icon' => 'bi-phone',
            ],
            'vnpay' => [
                'title' => 'VNPay',
                'desc' => 'Hỗ trợ thẻ ATM, QR và ngân hàng nội địa.',
                'icon' => 'bi-credit-card',
            ],
        ];
    }

    private function prepareOrderItems(array $cart): array
    {
        $items = [];

        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $cartKey;

            if (! is_numeric($productId)) {
                throw new \RuntimeException('Giỏ hàng có sản phẩm chưa tồn tại trong kho. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách.');
            }

            $product = Product::query()
                ->lockForUpdate()
                ->whereKey((int) $productId)
                ->where('status', true)
                ->first();

            if (! $product) {
                throw new \RuntimeException('Một sản phẩm trong giỏ đã ngừng bán. Vui lòng kiểm tra lại giỏ hàng.');
            }

            $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));

            if (Schema::hasColumn('products', 'stock')) {
                $stock = (int) ($product->stock ?? 0);

                if ($stock < $quantity) {
                    throw new \RuntimeException("Sản phẩm {$product->name} chỉ còn {$stock} món trong kho.");
                }

                $product->decrement('stock', $quantity);
            }

            $unitPrice = max(0, (int) ($item['price'] ?? $product->price ?? 0));

            $items[] = [
                'product_id' => (int) $productId,
                'product_size_id' => $this->resolveProductSizeId((int) $productId, $item, $unitPrice),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'ice_level' => (int) ($item['ice_level'] ?? 100),
                'sugar_level' => (int) ($item['sugar_level'] ?? 100),
            ];
        }

        if (empty($items)) {
            throw new \RuntimeException('Giỏ hàng trống, vui lòng thêm sản phẩm trước khi thanh toán.');
        }

        return $items;
    }

    private function resolveProductSizeId(int $productId, array $item, int $unitPrice): ?int
    {
        if (! Schema::hasColumn('order_items', 'product_size_id')) {
            return null;
        }

        if (! Schema::hasTable('product_sizes') || ! Schema::hasTable('sizes')) {
            throw new \RuntimeException('Thiếu bảng size sản phẩm, chưa thể tạo chi tiết đơn hàng.');
        }

        if (! empty($item['product_size_id'])) {
            $productSizeId = ProductSize::query()
                ->whereKey((int) $item['product_size_id'])
                ->where('product_id', $productId)
                ->value('id');

            if ($productSizeId) {
                return (int) $productSizeId;
            }
        }

        $sizeCode = strtoupper((string) ($item['size'] ?? 'M'));
        $sizeNames = array_values(array_unique([$sizeCode, "Size {$sizeCode}"]));
        $productSize = ProductSize::query()
            ->where('product_id', $productId)
            ->whereHas('size', fn ($query) => $query->whereIn('name', $sizeNames))
            ->first();

        if ($productSize) {
            return (int) $productSize->id;
        }

        $size = Size::query()
            ->whereIn('name', $sizeNames)
            ->first();

        if (! $size) {
            $size = Size::create([
                'name' => $sizeCode,
                'multiplier' => 1,
            ]);
        }

        return (int) ProductSize::firstOrCreate(
            [
                'product_id' => $productId,
                'size_id' => $size->id,
            ],
            [
                'price' => $unitPrice,
            ]
        )->id;
    }

    private function orderItemData(int $orderId, array $item): array
    {
        $data = [
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
        ];

        foreach (['product_size_id', 'unit_price', 'total_price', 'ice_level', 'sugar_level'] as $column) {
            if (Schema::hasColumn('order_items', $column)) {
                $data[$column] = $item[$column];
            }
        }

        if (Schema::hasColumn('order_items', 'price')) {
            $data['price'] = $item['unit_price'];
        }

        return $data;
    }

    private function removeCheckedOutItems(array $fullCart, mixed $selectedKeys): void
    {
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

            return;
        }

        session()->forget(['cart', 'checkout_cart_keys']);
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
