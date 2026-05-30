<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Support\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    private const MAX_QUANTITY = 99;

    private function sizeOptions(): array
    {
        return [
            'S' => ['label' => 'Size S', 'extra' => 0],
            'M' => ['label' => 'Size M', 'extra' => 5000],
            'L' => ['label' => 'Size L', 'extra' => 10000],
            'XL' => ['label' => 'Size XL', 'extra' => 15000],
            'XXL' => ['label' => 'Size XXL', 'extra' => 20000],
        ];
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
        $total = collect($cart)->sum(fn ($item) => (int) $item['price'] * (int) $item['quantity']);
        $quantityTotal = collect($cart)->sum(fn ($item) => (int) $item['quantity']);

        return [
            'success' => true,
            'message' => $message,
            'count' => count($cart),
            'quantity_count' => $quantityTotal,
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.') . 'đ',
            'items' => collect($cart)->mapWithKeys(function ($item, $id) {
                $subtotal = (int) $item['price'] * (int) $item['quantity'];

                return [$id => [
                    'quantity' => (int) $item['quantity'],
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
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
        ]);

        $demoProducts = $this->demoProducts();
        $product = isset($demoProducts[$id])
            ? (object) $demoProducts[$id]
            : Product::query()
                ->with('category')
                ->whereKey($id)
                ->where('status', true)
                ->firstOrFail();
        
        $cart = session()->get('cart', []);
        $sizes = $this->sizeOptions();
        $sizeCode = strtoupper((string) $request->input('size', 'M'));
        $size = $sizes[$sizeCode] ?? $sizes['M'];
        $sizeCode = array_key_exists($sizeCode, $sizes) ? $sizeCode : 'M';
        $cartKey = $id . ':' . $sizeCode;
        $productSize = $product instanceof Product ? $this->productSizeFor($product, $sizeCode) : null;
        $basePrice = (int) ($product->price ?? 0);
        $unitPrice = $productSize
            ? (int) $productSize->price
            : $basePrice + $size['extra'];
        $displaySizeExtra = $productSize
            ? max(0, $unitPrice - $basePrice)
            : $size['extra'];
        $quantity = max(1, min(self::MAX_QUANTITY, (int) $request->input('quantity', 1)));
        $maxAvailable = $product instanceof Product
            ? max(0, (int) ($product->stock ?? self::MAX_QUANTITY))
            : self::MAX_QUANTITY;

        if ($maxAvailable < 1) {
            return $this->cartError($request, 'Sản phẩm này hiện đã hết hàng.');
        }
        
        // If the same product and size already exist, increase quantity.
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = min(
                self::MAX_QUANTITY,
                $maxAvailable,
                (int) $cart[$cartKey]['quantity'] + $quantity
            );
        } else {
            // Add new product to cart
            $image = $product instanceof Product
                ? $product->image_url
                : ($product->image ?? ProductImage::forCategory(null, crc32((string) $id)));

            $cart[$cartKey] = [
                'product_id' => $id,
                'product_size_id' => $productSize?->id,
                'name' => $product->name,
                'base_price' => $basePrice,
                'price' => $unitPrice,
                'size' => $sizeCode,
                'size_label' => $size['label'],
                'size_extra' => $displaySizeExtra,
                'image' => $image,
                'sku' => $product instanceof Product ? ($product->sku ?? null) : null,
                'category' => $product instanceof Product ? $product->category?->name : null,
                'quantity' => min($quantity, $maxAvailable),
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
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
        ]);

        $cart = session()->get('cart', []);
        
        if (isset($cart[$id])) {
            $maxAvailable = self::MAX_QUANTITY;

            if (is_numeric($cart[$id]['product_id'] ?? null)) {
                $stock = Product::query()
                    ->whereKey($cart[$id]['product_id'])
                    ->value('stock');

                if ($stock !== null) {
                    $maxAvailable = max(1, min(self::MAX_QUANTITY, (int) $stock));
                }
            }

            $cart[$id]['quantity'] = max(1, min($maxAvailable, (int) $request->input('quantity', 1)));
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

    private function productSizeFor(Product $product, string $sizeCode): ?ProductSize
    {
        if (! Schema::hasTable('product_sizes') || ! Schema::hasTable('sizes')) {
            return null;
        }

        return ProductSize::query()
            ->where('product_id', $product->id)
            ->whereHas('size', fn ($query) => $query->whereIn('name', [$sizeCode, "Size {$sizeCode}"]))
            ->first();
    }

    private function cartError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()->back()->with('error', $message);
    }
}
