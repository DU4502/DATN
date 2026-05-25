<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function demoProducts(): array
    {
        return [
            'demo-wild-berry-bliss' => ['name' => 'Sinh Tố Dâu Rừng', 'price' => 65000, 'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85'],
            'demo-matcha-latte-da' => ['name' => 'Matcha Latte Đá', 'price' => 57000, 'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85'],
            'demo-citrus-sunset' => ['name' => 'Nước Ép Cam Chanh Dây', 'price' => 49000, 'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85'],
            'demo-tra-sua-tran-chau-demo' => ['name' => 'Trà Sữa Trân Châu', 'price' => 62000, 'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
            'demo-cold-brew-arctic' => ['name' => 'Cà Phê Ủ Lạnh', 'price' => 52000, 'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85'],
            'demo-tropical-frost' => ['name' => 'Trà Trái Cây Nhiệt Đới', 'price' => 59000, 'image' => 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=700&q=85'],
        ];
    }

    /**
     * Display cart page
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $suggestions = Product::query()
            ->where('status', true)
            ->with('category')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('client.cart.index', compact('cart', 'suggestions'));
    }

    private function cartPayload(string $message): array
    {
        $cart = session()->get('cart', []);
        $total = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $quantityTotal = collect($cart)->sum(fn ($item) => $item['quantity']);

        return [
            'success' => true,
            'message' => $message,
            'count' => count($cart),
            'quantity_count' => $quantityTotal,
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.') . 'đ',
            'items' => collect($cart)->mapWithKeys(function ($item, $id) {
                $subtotal = $item['price'] * $item['quantity'];

                return [$id => [
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'subtotal_formatted' => number_format($subtotal, 0, ',', '.') . 'đ',
                ]];
            })->all(),
        ];
    }

    /**
     * Add product to cart
     */
    public function add(Request $request, $id)
    {
        $demoProducts = $this->demoProducts();
        $product = isset($demoProducts[$id])
            ? (object) $demoProducts[$id]
            : Product::findOrFail($id);
        
        $cart = session()->get('cart', []);
        
        // If product already in cart, increase quantity
        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            // Add new product to cart
            $image = $product instanceof Product
                ? $product->image_url
                : ($product->image ?? \App\Support\ProductImage::forCategory(null, crc32((string) $id)));

            $cart[$id] = [
                'name' => $product->name,
                'price' => $product->price,
                'image' => $image,
                'sku' => $product instanceof Product ? $product->sku : null,
                'category' => $product instanceof Product ? $product->category?->name : null,
                'quantity' => 1,
            ];
        }
        
        session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã thêm sản phẩm vào giỏ hàng!'));
        }
        
        return redirect()->back();
    }

    /**
     * Update cart quantity
     */
    public function update(Request $request, $id)
    {
        $cart = session()->get('cart', []);
        
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = max(1, min(99, (int) $request->input('quantity', 1)));
            session()->put('cart', $cart);
        }

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã cập nhật giỏ hàng!'));
        }
        
        return redirect()->back();
    }

    /**
     * Remove product from cart
     */
    public function remove(Request $request, $id)
    {
        $cart = session()->get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã xóa sản phẩm khỏi giỏ hàng!'));
        }
        
        return redirect()->back();
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        session()->forget('cart');

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã xóa toàn bộ giỏ hàng!'));
        }

        return redirect()->back();
    }
}
