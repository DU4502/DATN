<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay',
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

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $total,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'note' => $request->note,
            ]);

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
