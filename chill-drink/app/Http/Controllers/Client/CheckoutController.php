<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    /**
     * Display checkout page
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        return view('client.checkout.index', compact('cart'));
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay,card,wallet',
            'note' => 'nullable|string|max:500',
        ]);

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        DB::beginTransaction();
        
        try {
            // Calculate total
            $total = 0;
            foreach ($cart as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            $paymentMethod = match ($request->payment_method) {
                'bank_transfer' => 'card',
                default => $request->payment_method,
            };

            // Create order
            $orderData = [
                'user_id' => auth()->id(),
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'note' => $request->note,
            ];

            if (Schema::hasColumn('orders', 'subtotal')) {
                $orderData['subtotal'] = $total;
            }
            if (Schema::hasColumn('orders', 'shipping_fee')) {
                $orderData['shipping_fee'] = 0;
            }
            if (Schema::hasColumn('orders', 'discount')) {
                $orderData['discount'] = 0;
            }
            if (Schema::hasColumn('orders', 'total')) {
                $orderData['total'] = $total;
            }
            if (Schema::hasColumn('orders', 'total_price')) {
                $orderData['total_price'] = $total;
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                $orderData['payment_status'] = 'pending';
            }

            $order = Order::create($orderData);

            // Create order items
            foreach ($cart as $cartKey => $item) {
                $productId = $item['product_id'] ?? $cartKey;

                if (is_numeric($productId)) {
                    $productId = (int) $productId;
                    $quantity = max(1, (int) ($item['quantity'] ?? 1));
                    $unitPrice = max(0, (int) ($item['price'] ?? 0));
                    $sizeId = (int) ($item['size_id'] ?? 0);

                    if (Schema::hasColumn('order_items', 'product_size_id') && $sizeId <= 0) {
                        $sizeId = (int) DB::table('product_sizes')
                            ->where('product_id', $productId)
                            ->orderBy('id')
                            ->value('id');
                    }

                    $orderItemData = [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ];

                    if (Schema::hasColumn('order_items', 'price')) {
                        $orderItemData['price'] = $unitPrice;
                    }
                    if (Schema::hasColumn('order_items', 'unit_price')) {
                        $orderItemData['unit_price'] = $unitPrice;
                    }
                    if (Schema::hasColumn('order_items', 'total_price')) {
                        $orderItemData['total_price'] = $unitPrice * $quantity;
                    }
                    if (Schema::hasColumn('order_items', 'sugar_level')) {
                        $orderItemData['sugar_level'] = max(0, min(150, (int) ($item['sugar_level'] ?? 30)));
                    }
                    if (Schema::hasColumn('order_items', 'ice_level')) {
                        $orderItemData['ice_level'] = max(0, min(150, (int) ($item['ice_level'] ?? 100)));
                    }

                    if (Schema::hasColumn('order_items', 'product_size_id')) {
                        if ($sizeId <= 0) {
                            continue;
                        }

                        $orderItemData['product_size_id'] = $sizeId;
                    }

                    OrderItem::create($orderItemData);
                }
            }

            // Clear cart
            session()->forget('cart');

            DB::commit();

            return redirect()->route('home')->with('success', 'Đặt hàng thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    }
}
