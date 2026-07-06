<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use App\Models\GroupOrderItem;
use App\Models\GroupOrderMember;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GroupOrderController extends Controller
{
    public function index()
    {
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'closes_at' => ['required', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'closes_at.required' => 'Vui lòng chọn thời gian chốt đơn.',
            'closes_at.date' => 'Thời gian chốt đơn không hợp lệ.',
            'closes_at.after' => 'Thời gian chốt đơn phải ở tương lai.',
        ]);

        $group = GroupOrder::create($data + [
            'owner_id' => auth()->id(),
            'code' => $this->uniqueCode(),
        ]);

        return redirect()->route('group-orders.show', $group->code)
            ->with('success', 'Đã tạo đơn nhóm. Hãy gửi đường link cho mọi người!');
    }

    public function show(string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->load(['owner', 'members.items.product', 'items']);
        $products = Product::where('status', true)->orderBy('name')->get();
        $toppings = Schema::hasTable('toppings')
            ? DB::table('toppings')->orderBy('name')->get(['id', 'name', 'price'])
            : collect();
        $currentMember = $group->members->firstWhere('user_id', auth()->id());

        return view('client.group-orders.show', compact('group', 'products', 'toppings', 'currentMember'));
    }

    public function join(Request $request, string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
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
            abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng hoặc hết hạn.');
            $member = $this->currentMember($group);
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            abort_unless((bool) $product->status, 422, 'Sản phẩm hiện không còn bán.');

            $alreadyReserved = (int) $group->items()->where('product_id', $product->id)->sum('quantity');
            abort_if($alreadyReserved + $data['quantity'] > (int) $product->stock, 422, 'Sản phẩm không đủ số lượng tồn kho.');

            $selectedIds = collect($data['toppings'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $allowedIds = DB::table('product_toppings')->where('product_id', $product->id)->pluck('topping_id');
            if ($allowedIds->isNotEmpty() && $selectedIds->diff($allowedIds)->isNotEmpty()) {
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
            $member = $this->currentMember($group);
            $lockedItem = GroupOrderItem::lockForUpdate()->findOrFail($item->id);
            abort_unless($lockedItem->group_order_id === $group->id && $lockedItem->group_order_member_id === $member->id, 403);
            abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng.');
            $lockedItem->delete();
        });

        return back()->with('success', 'Đã xóa món khỏi đơn nhóm.');
    }

    public function close(string $code)
    {
        abort_if(session()->has('checkout_group_order_id'), 422, 'Bạn đang có một đơn nhóm chờ thanh toán. Hãy hoàn tất hoặc hủy đơn đó trước.');
        $group = DB::transaction(function () use ($code) {
            $group = GroupOrder::where('code', $code)->lockForUpdate()->firstOrFail();
            abort_unless($group->owner_id === auth()->id(), 403);
            abort_if($group->status !== 'open', 422, 'Đơn nhóm đã được chốt hoặc hủy trước đó.');
            $group->load(['items.product.category', 'items.member']);
            abort_if($group->items->isEmpty(), 422, 'Chưa có món nào trong đơn nhóm.');

            foreach ($group->items->groupBy('product_id') as $productItems) {
                $product = Product::whereKey($productItems->first()->product_id)->lockForUpdate()->first();
                abort_unless($product && $product->status, 422, 'Có sản phẩm đã ngừng bán. Vui lòng xóa khỏi đơn.');
                abort_if($productItems->sum('quantity') > (int) $product->stock, 422, "{$product->name} không đủ tồn kho.");
                foreach ($productItems as $item) {
                    $item->update(['unit_price' => $this->currentPrice($product, $item->size, $item->toppings ?? [])]);
                }
            }

            $group->update(['status' => 'closed', 'locked_at' => now()]);
            $group->refresh()->load(['items.product.category', 'items.member']);
            return $group;
        });

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
        abort_if(session()->has('checkout_group_order_id'), 422, 'Bạn đang có một đơn nhóm khác chờ thanh toán.');
        $group = GroupOrder::with(['items.product.category', 'items.member'])->where('code', $code)->firstOrFail();
        abort_unless($group->owner_id === auth()->id() && $group->status === 'closed' && ! $group->order_id, 403);
        abort_if($group->items->isEmpty(), 422, 'Đơn nhóm không có món để thanh toán.');
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
