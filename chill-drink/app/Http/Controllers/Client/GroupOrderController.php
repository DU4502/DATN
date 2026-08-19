<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GroupOrder;
use App\Events\GroupOrderGroupMessageSent;
use App\Models\GroupOrderItem;
use App\Models\GroupOrderMember;
use App\Models\GroupOrderMessage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $branches = Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        $selectedBranchId = old('branch_id');

        return view('client.group-orders.create', compact('branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $minimumClosingTime = now()->addMinutes(5)->format('Y-m-d H:i:s');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
            'note' => ['nullable', 'string', 'max:500'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:'.$minimumClosingTime, 'before_or_equal:+7 days'],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh phục vụ đơn nhóm.',
            'branch_id.exists' => 'Chi nhánh đã chọn không còn hoạt động.',
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

            $group = GroupOrder::create($data + [
                'owner_id' => auth()->id(),
                'code' => $this->uniqueCode(),
                'closes_at' => $closesAt,
            ]);

            // Chủ phòng cũng là một thành viên để có thể chọn món và chat ngay,
            // không phải tham gia lại bằng chính link của phòng.
            $group->members()->create([
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'member_token' => Str::random(48),
            ]);

            return $group;
        });

        $redirectUrl = route('group-orders.show', $group->code);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã tạo đơn nhóm.',
                'redirect_url' => $redirectUrl,
            ], 201);
        }

        return redirect()->to($redirectUrl)
            ->with('success', 'Đã tạo đơn nhóm. Hãy gửi đường link cho mọi người!');
    }

    public function show(string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->closeIfExpired();

        // Sau khi chủ nhóm đã tạo đơn chính thức, thành viên không còn được
        // ở lại hoặc mở lại phòng cũ để thao tác tiếp.
        if ($group->status === 'ordered' && $group->owner_id !== auth()->id()) {
            return redirect()->route('home')->with('success', 'Đơn nhóm "'.$group->name.'" đã được chủ nhóm đặt thành công. Phòng đã đóng.');
        }

        // Khôi phục dữ liệu các phòng đã được tạo trước khi chủ phòng được tự
        // thêm vào danh sách thành viên. Nhờ đó chủ phòng cũ vẫn xem/chat được
        // cả khi phòng đã đóng.
        if ($group->owner_id === auth()->id()) {
            $group->members()->firstOrCreate(
                ['user_id' => auth()->id()],
                ['name' => auth()->user()->name, 'member_token' => Str::random(48)]
            );
        }

        if ($group->owner_id === auth()->id() && $group->isOpen()) {
            $group->update(['owner_last_seen_at' => now()]);
        }
        $group->load(['owner', 'branch', 'members.items.product', 'items']);
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

        if ($group->status === 'ordered' && $group->owner_id !== auth()->id()) {
            session()->flash('success', 'Đơn nhóm "'.$group->name.'" đã được chủ nhóm đặt thành công. Phòng đã đóng.');

            return response()->json([
                'is_open' => false,
                'redirect_url' => route('home'),
            ]);
        }

        if ($group->owner_id === auth()->id() && $group->isOpen()) {
            $group->update(['owner_last_seen_at' => now()]);
        }

        return response()->json([
            'owner_present' => $group->fresh()->ownerIsPresent(),
            'is_open' => $group->isOpen(),
            'closes_at' => $group->closes_at->toIso8601String(),
        ]);
    }

    public function state(string $code): JsonResponse
    {
        $group = GroupOrder::query()
            ->where('code', $code)
            ->firstOrFail(['id', 'status', 'closes_at', 'status_changed_at']);

        $itemState = $group->items()
            ->selectRaw('COUNT(*) as item_count, COALESCE(SUM(quantity), 0) as quantity_total, MAX(updated_at) as latest_update')
            ->first();
        $memberState = $group->members()
            ->selectRaw('COUNT(*) as member_count, MAX(updated_at) as latest_update')
            ->first();

        $fingerprint = sha1(json_encode([
            $group->status,
            $group->closes_at?->getTimestamp(),
            $group->status_changed_at?->getTimestamp(),
            (int) $itemState->item_count,
            (int) $itemState->quantity_total,
            (string) $itemState->latest_update,
            (int) $memberState->member_count,
            (string) $memberState->latest_update,
        ], JSON_THROW_ON_ERROR));

        return response()->json(['fingerprint' => $fingerprint])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function leave(string $code): JsonResponse
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        if ($group->owner_id === auth()->id() && $group->isOpen()) {
            $group->update(['owner_last_seen_at' => null]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * leave() chỉ ghi nhận chủ phòng rời tab. Hàm này là thao tác rời phòng
     * thực sự: xóa phần tham gia của thành viên và giải phóng một chỗ trong phòng.
     */
    public function leaveRoom(string $code)
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $group->closeIfExpired();

        abort_if($group->owner_id === auth()->id(), 422, 'Chủ phòng không thể rời phòng. Bạn có thể hủy phòng nếu không muốn tiếp tục.');
        abort_unless($group->isOpen(), 422, 'Đơn nhóm đã đóng nên không thể rời phòng.');

        DB::transaction(function () use ($group) {
            $member = GroupOrderMember::query()
                ->where('group_order_id', $group->id)
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            abort_unless($member, 404, 'Bạn không còn là thành viên của phòng này.');
            $member->delete();
        });

        session()->forget("group_member_{$group->id}");

        return redirect()->route('group-orders.index')
            ->with('success', 'Bạn đã rời phòng. Món đã chọn và tin nhắn riêng liên quan của bạn đã được xóa; phòng vẫn tiếp tục cho các thành viên còn lại.');
    }

    public function messages(Request $request, string $code): JsonResponse
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $member = $this->currentMember($group);
        $recipientId = $request->integer('recipient_id') ?: null;

        $query = $group->messages()->with(['sender:id,name', 'recipient:id,name'])->latest('id')->limit(100);
        if ($recipientId) {
            abort_unless($group->members()->whereKey($recipientId)->exists(), 422, 'Thành viên nhận không hợp lệ.');
            $query->where(function ($query) use ($member, $recipientId) {
                $query->where(fn ($q) => $q->where('sender_member_id', $member->id)->where('recipient_member_id', $recipientId))
                    ->orWhere(fn ($q) => $q->where('sender_member_id', $recipientId)->where('recipient_member_id', $member->id));
            });
        } else {
            $query->whereNull('recipient_member_id');
        }

        $latestIncoming = $group->messages()
            ->with('sender:id,name')
            ->where('recipient_member_id', $member->id)
            ->latest('id')
            ->first();
        $latestGroupMessage = $group->messages()
            ->with('sender:id,name')
            ->whereNull('recipient_member_id')
            ->latest('id')
            ->first();
        $recentGroupMessages = $group->messages()
            ->with('sender:id,name')
            ->whereNull('recipient_member_id')
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message) => $this->messagePayload($message));
        $privateUnreadCounts = $group->messages()
            ->where('recipient_member_id', $member->id)
            ->whereNull('read_at')
            ->selectRaw('sender_member_id, COUNT(*) as unread_count')
            ->groupBy('sender_member_id')
            ->pluck('unread_count', 'sender_member_id');
        $latestUnreadBySender = $group->messages()
            ->with('sender:id,name')
            ->where('recipient_member_id', $member->id)
            ->whereNull('read_at')
            ->latest('id')
            ->get()
            ->unique('sender_member_id')
            ->values()
            ->map(fn ($message) => $this->messagePayload($message));

        return response()->json([
            'group_id' => $group->id,
            'group_code' => $group->code,
            'messages' => $query->get()->reverse()->values()->map(fn ($message) => $this->messagePayload($message)),
            'members' => $group->members()->orderBy('id')->get(['id', 'name']),
            'latest_incoming_private' => $latestIncoming ? $this->messagePayload($latestIncoming) : null,
            'latest_group_message' => $latestGroupMessage ? $this->messagePayload($latestGroupMessage) : null,
            'recent_group_messages' => $recentGroupMessages,
            'private_unread_counts' => $privateUnreadCounts,
            'latest_unread_private_by_sender' => $latestUnreadBySender,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function sendMessage(Request $request, string $code): JsonResponse
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        abort_if($group->status === 'cancelled', 422, 'Phòng đã hủy nên không thể gửi tin nhắn.');
        abort_if($group->status === 'closed', 422, 'Phòng đã đóng nên không thể gửi tin nhắn mới.');
        $sender = $this->currentMember($group);
        $data = $request->validate([
            'content' => [
                'nullable',
                'string',
                'max:1000',
                'required_without:attachment',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $this->containsProhibitedGroupChatWord($value)) {
                        $fail('Tin nhắn chứa từ ngữ không phù hợp. Vui lòng điều chỉnh trước khi gửi.');
                    }
                },
            ],
            'recipient_id' => ['nullable', 'integer'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,txt,zip'],
        ]);
        $recipientId = isset($data['recipient_id']) ? (int) $data['recipient_id'] : null;
        if ($recipientId) {
            abort_if($recipientId === $sender->id, 422, 'Bạn không thể tự nhắn cho chính mình.');
            abort_unless($group->members()->whereKey($recipientId)->exists(), 422, 'Thành viên nhận không hợp lệ.');
        }
        $attachment = $request->file('attachment');
        $message = GroupOrderMessage::create([
            'group_order_id' => $group->id,
            'sender_member_id' => $sender->id,
            'recipient_member_id' => $recipientId,
            'content' => trim($data['content'] ?? ''),
            'attachment_path' => $attachment?->store("group-orders/{$group->id}", 'public'),
            'attachment_name' => $attachment?->getClientOriginalName(),
            'attachment_mime' => $attachment?->getMimeType(),
            'attachment_size' => $attachment?->getSize(),
        ])->load(['sender:id,name', 'recipient:id,name']);

        broadcast(new GroupOrderGroupMessageSent($message))->toOthers();

        return response()->json(['message' => $this->messagePayload($message)], 201);
    }

    public function readMessages(Request $request, string $code): JsonResponse
    {
        $group = GroupOrder::where('code', $code)->firstOrFail();
        $member = $this->currentMember($group);
        $data = $request->validate(['sender_id' => ['required', 'integer']]);
        abort_unless($group->members()->whereKey($data['sender_id'])->exists(), 422, 'Thành viên gửi không hợp lệ.');

        $group->messages()
            ->where('sender_member_id', $data['sender_id'])
            ->where('recipient_member_id', $member->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function messagePayload(GroupOrderMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_member_id,
            'sender_name' => $message->sender->name,
            'recipient_id' => $message->recipient_member_id,
            'content' => $message->content,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'attachment_size' => $message->attachment_size,
            'attachment_url' => $message->attachment_path ? Storage::disk('public')->url($message->attachment_path) : null,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Bộ lọc này chỉ áp dụng cho chat trong đơn nhóm. Nội dung được chuẩn hóa
     * để nhận diện cả chữ không dấu, viết xen ký tự và một số kiểu viết leet.
     */
    private function containsProhibitedGroupChatWord(string $content): bool
    {
        $normalized = Str::lower(Str::ascii($content));
        $normalized = strtr($normalized, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's']);
        $normalized = preg_replace('/[^a-z]+/', ' ', $normalized) ?? '';

        $wordPattern = '/(?:^| )(?:dm+|dcm+|vcl|vl|cc|cac|lon|dit|deo|concho|ngu|fuck|shit|bitch)(?= |$)/';
        $obfuscatedAbbreviationPattern = '/(?:^| )d\s*(?:m+|c\s*m?)(?= |$)/';

        return preg_match($wordPattern, $normalized) === 1
            || preg_match($obfuscatedAbbreviationPattern, $normalized) === 1;
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

    public function decrementItem(string $code, GroupOrderItem $item)
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

            if ($lockedItem->quantity <= 1) {
                $lockedItem->delete();

                return;
            }

            $lockedItem->decrement('quantity');
        });

        return back()->with('success', 'Đã giảm 1 phần của món này.');
    }

    public function close(string $code)
    {
        if (session()->has('checkout_group_order_id')) {
            abort(422, 'Bạn đang có một đơn nhóm chờ thanh toán. Hãy hoàn tất hoặc hủy đơn đó trước.');
        }

        $group = GroupOrder::where('code', $code)->firstOrFail();
        if ($group->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($group->status !== 'open') {
            abort(422, 'Đơn nhóm đã được chốt hoặc hủy trước đó.');
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
        $group = GroupOrder::with(['items.product.category', 'items.member'])->where('code', $code)->firstOrFail();
        abort_unless($group->owner_id === auth()->id() && $group->status === 'closed' && ! $group->order_id, 403);

        // Nhóm này đã ở giỏ chờ thanh toán: quay thẳng về checkout thay vì
        // chặn nhầm là một đơn nhóm khác.
        if (session()->has('checkout_group_order_id')) {
            if ((int) session('checkout_group_order_id') === (int) $group->id) {
                return redirect()->route('checkout.index');
            }

            return back()->with('error', 'Bạn đang có một đơn nhóm khác chờ thanh toán.');
        }

        if ($group->items->isEmpty()) {
            return back()->with('error', 'Đơn nhóm không có món để thanh toán.');
        }
        $this->activateGroupCart($group);
        return redirect()->route('cart.index')->with('success', 'Đã khôi phục giỏ hàng đơn nhóm.');
    }

    /**
     * Khôi phục đơn nhóm chờ thanh toán trên một phiên/trình duyệt khác.
     */
    public function resumePendingCheckout()
    {
        $group = GroupOrder::with(['items.product.category', 'items.member'])
            ->where('owner_id', auth()->id())
            ->where('status', 'closed')
            ->whereNull('order_id')
            ->latest('locked_at')
            ->latest('id')
            ->firstOrFail();

        if (session()->has('checkout_group_order_id')) {
            if ((int) session('checkout_group_order_id') === (int) $group->id) {
                return redirect()->route('checkout.index');
            }

            return back()->with('error', 'Bạn đang có một đơn nhóm khác chờ thanh toán.');
        }

        if ($group->items->isEmpty()) {
            return back()->with('error', 'Đơn nhóm không có món để thanh toán.');
        }

        $this->activateGroupCart($group);

        return redirect()->route('cart.index')->with('success', 'Đã khôi phục giỏ hàng đơn nhóm.');
    }

    private function restorePersonalCart(): void
    {
        if (session()->has('personal_cart_backup')) session()->put('cart', session()->pull('personal_cart_backup'));
        session()->forget(['group_cart_keys', 'checkout_group_order_id', 'group_branch_id']);
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
        session()->put('group_branch_id', $group->branch_id);
    }

    private function uniqueCode(): string
    {
        do { $code = strtoupper(Str::random(8)); } while (GroupOrder::where('code', $code)->exists());
        return $code;
    }
}
