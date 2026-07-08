<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TasteProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickOrderController extends Controller
{
    public function favorites()
    {
        $favorites = Favorite::with('product.category')->where('user_id', auth()->id())->latest()->get();
        return view('client.favorites.index', compact('favorites'));
    }

    public function toggleFavorite(Request $request, Product $product)
    {
        $favorite = Favorite::where(['user_id' => auth()->id(), 'product_id' => $product->id])->first();
        if ($favorite) {
            $favorite->delete();
            if ($request->expectsJson()) return response()->json(['favorited' => false]);
            return back()->with('success', 'Đã bỏ khỏi danh sách yêu thích.');
        }
        Favorite::create(['user_id' => auth()->id(), 'product_id' => $product->id]);
        if ($request->expectsJson()) return response()->json(['favorited' => true]);
        return back()->with('success', 'Đã thêm vào món yêu thích.');
    }

    public function reorderOrder(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['orderItems.product', 'orderItems.productSize.size']);
        abort_if($order->orderItems->isEmpty(), 422, 'Đơn cũ không còn sản phẩm để đặt lại.');

        foreach ($order->orderItems as $item) $this->addOrderItemToCart($item);
        DB::table('reorder_history')->insert(['user_id' => auth()->id(), 'source_order_id' => $order->id, 'type' => 'order', 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('cart.index')->with('success', 'Đã thêm lại toàn bộ đơn cũ vào giỏ hàng. Giá được cập nhật theo hiện tại.');
    }

    public function reorderItem(Order $order, OrderItem $item)
    {
        abort_unless($order->user_id === auth()->id() && $item->order_id === $order->id, 403);
        $item->load(['product', 'productSize.size']);
        abort_unless($item->product && $item->product->status, 422, 'Sản phẩm này hiện không còn bán.');
        $this->addOrderItemToCart($item);
        DB::table('reorder_history')->insert(['user_id' => auth()->id(), 'source_order_id' => $order->id, 'source_order_item_id' => $item->id, 'type' => 'item', 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('cart.index')->with('success', 'Đã thêm lại món vào giỏ hàng.');
    }

    public function saveTaste(Request $request, Product $product)
    {
        $data = $request->validate([
            'size' => ['required', 'in:S,M,L'], 'sugar_level' => ['required', 'integer', 'between:0,100'],
            'ice_level' => ['required', 'integer', 'between:0,100'], 'toppings' => ['nullable', 'json'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $data['toppings'] = json_decode($data['toppings'] ?? '[]', true) ?: [];
        TasteProfile::updateOrCreate(['user_id' => auth()->id(), 'product_id' => $product->id], $data);
        return back()->with('success', 'Đã lưu cấu hình vị giác cho món này.');
    }

    private function addOrderItemToCart(OrderItem $item): void
    {
        if (! $item->product || ! $item->product->status) return;
        $size = strtoupper((string) ($item->productSize?->size?->name ?? 'M'));
        if (! in_array($size, ['S', 'M', 'L'], true)) $size = 'M';
        $toppings = DB::table('order_item_toppings')->join('toppings', 'toppings.id', '=', 'order_item_toppings.topping_id')
            ->where('order_item_id', $item->id)->get(['toppings.name', 'order_item_toppings.price'])->map(fn ($t) => ['name' => $t->name, 'price' => (int) $t->price])->all();
        $sizeExtra = ['S' => 0, 'M' => 5000, 'L' => 10000][$size];
        $toppingTotal = collect($toppings)->sum('price');
        $key = 'reorder-'.$item->id.'-'.uniqid();
        $cart = session()->get('cart', []);
        $cart[$key] = ['product_id' => $item->product_id, 'name' => $item->product->name, 'base_price' => (int) $item->product->price,
            'price' => (int) $item->product->price + $sizeExtra + $toppingTotal, 'size' => $size, 'size_label' => 'Size '.$size,
            'size_extra' => $sizeExtra, 'sugar_level' => $item->sugar_level, 'ice_level' => $item->ice_level, 'toppings' => $toppings,
            'topping_total' => $toppingTotal, 'image' => $item->product->image_url, 'sku' => $item->product->sku,
            'category' => $item->product->category?->name, 'quantity' => max(1, (int) $item->quantity)];
        session()->put('cart', $cart);
    }
}
