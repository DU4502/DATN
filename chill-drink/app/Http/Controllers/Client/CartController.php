<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    private function buildStaticSizeOptions(int $basePrice): array
    {
        $options = [
            'S' => [
                'token' => 'S',
                'label' => 'Size S',
                'price' => max(0, $basePrice - 5000),
                'size_id' => null,
                'size_name' => 'S',
            ],
            'M' => [
                'token' => 'M',
                'label' => 'Size M',
                'price' => $basePrice,
                'size_id' => null,
                'size_name' => 'M',
            ],
            'L' => [
                'token' => 'L',
                'label' => 'Size L',
                'price' => $basePrice + 5000,
                'size_id' => null,
                'size_name' => 'L',
            ],
        ];

        $base = (int) ($options['M']['price'] ?? $basePrice);

        foreach ($options as $key => $option) {
            $options[$key]['extra'] = (int) $option['price'] - $base;
        }

        return $options;
    }

    private function buildProductSizeOptions(Product $product): array
    {
        if (! Schema::hasTable('sizes') || ! Schema::hasTable('product_sizes')) {
            return $this->buildStaticSizeOptions((int) ($product->price ?? 0));
        }

        $product->loadMissing('sizes');

        $options = [];

        foreach ($product->sizes as $size) {
            $sizeName = trim((string) $size->name);
            $label = str_starts_with(mb_strtolower($sizeName), 'size') ? $sizeName : 'Size '.$sizeName;
            $token = 'db:'.$size->id;
            $price = max(0, (int) ($size->pivot->price ?? $product->price ?? 0));

            $options[$token] = [
                'token' => $token,
                'label' => $label,
                'price' => $price,
                'size_id' => (int) $size->id,
                'size_name' => $sizeName,
            ];
        }

        if ($options === []) {
            return $this->buildStaticSizeOptions((int) ($product->price ?? 0));
        }

        $base = min(array_map(fn ($option) => (int) $option['price'], $options));

        foreach ($options as $key => $option) {
            $options[$key]['extra'] = (int) $option['price'] - $base;
        }

        return $options;
    }

    private function demoProducts(): array
    {
        return [
            'demo-wild-berry-bliss' => ['name' => 'Sinh Tố Dâu Rừng', 'price' => 65000, 'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85'],
            'demo-sinh-to-dau' => ['name' => 'Sinh Tố Dâu', 'price' => 45000, 'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85'],
            'demo-matcha-latte-da' => ['name' => 'Matcha Latte Đá', 'price' => 57000, 'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85'],
            'demo-citrus-sunset' => ['name' => 'Nước Ép Cam Chanh Dây', 'price' => 49000, 'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85'],
            'demo-tra-sua-tran-chau-demo' => ['name' => 'Trà Sữa Trân Châu', 'price' => 62000, 'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
            'demo-tra-sua-tran-chau-duong-den' => ['name' => 'Trà Sữa Trân Châu Đường Đen', 'price' => 75450, 'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
            'demo-ca-phe-sua-da' => ['name' => 'Cà Phê Sữa Đá', 'price' => 24971, 'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85'],
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
        $basePrice = (int) ($product->price ?? 0);
        $sizes = $product instanceof Product
            ? $this->buildProductSizeOptions($product)
            : $this->buildStaticSizeOptions($basePrice);

        $requestedSizeToken = (string) $request->input('size', 'M');
        $size = $sizes[$requestedSizeToken]
            ?? $sizes['M']
            ?? reset($sizes)
            ?? ['token' => 'M', 'label' => 'Size M', 'price' => $basePrice, 'extra' => 0, 'size_id' => null, 'size_name' => 'M'];
        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));
        $sugarLevel = max(0, min(150, (int) $request->input('sugar_level', 30)));
        $iceLevel = max(0, min(150, (int) $request->input('ice_level', 100)));
        $sugarLabel = trim((string) $request->input('sugar_label', $sugarLevel.'%')) ?: ($sugarLevel.'%');
        $iceLabel = trim((string) $request->input('ice_label', $iceLevel.'%')) ?: ($iceLevel.'%');
        $cartKey = implode(':', [$id, $size['token'], $sugarLevel, $iceLevel]);
        
        // If the same product and size already exist, increase quantity.
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = min(99, $cart[$cartKey]['quantity'] + $quantity);
        } else {
            // Add new product to cart
            $image = $product instanceof Product
                ? $product->image_url
                : ($product->image ?? \App\Support\ProductImage::forCategory(null, crc32((string) $id)));

            $cart[$cartKey] = [
                'product_id' => $id,
                'name' => $product->name,
                'base_price' => $basePrice,
                'price' => (int) $size['price'],
                'size' => (string) $size['token'],
                'size_label' => $size['label'],
                'size_extra' => (int) ($size['extra'] ?? 0),
                'size_id' => $size['size_id'],
                'size_name' => $size['size_name'],
                'sugar_level' => $sugarLevel,
                'sugar_label' => $sugarLabel,
                'ice_level' => $iceLevel,
                'ice_label' => $iceLabel,
                'image' => $image,
                'sku' => $product instanceof Product ? ($product->sku ?? null) : null,
                'category' => $product instanceof Product ? $product->category?->name : null,
                'quantity' => $quantity,
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
