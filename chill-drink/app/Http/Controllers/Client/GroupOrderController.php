<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use App\Models\GroupOrderItem;
use App\Models\GroupOrderMember;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GroupOrderController extends Controller
{
    public function index()
    {
        GroupOrder::closeExpiredOrders();

        $groups = GroupOrder::withCount(['members', 'items'])
            ->where('owner_id', auth()->id())->latest()->get();

        return view('client.group-orders.index', compact('groups'));
    }

    public function create()
    {
        return view('client.group-orders.create');
    }

    public function store(Request $request)
    {
        $minimumClosingTime = now()->addMinutes(5)->format('Y-m-d H:i:s');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:'.$minimumClosingTime, 'before_or_equal:+7 days'],
        ], [
            'closes_at.after_or_equal' => 'Thời gian kết thúc phải cách thời điểm hiện tại ít nhất 5 phút.',
            'closes_at.before_or_equal' => 'Thời gian kết thúc không được vượt quá 7 ngày.',
        ]);

        $closesAt = isset($data['closes_at'])
            ? \Illuminate\Support\Carbon::parse($data['closes_at'])
            : now()->addMinutes(GroupOrder::ORDER_WINDOW_MINUTES);

        // Lớp bảo vệ cuối: không để bất kỳ giá trị cũ/sai nào tạo phòng chỉ tồn tại vài giây.
        if ($closesAt->lessThan(now()->addMinutes(5))) {
            $closesAt = now()->addMinutes(GroupOrder::ORDER_WINDOW_MINUTES);
        }
        unset($data['closes_at']);

        $group = DB::transaction(function () use ($data, $closesAt) {
            GroupOrder::query()
                ->where('owner_id', auth()->id())
                ->where('status', 'open')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return GroupOrder::create($data + [
                'owner_id' => auth()->id(),
                'code' => $this->uniqueCode(),
                'closes_at' => $closesAt,
            ]);
        });

        return redirect()->route('group-orders.show', $group->code)
            ->with('success', 'Đã tạo đơn nhóm. Hãy gửi đường link cho mọi người!');
    }

    public function show(string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->closeIfExpired();
        if ($group->owner_id === auth()->id() && $group->isOpen()) {
            $group->update(['owner_last_seen_at' => now()]);
        }
        $group->load(['owner', 'members.items.product', 'items']);
        $products = Product::with('category')->where('status', true)->orderBy('name')->get();
        $toppings = Schema::hasTable('toppings')
            ? DB::table('toppings')->where('status', 1)->orderBy('name')->get(['id', 'name', 'price'])
            : collect();
        $productToppingMap = $products->mapWithKeys(fn (Product $product) => [
            $product->id => $this->toppingIdsForProduct($product, $toppings),
        ]);
        $currentMember = $group->members->firstWhere('user_id', auth()->id());

        return view('client.group-orders.show', compact('group', 'products', 'toppings', 'productToppingMap', 'currentMember'));
    }

    public function presence(string $code): JsonResponse
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->closeIfExpired();

        if ($group->owner_id === auth()->id() && $group->isOpen()) {
            $group->update(['owner_last_seen_at' => now()]);
        }

        return response()->json([
            'owner_present' => $group->fresh()->ownerIsPresent(),
            'is_open' => $group->isOpen(),
            'closes_at' => $group->closes_at->toIso8601String(),
        ]);
    }

    public function join(Request $request, string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->closeIfExpired();
        abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng hoặc hết hạn.');
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $member = DB::transaction(function () use ($group, $data) {
            $lockedGroup = GroupOrder::query()->lockForUpdate()->findOrFail($group->id);
            $existingMember = $lockedGroup->members()->where('user_id', auth()->id())->first();

            if ($existingMember) {
                $existingMember->update(['name' => $data['name']]);
                return $existingMember;
            }

            abort_if(
                $lockedGroup->members()->count() >= GroupOrder::MAX_MEMBERS,
                422,
                'Đơn nhóm đã đủ '.GroupOrder::MAX_MEMBERS.' thành viên.'
            );

            return $lockedGroup->members()->create([
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'member_token' => Str::random(48),
            ]);
        });

        session(["group_member_{$group->id}" => $member->member_token]);

        return back()->with('success', "Chào {$member->name}, bạn có thể chọn món rồi nhé!");
    }

    public function addItem(Request $request, string $code)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'size' => ['required', 'in:S,M,L'],
            'sugar_level' => ['required', 'integer', 'between:0,100'],
            'ice_level' => ['required', 'integer', 'between:0,100'],
            'quantity' => ['required', 'integer', 'between:1,20'],
            'note' => ['nullable', 'string', 'max:500'],
            'toppings' => ['nullable', 'array'],
            'toppings.*' => ['integer', 'exists:toppings,id'],
        ]);

        DB::transaction(function () use ($code, $data) {
            $group = GroupOrder::where('code', $code)->lockForUpdate()->firstOrFail();
            $group->closeIfExpired();
            abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng hoặc hết hạn.');
            $member = $this->currentMember($group);
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            abort_unless((bool) $product->status, 422, 'Sản phẩm hiện không còn bán.');

            $alreadyReserved = (int) $group->items()->where('product_id', $product->id)->sum('quantity');
            abort_if($alreadyReserved + $data['quantity'] > (int) $product->stock, 422, 'Sản phẩm không đủ số lượng tồn kho.');

            $selectedIds = collect($data['toppings'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $availableToppings = DB::table('toppings')->where('status', 1)->get(['id', 'name', 'price']);
            $allowedIds = $this->toppingIdsForProduct($product->loadMissing('category'), $availableToppings);
            if ($selectedIds->diff($allowedIds)->isNotEmpty()) {
                abort(422, 'Topping đã chọn không áp dụng cho sản phẩm này.');
            }
            $selectedToppings = $selectedIds->isEmpty() ? collect() : DB::table('toppings')->whereIn('id', $selectedIds)->get(['name', 'price']);
            abort_if($selectedToppings->count() !== $selectedIds->count(), 422, 'Topping không hợp lệ.');
            $toppingPayload = $selectedToppings->map(fn ($topping) => ['name' => $topping->name, 'price' => (int) $topping->price])->values()->all();
            unset($data['toppings']);

            GroupOrderItem::create($data + [
                'group_order_id' => $group->id,
                'group_order_member_id' => $member->id,
                'toppings' => $toppingPayload,
                'unit_price' => $this->currentPrice($product, $data['size'], $toppingPayload),
            ]);
        });

        return back()->with('success', 'Đã thêm món của bạn vào đơn nhóm.');
    }

    public function removeItem(string $code, GroupOrderItem $item)
    {
        DB::transaction(function () use ($code, $item) {
            $group = GroupOrder::where('code', $code)->lockForUpdate()->firstOrFail();
            $group->closeIfExpired();
            $member = $this->currentMember($group);
            $lockedItem = GroupOrderItem::lockForUpdate()->findOrFail($item->id);
            abort_unless($lockedItem->group_order_id === $group->id && $lockedItem->group_order_member_id === $member->id, 403);
            abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng.');
            $lockedItem->delete();
        });

        return back()->with('success', 'Đã xóa món khỏi đơn nhóm.');
    }

    public function incrementItem(string $code, GroupOrderItem $item)
    {
        DB::transaction(function () use ($code, $item) {
            $group = GroupOrder::where('code', $code)->lockForUpdate()->firstOrFail();
            $group->closeIfExpired();
            abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng hoặc hết hạn.');

            $member = $this->currentMember($group);
            $lockedItem = GroupOrderItem::lockForUpdate()->findOrFail($item->id);
            abort_unless(
                $lockedItem->group_order_id === $group->id && $lockedItem->group_order_member_id === $member->id,
                403
            );
            abort_if($lockedItem->quantity >= 20, 422, 'Mỗi món chỉ được chọn tối đa 20 phần.');

            $product = Product::whereKey($lockedItem->product_id)->lockForUpdate()->firstOrFail();
            $reserved = (int) $group->items()->where('product_id', $product->id)->sum('quantity');
            abort_if($reserved + 1 > (int) $product->stock, 422, 'Sản phẩm không đủ số lượng tồn kho.');

            $lockedItem->increment('quantity');
        });

        return back()->with('success', 'Đã thêm 1 phần của món này.');
    }

    public function close(string $code)
    {
        if (session()->has('checkout_group_order_id')) {
            return back()->with('error', 'Bạn đang có một đơn nhóm chờ thanh toán. Hãy hoàn tất hoặc hủy đơn đó trước.');
        }

        $group = GroupOrder::where('code', $code)->firstOrFail();
        if ($group->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($group->status !== 'open') {
            return back()->with('error', 'Đơn nhóm đã được chốt hoặc hủy trước đó.');
        }

        if ($group->items()->count() === 0) {
            return back()->with('error', 'Chưa có món nào trong đơn nhóm.');
        }

        try {
            $group = DB::transaction(function () use ($group) {
                $lockedGroup = GroupOrder::lockForUpdate()->findOrFail($group->id);
                $lockedGroup->load(['items.product.category', 'items.member']);

                if ($lockedGroup->items->isEmpty()) {
                    throw new \Exception('Chưa có món nào trong đơn nhóm.');
                }

                foreach ($lockedGroup->items->groupBy('product_id') as $productItems) {
                    $product = Product::whereKey($productItems->first()->product_id)->lockForUpdate()->first();
                    if (!$product || !$product->status) {
                        throw new \Exception('Có sản phẩm đã ngừng bán. Vui lòng xóa khỏi đơn.');
                    }
                    if ($productItems->sum('quantity') > (int) $product->stock) {
                        throw new \Exception("Sản phẩm {$product->name} không đủ tồn kho.");
                    }
                    foreach ($productItems as $item) {
                        $item->update(['unit_price' => $this->currentPrice($product, $item->size, $item->toppings ?? [])]);
                    }
                }

                $lockedGroup->update(['status' => 'closed', 'locked_at' => now()]);
                $lockedGroup->refresh()->load(['items.product.category', 'items.member']);
                return $lockedGroup;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activateGroupCart($group);

        return redirect()->route('cart.index')->with('success', 'Đã gom toàn bộ món vào giỏ hàng chung.');
    }

    private function currentMember(GroupOrder $group): GroupOrderMember
    {
        $member = $group->members()->where('user_id', auth()->id())->first();
        abort_unless($member, 403, 'Bạn cần tham gia đơn nhóm trước.');
        return $member;
    }

    public function cancel(string $code)
    {
        DB::transaction(function () use ($code) {
            $group = GroupOrder::where('code', $code)->lockForUpdate()->firstOrFail();
            abort_unless($group->owner_id === auth()->id(), 403);
            abort_if($group->order_id || ! in_array($group->status, ['open', 'closed'], true), 422, 'Đơn nhóm này không thể hủy.');
            $group->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        });
        $this->restorePersonalCart();
        return redirect()->route('group-orders.index')->with('success', 'Đã hủy đơn nhóm.');
    }

    public function resume(string $code)
    {
        if (session()->has('checkout_group_order_id')) {
            return back()->with('error', 'Bạn đang có một đơn nhóm khác chờ thanh toán.');
        }
        $group = GroupOrder::with(['items.product.category', 'items.member'])->where('code', $code)->firstOrFail();
        abort_unless($group->owner_id === auth()->id() && $group->status === 'closed' && ! $group->order_id, 403);
        if ($group->items->isEmpty()) {
            return back()->with('error', 'Đơn nhóm không có món để thanh toán.');
        }
        $this->activateGroupCart($group);
        return redirect()->route('cart.index')->with('success', 'Đã khôi phục giỏ hàng đơn nhóm.');
    }

    private function restorePersonalCart(): void
    {
        if (session()->has('personal_cart_backup')) session()->put('cart', session()->pull('personal_cart_backup'));
        session()->forget(['group_cart_keys', 'checkout_group_order_id']);
    }

    private function currentPrice(Product $product, string $size, array $toppings): int
    {
        $sizeExtra = ['S' => 0, 'M' => 5000, 'L' => 10000][$size] ?? 0;
        return max(0, (int) $product->price + $sizeExtra + (int) collect($toppings)->sum('price'));
    }

    private function toppingIdsForProduct(Product $product, $toppings)
    {
        $pivotIds = DB::table('product_toppings')->where('product_id', $product->id)->pluck('topping_id');
        if ($pivotIds->isNotEmpty()) {
            return $pivotIds->map(fn ($id) => (int) $id)->values();
        }

        $text = Str::lower(($product->name ?? '').' '.($product->category?->name ?? ''));
        $allowedNames = match (true) {
            str_contains($text, 'matcha') => ['Trân châu đen', 'Kem cheese', 'Thạch matcha'],
            str_contains($text, 'trà sữa') => ['Trân châu đen', 'Pudding trứng', 'Thạch phô mai'],
            str_contains($text, 'cà phê'), str_contains($text, 'bạc xỉu') => ['Kem cheese'],
            str_contains($text, 'soda'), str_contains($text, 'nước ép'), str_contains($text, 'trà trái cây') => ['Trân châu trắng', 'Thạch nha đam'],
            str_contains($text, 'sinh tố') => ['Trân châu trắng', 'Thạch nha đam'],
            default => ['Trân châu đen', 'Trân châu trắng', 'Kem cheese'],
        };

        return $toppings->whereIn('name', $allowedNames)->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    private function activateGroupCart(GroupOrder $group): void
    {
        $cart = [];
        foreach ($group->items as $item) {
            $key = 'group-'.$group->id.'-'.$item->id;
            $cart[$key] = [
                'product_id' => $item->product_id, 'name' => $item->product->name,
                'base_price' => (int) $item->product->price, 'price' => $item->unit_price,
                'size' => $item->size, 'size_label' => 'Size '.$item->size,
                'size_extra' => max(0, $item->unit_price - (int) $item->product->price - (int) collect($item->toppings ?? [])->sum('price')),
                'sugar_level' => $item->sugar_level, 'ice_level' => $item->ice_level,
                'toppings' => $item->toppings ?? [], 'topping_total' => collect($item->toppings ?? [])->sum('price'),
                'image' => $item->product->image_url, 'sku' => $item->product->sku,
                'category' => $item->product->category?->name, 'quantity' => $item->quantity,
                'group_member_name' => $item->member->name ?? null, 'note' => $item->note,
            ];
        }
        session()->put('personal_cart_backup', session()->get('cart', []));
        session()->put('cart', $cart);
        session()->put('group_cart_keys', array_keys($cart));
        session()->put('checkout_group_order_id', $group->id);
    }

    private function uniqueCode(): string
    {
        do { $code = strtoupper(Str::random(8)); } while (GroupOrder::where('code', $code)->exists());
        return $code;
    }
}
