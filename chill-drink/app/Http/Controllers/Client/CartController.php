<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Favorite;
use App\Models\GroupOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function sizeOptions(): array
    {
        return [
            'S' => ['label' => 'Size S', 'extra' => 0],
            'M' => ['label' => 'Size M', 'extra' => 5000],
            'L' => ['label' => 'Size L', 'extra' => 10000],
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
        $cart = $this->refreshCartItems(session()->get('cart', []));
        session()->put('cart', $cart);

        $suggestions = Product::query()
            ->where('status', true)
            ->with('category')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $favoriteProductIds = auth()->check()
            ? Favorite::where('user_id', auth()->id())->pluck('product_id')
            : collect();

        return view('client.cart.index', compact('cart', 'suggestions', 'favoriteProductIds'));
    }

    private function cartPayload(string $message): array
    {
        $cart = $this->refreshCartItems(session()->get('cart', []));
        session()->put('cart', $cart);
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

    private function refreshCartItems(array $cart): array
    {
        $productIds = collect($cart)
            ->pluck('product_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $cart;
        }

        $products = Product::with(['category', 'sizes'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($cart as $key => $item) {
            $productId = $item['product_id'] ?? null;

            if (! is_numeric($productId) || ! $products->has((int) $productId)) {
                continue;
            }

            $product = $products->get((int) $productId);
            $sizeCode = strtoupper((string) ($item['size'] ?? 'M'));
            $sizeObj = $product->sizes->first(fn ($s) => strtoupper(trim($s->name)) === $sizeCode);
            $defaultExtra = $sizeCode === 'S' ? 0 : ($sizeCode === 'M' ? 5000 : 10000);
            $sizeExtra = ($sizeObj && isset($sizeObj->pivot->price))
                ? (int) $sizeObj->pivot->price
                : (int) ($item['size_extra'] ?? $defaultExtra);

            $cart[$key]['name'] = $product->name;
            $cart[$key]['image'] = $product->image_url;
            $cart[$key]['sku'] = $product->sku ?? null;
            $cart[$key]['category'] = $product->category?->name;
            $cart[$key]['base_price'] = (int) $product->price;
            $cart[$key]['size_extra'] = $sizeExtra;
            $cart[$key]['price'] = (int) $product->price + $sizeExtra + (int) ($item['topping_total'] ?? 0);
        }

        return $cart;
    }

    /**
     * Add product to cart
     */
    public function add(Request $request, $id)
    {
        if ($activeGroup = $this->activeOpenGroupForCurrentUser()) {
            $message = 'Bạn đang tham gia phòng "'.$activeGroup->name.'". Hãy quay lại để chọn món, hoặc rời/hủy phòng trước khi mua riêng.';
            $redirectUrl = route('group-orders.show', $activeGroup->code);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'redirect_url' => $redirectUrl,
                    'redirect_label' => 'Về phòng nhóm',
                ], 409);
            }

            return redirect($redirectUrl)->with('error', $message);
        }

        $demoProducts = $this->demoProducts();
        $product = isset($demoProducts[$id])
            ? $this->resolveOrCreatePayableProduct($demoProducts[$id], $id)
            : Product::findOrFail($id);
        
        $cart = session()->get('cart', []);
        $sizes = $this->sizeOptions();
        $sizeCode = strtoupper((string) $request->input('size', 'M'));
        if (! in_array($sizeCode, ['S', 'M', 'L'], true)) {
            $sizeCode = 'M';
        }
        $size = $sizes[$sizeCode] ?? $sizes['M'];

        $sizeExtra = (int) $size['extra'];
        if ($product instanceof Product) {
            $product->loadMissing('sizes');
            $sizeObj = $product->sizes->first(fn ($s) => strtoupper(trim($s->name)) === $sizeCode);
            if ($sizeObj && isset($sizeObj->pivot->price)) {
                $sizeExtra = (int) $sizeObj->pivot->price;
            }
        }

        $sugarLevel = max(0, min(100, (int) $request->input('sugar_level', 100)));
        $iceLevel = max(0, min(100, (int) $request->input('ice_level', 100)));
        $toppings = collect(json_decode((string) $request->input('toppings', '[]'), true) ?: [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['name']))
            ->map(fn ($item) => [
                'name' => (string) $item['name'],
                'price' => max(0, (int) ($item['price'] ?? 0)),
            ])
            ->values()
            ->all();
        $toppingTotal = collect($toppings)->sum('price');
        $toppingKey = collect($toppings)->pluck('name')->implode(',');
        $cartKey = $id . ':' . $sizeCode . ':' . $sugarLevel . ':' . $iceLevel . ':' . md5($toppingKey);
        $basePrice = (int) ($product->price ?? 0);
        $productId = $product instanceof Product ? (int) $product->id : $id;
        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));
        $itemNote = mb_substr(trim((string) $request->input('note')), 0, 500);
        
        // If the same product and size already exist, increase quantity.
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = min(99, $cart[$cartKey]['quantity'] + $quantity);
        } else {
            // Add new product to cart
            $image = $product instanceof Product
                ? $product->image_url
                : ($product->image ?? \App\Support\ProductImage::forCategory(null, crc32((string) $id)));

            $cart[$cartKey] = [
                'product_id' => $productId,
                'name' => $product->name,
                'base_price' => $basePrice,
                'price' => $basePrice + $sizeExtra + $toppingTotal,
                'size' => $sizeCode,
                'size_label' => 'Size ' . $sizeCode,
                'size_extra' => $sizeExtra,
                'sugar_level' => $sugarLevel,
                'ice_level' => $iceLevel,
                'toppings' => $toppings,
                'topping_total' => $toppingTotal,
                'image' => $image,
                'sku' => $product instanceof Product ? ($product->sku ?? null) : null,
                'category' => $product instanceof Product ? $product->category?->name : null,
                'quantity' => $quantity,
                'note' => $itemNote !== '' ? $itemNote : null,
            ];
        }
        
        session()->put('cart', $cart);
        if (session()->has('checkout_group_order_id')) {
            session()->put('group_cart_keys', collect(session('group_cart_keys', []))->push($cartKey)->unique()->values()->all());
            session()->put('checkout_cart_keys', collect(session('checkout_cart_keys', []))->push($cartKey)->unique()->values()->all());
        }

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã thêm sản phẩm vào giỏ hàng!'));
        }

        if ($request->boolean('buy_now')) {
            $route = auth()->check() ? 'checkout.index' : 'checkout.guest.index';
            return redirect()->route($route, ['items' => [$cartKey]]);
        }
        
        return redirect()->back();
    }

    private function activeOpenGroupForCurrentUser(): ?GroupOrder
    {
        if (! auth()->check() || session()->has('checkout_group_order_id')) {
            return null;
        }

        return GroupOrder::query()
            ->where(function ($query) {
                $query->where('owner_id', auth()->id())
                    ->orWhereHas('members', fn ($members) => $members->where('user_id', auth()->id()));
            })
            ->where('status', 'open')
            ->where('closes_at', '>', now())
            ->latest('id')
            ->first();
    }

    private function resolveOrCreatePayableProduct(array $demoProduct, string $demoId): Product
    {
        $name = trim((string) ($demoProduct['name'] ?? ''));
        $slug = Str::slug($name !== '' ? $name : $demoId);
        $price = max(0, (int) ($demoProduct['price'] ?? 0));

        $product = Product::query()
            ->where(function ($query) use ($name, $slug) {
                $query->where('name', $name);

                if ($slug !== '') {
                    $query->orWhere('slug', $slug);
                }
            })
            ->first();

        if ($product) {
            return $product;
        }

        $categoryName = trim((string) ($demoProduct['category'] ?? ''));
        $category = null;

        if (Schema::hasTable('categories') && $categoryName !== '') {
            $category = Category::query()->firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName), 'status' => true]
            );
        }

        return Product::create([
            'category_id' => $category?->id,
            'name' => $name !== '' ? $name : 'Sản phẩm demo',
            'slug' => $slug,
            'price' => $price,
            'stock' => 100,
            'status' => true,
            'description' => trim((string) ($demoProduct['description'] ?? '')) !== ''
                ? $demoProduct['description']
                : 'Sản phẩm được tạo tự động để hỗ trợ thanh toán.',
            'image' => $demoProduct['image'] ?? null,
        ]);
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
            session()->put('group_cart_keys', collect(session('group_cart_keys', []))->reject(fn ($key) => (string) $key === (string) $id)->values()->all());
            session()->put('checkout_cart_keys', collect(session('checkout_cart_keys', []))->reject(fn ($key) => (string) $key === (string) $id)->values()->all());
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
        abort_if(session()->has('checkout_group_order_id'), 422, 'Không thể xóa giỏ đơn nhóm. Hãy hủy đơn nhóm nếu không muốn tiếp tục.');
        session()->forget('cart');

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload('Đã xóa toàn bộ giỏ hàng!'));
        }

        return redirect()->back();
    }
}
