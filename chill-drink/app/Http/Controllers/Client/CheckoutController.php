<?php

namespace App\Http\Controllers\Client;

use App\Support\GuestOrderAccess;
use App\Support\RealtimeOrderNotifier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\Voucher;
use App\Support\ShippingFee;
use App\Support\ScheduledDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * Display checkout page
     */
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        $cart = $this->normalizeCartForCheckout($cart);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        if (! empty($this->invalidCheckoutCartKeys($cart))) {
            session()->forget('checkout_cart_keys');

            return redirect()->route('cart.index')->with(
                'error',
                'Giỏ hàng có sản phẩm demo chưa thể thanh toán. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách sản phẩm thật.'
            );
        }

        if ($request->query->has('items')) {
            $selectedKeys = $this->selectedCartKeys($request->query('items', []), $cart);

            if (empty($selectedKeys)) {
                return redirect()->route('cart.index')->with('error', 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
            }

            $cart = array_intersect_key($cart, array_flip($selectedKeys));
            session(['checkout_cart_keys' => $selectedKeys]);
        } else {
            session()->forget('checkout_cart_keys');
        }

        $cart = $this->hydrateCheckoutCart($cart);
        $shippingDistanceOptions = ShippingFee::distanceOptions();
        $shippingMethods = ShippingFee::methods();
        $paymentOptions = $this->paymentOptions();
        $locationReadyBranchCount = Branch::query()->availableForLocation()->count();
        $loyaltyContext = $this->loyaltyContext(false);
        $subtotal = $this->cartSubtotal($cart);
        $checkoutAddresses = auth()->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()->map(fn ($address) => [
            'id' => (string) $address->id,
            'name' => $address->receiver_name,
            'phone' => $address->phone,
            'street' => $address->detail,
            'area' => collect([$address->ward, $address->district, $address->province])->filter()->implode(', '),
            'type' => $address->label,
            'isDefault' => (bool) $address->is_default,
        ])->values();
        $now = now();
        $availableVouchers = Voucher::query()
            ->where('status', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($query) {
                $query->where('usage_limit', '<=', 0)
                    ->orWhereRaw('used_count < usage_limit');
            })
            ->latest('created_at')
            ->get()
            ->filter(fn (Voucher $voucher) => $voucher->isActiveNow()
                && $voucher->hasRemainingUses())
            ->values();

        return view('client.checkout.index', compact(
            'cart',
            'shippingDistanceOptions',
            'shippingMethods',
            'paymentOptions',
            'availableVouchers',
            'locationReadyBranchCount',
            'loyaltyContext',
            'subtotal',
            'checkoutAddresses'
        ));
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        if ($request->filled('scheduled_at') && ! $request->filled('delivery_type')) {
            $request->merge([
                'delivery_type' => 'scheduled',
                'scheduled_delivery_time' => $request->input('scheduled_at'),
            ]);
        }

        $request->validate([
            'payment_method' => [
                'required',
                Rule::in(array_keys($this->paymentOptions())),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('delivery_type') === 'scheduled' && $value === 'cod') {
                        $fail('Đơn đặt giao sau phải thanh toán trước. Vui lòng chọn VNPay để tiếp tục.');
                    }
                },
            ],
            'shipping_method_ui' => ['required', Rule::in(array_keys(ShippingFee::methods()))],
            'shipping_address_ui' => ['required', 'string', 'max:255'],
            'shipping_area_ui' => ['nullable', 'string', 'max:255'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
            'voucher_code' => 'nullable|string|max:50',
            'shipping_voucher_code' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
            'delivery_note' => 'nullable|string|max:1000',
            'delivery_type' => ['nullable', Rule::in(['now', 'scheduled'])],
            'scheduled_delivery_time' => [
                'nullable', 'date', 'required_if:delivery_type,scheduled',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('delivery_type') !== 'scheduled') return;
                    if ($message = ScheduledDelivery::validate($value)) $fail($message);
                },
            ],
        ], [
            'shipping_address_ui.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'shipping_method_ui.required' => 'Vui lòng chọn phương thức giao hàng.',
            'shipping_method_ui.in' => 'Phương thức giao hàng không hợp lệ.',
            'scheduled_delivery_time.required_if' => 'Vui lòng chọn ngày và giờ muốn nhận hàng.',
            'branch_id.exists' => 'Chi nhánh đã chọn không còn hoạt động. Vui lòng tìm lại chi nhánh gần nhất.',
        ]);

        $fullCart = session()->get('cart', []);
        $selectedKeys = session()->get('checkout_cart_keys');
        $cart = $this->normalizeCartForCheckout($this->cartForCheckout($fullCart, $selectedKeys));

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        if (! empty($this->invalidCheckoutCartKeys($cart))) {
            return redirect()->route('cart.index')->with(
                'error',
                'Giỏ hàng có sản phẩm demo chưa thể thanh toán. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách sản phẩm thật.'
            );
        }

        try {
            DB::beginTransaction();

            $orderItems = $this->prepareOrderItems($cart);
            $subtotal = collect($orderItems)->sum('total_price');

            $shippingQuote = ShippingFee::quoteForAddress(
                $request->shipping_address_ui,
                $request->shipping_area_ui,
                $request->shipping_method_ui
            );
            [$voucher, $orderDiscount] = $this->resolveVoucher($request->input('voucher_code'), $subtotal, false);
            [$shippingVoucher, $rawShippingDiscount] = $this->resolveVoucher($request->input('shipping_voucher_code'), $subtotal, true);
            $shippingDiscount = min((int) $shippingQuote['total_fee'], $rawShippingDiscount);
            $discount = $orderDiscount + $shippingDiscount;
            $grandTotal = max(0, $subtotal + $shippingQuote['total_fee'] - $discount);
            $addressText = trim(collect([
                $request->shipping_address_ui,
                $request->shipping_area_ui,
            ])->filter()->implode(', '));
            $shippingNote = sprintf(
                'Giao hàng: phí cố định %s%s',
                ShippingFee::formatCurrency($shippingQuote['total_fee']),
                $addressText ? ", địa chỉ: {$addressText}" : ''
            );
            $note = trim((string) $request->note);
            $note = trim($note ? "{$note}\n{$shippingNote}" : $shippingNote);
            $note = mb_substr($note, 0, 500);

            // Create order
            $orderData = [
                'user_id' => auth()->id(),
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'note' => $note,
                'delivery_type' => $request->input('delivery_type', 'now'),
                'fulfillment_type' => 'delivery',
                'scheduled_delivery_time' => $request->input('delivery_type') === 'scheduled' ? $request->date('scheduled_delivery_time') : null,
                'scheduled_at' => $request->input('delivery_type') === 'scheduled' ? $request->date('scheduled_delivery_time') : null,
                'delivery_note' => $request->input('delivery_note'),
            ];

            if (Schema::hasColumn('orders', 'branch_id') && $request->filled('branch_id')) {
                $orderData['branch_id'] = (int) $request->input('branch_id');
            }

            if (Schema::hasColumn('orders', 'total_price')) {
                $orderData['total_price'] = $grandTotal;
            }

            if (Schema::hasColumn('orders', 'subtotal')) {
                $orderData['subtotal'] = $subtotal;
            }

            if (Schema::hasColumn('orders', 'shipping_fee')) {
                $orderData['shipping_fee'] = $shippingQuote['total_fee'];
            }

            if (Schema::hasColumn('orders', 'discount')) {
                $orderData['discount'] = $discount;
            }

            if (($voucher || $shippingVoucher) && Schema::hasColumn('orders', 'coupon_id')) {
                $orderData['coupon_id'] = ($voucher ?? $shippingVoucher)->id;
            }

            if (Schema::hasColumn('orders', 'total')) {
                $orderData['total'] = $grandTotal;
            }

            if (Schema::hasColumn('orders', 'payment_status')) {
                $orderData['payment_status'] = 'pending';
            }

            $groupOrderId = session()->get('checkout_group_order_id');
            if ($groupOrderId) {
                $groupOrder = \App\Models\GroupOrder::query()->lockForUpdate()->findOrFail($groupOrderId);
                abort_unless($groupOrder->owner_id === auth()->id() && $groupOrder->status === 'closed' && ! $groupOrder->order_id, 422, 'Đơn nhóm không còn hợp lệ để thanh toán.');
                $expectedKeys = collect(session()->get('group_cart_keys', []))->sort()->values()->all();
                $actualKeys = collect(array_keys($cart))->sort()->values()->all();
                abort_unless($expectedKeys === $actualKeys, 422, 'Giỏ hàng đơn nhóm đã bị thay đổi. Vui lòng chốt lại đơn.');
            }

            $order = Order::create($orderData);

            if ($groupOrderId) {
                $groupOrder->update(['order_id' => $order->id, 'status' => 'ordered']);
            }

            // Create order items
            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create($this->orderItemData($order->id, $item));
                $this->recordOrderItemToppings((int) $orderItem->id, $item['toppings'] ?? []);
            }

            if ($voucher) {
                $this->recordVoucherUsage($voucher, $order->id, $orderDiscount);
            }
            if ($shippingVoucher) {
                $this->recordVoucherUsage($shippingVoucher, $order->id, $shippingDiscount);
            }

            if ($groupOrderId) {
                session()->put('cart', session()->pull('personal_cart_backup', []));
                session()->forget(['checkout_cart_keys', 'checkout_group_order_id', 'group_cart_keys']);
            } else {
                $this->removeCheckedOutItems($fullCart, $selectedKeys);
            }

            DB::commit();

            RealtimeOrderNotifier::orderStatusUpdated($order);

            if ($order->payment_method !== 'vnpay') {
                RealtimeOrderNotifier::orderCreated($order);
            }

            if ($order->payment_method === 'vnpay') {
                return redirect()
                    ->route('vnpay.payment', $order)
                    ->with('success', 'Đơn hàng đã được tạo. Vui lòng thanh toán qua VNPay.');
            }

            return redirect()->route('checkout.success', $order);

        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Checkout failed.', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Có lỗi xảy ra, vui lòng thử lại!';

            return redirect()->back()->withInput()->with('error', $message);
        }
    }

    public function success(Order $order)
    {
        abort_unless(GuestOrderAccess::canView($order), 403);

        $order->load('orderItems.product', 'branch');

        $payload = [
            'order' => $order,
            'result' => 'success',
            'title' => 'Cảm ơn bạn đã đặt hàng',
            'message' => 'Đơn hàng đã được tiếp nhận và sẽ sớm được chuẩn bị.',
        ];

        if ($order->isGuest()) {
            GuestOrderAccess::remember($order);
            GuestOrderAccess::storeConvertPayload($order);
            $payload['title'] = 'Cảm ơn bạn! Đơn hàng đã được tiếp nhận';
            $payload['guestConvert'] = session('guest_convert');
        }

        return view('client.checkout.success', $payload);
    }

    protected function resolveVoucher(?string $code, int $subtotal, ?bool $expectShipping = null): array
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return [null, 0];
        }

        $voucher = Voucher::query()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if (! $voucher) {
            throw new \RuntimeException('Mã voucher không tồn tại.');
        }

        if ($expectShipping !== null && $this->isShippingVoucher($voucher) !== $expectShipping) {
            throw new \RuntimeException($expectShipping
                ? 'Mã đã chọn không phải voucher miễn phí vận chuyển.'
                : 'Mã freeship không thể dùng ở ô giảm giá đơn hàng.');
        }

        if (! $voucher->status) {
            throw new \RuntimeException('Mã voucher đã bị tắt.');
        }

        if ($voucher->starts_at && $voucher->starts_at->gt(now())) {
            throw new \RuntimeException('Mã voucher chưa đến thời gian sử dụng.');
        }

        if ($voucher->expires_at && $voucher->expires_at->lt(now())) {
            throw new \RuntimeException('Mã voucher đã hết hạn.');
        }

        if (! $voucher->hasRemainingUses()) {
            throw new \RuntimeException('Mã voucher đã hết lượt sử dụng.');
        }

        if (! $voucher->meetsMinimumOrder($subtotal)) {
            throw new \RuntimeException(
                'Mã voucher chỉ áp dụng cho đơn từ '
                .number_format((int) $voucher->min_order, 0, ',', '.')
                .'đ.'
            );
        }

        $this->assertVoucherRankAndPoints($voucher);

        $discount = $voucher->discountFor($subtotal);

        if ($discount <= 0) {
            throw new \RuntimeException('Mã voucher không tạo được giá trị giảm cho đơn hàng này.');
        }

        return [$voucher, $discount];
    }

    protected function isShippingVoucher(Voucher $voucher): bool
    {
        return Str::contains(Str::upper((string) $voucher->code), ['SHIP', 'FREE']);
    }

    protected function assertVoucherRankAndPoints(Voucher $voucher): void
    {
        $context = $this->loyaltyContext();

        if ($voucher->required_rank && ! $this->rankAllows($context['rank'], $voucher->required_rank)) {
            throw new \RuntimeException("Mã voucher này chỉ dành cho khách hạng {$voucher->rankLabel()} trở lên.");
        }

        if ($voucher->is_redeemable && (int) $voucher->point_cost > 0 && $context['points'] < (int) $voucher->point_cost) {
            throw new \RuntimeException(
                'Bạn chưa đủ '
                .number_format((int) $voucher->point_cost, 0, ',', '.')
                .' điểm để dùng mã voucher này.'
            );
        }
    }

    protected function userCanUseVoucher(Voucher $voucher, array $context): bool
    {
        if ($voucher->required_rank && ! $this->rankAllows($context['rank'], $voucher->required_rank)) {
            return false;
        }

        return ! ($voucher->is_redeemable && (int) $voucher->point_cost > 0 && $context['points'] < (int) $voucher->point_cost);
    }

    protected function recordVoucherUsage(Voucher $voucher, int $orderId, int $discount): void
    {
        $voucher->increment('used_count');

        if (Schema::hasTable('user_coupon_usage')) {
            DB::table('user_coupon_usage')->insert([
                'user_id' => auth()->id(),
                'coupon_id' => $voucher->id,
                'order_id' => $orderId,
                'discount_amount' => $discount,
                'used_at' => now(),
            ]);
        }

        if ($voucher->is_redeemable && (int) $voucher->point_cost > 0 && Schema::hasTable('loyalty_points')) {
            DB::table('loyalty_points')
                ->where('user_id', auth()->id())
                ->where('total_points', '>=', (int) $voucher->point_cost)
                ->decrement('total_points', (int) $voucher->point_cost);
        }
    }

    protected function loyaltyContext(bool $lock = true): array
    {
        if (! Schema::hasTable('loyalty_points')) {
            return ['rank' => 'bronze', 'points' => 0];
        }

        $query = DB::table('loyalty_points')->where('user_id', auth()->id());

        if ($lock) {
            $query->lockForUpdate();
        }

        $row = $query->first();

        return [
            'rank' => $row->level ?? 'bronze',
            'points' => (int) ($row->total_points ?? 0),
        ];
    }

    protected function rankAllows(?string $userRank, string $requiredRank): bool
    {
        $rankOrder = [
            'bronze' => 1,
            'silver' => 2,
            'gold' => 3,
            'diamond' => 4,
        ];

        return ($rankOrder[$userRank ?: 'bronze'] ?? 1) >= ($rankOrder[$requiredRank] ?? 1);
    }

    protected function paymentOptions(): array
    {
        return [
            'cod' => [
                'title' => 'Thanh toán khi nhận hàng',
                'desc' => 'Trả tiền mặt sau khi nhận đồ uống.',
                'icon' => 'bi-cash-coin',
            ],
            'vnpay' => [
                'title' => 'VNPay',
                'desc' => 'Hỗ trợ thẻ ATM, QR và ngân hàng nội địa.',
                'icon' => 'bi-credit-card',
            ],
        ];
    }

    protected function normalizeCartForCheckout(array $cart): array
    {
        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $cartKey;

            if (is_numeric($productId)) {
                $product = Product::query()->find((int) $productId);

                if ($product) {
                    $cart[$cartKey]['product_id'] = (int) $product->id;
                    $cart[$cartKey]['name'] = $product->name;
                    $cart[$cartKey]['sku'] = $product->sku ?? null;
                    $cart[$cartKey]['category'] = $product->category?->name;
                    $cart[$cartKey]['base_price'] = (int) ($product->price ?? 0);
                    $cart[$cartKey]['price'] = max(0, (int) ($product->price ?? 0) + (int) ($item['size_extra'] ?? 0) + (int) ($item['topping_total'] ?? 0));

                    continue;
                }
            }

            $resolvedProduct = $this->resolveOrCreatePayableProduct($item, (string) $cartKey);

            if ($resolvedProduct) {
                $cart[$cartKey]['product_id'] = (int) $resolvedProduct->id;
                $cart[$cartKey]['name'] = $resolvedProduct->name;
                $cart[$cartKey]['sku'] = $resolvedProduct->sku ?? null;
                $cart[$cartKey]['category'] = $resolvedProduct->category?->name;
                $cart[$cartKey]['base_price'] = (int) ($resolvedProduct->price ?? 0);
                $cart[$cartKey]['price'] = max(0, (int) ($resolvedProduct->price ?? 0) + (int) ($item['size_extra'] ?? 0) + (int) ($item['topping_total'] ?? 0));
            }
        }

        return $cart;
    }

    protected function resolveOrCreatePayableProduct(array $item, string $cartKey): ?Product
    {
        $name = trim((string) ($item['name'] ?? ''));
        $fallbackName = $name !== '' ? $name : 'Sản phẩm '.Str::upper(Str::substr($cartKey, 0, 6));
        $candidateSlug = trim((string) ($item['slug'] ?? ''));
        $productId = $item['product_id'] ?? null;
        $fallbackSlug = $candidateSlug !== '' ? $candidateSlug : Str::slug($fallbackName);

        $product = Product::query()
            ->when($name !== '', fn ($query) => $query->where(function ($subQuery) use ($name, $fallbackSlug) {
                $subQuery->where('name', $name)
                    ->orWhere('slug', $fallbackSlug);
            }))
            ->when($name === '', fn ($query) => $query->where('slug', $fallbackSlug))
            ->first();

        if ($product) {
            return $product;
        }

        $categoryName = trim((string) ($item['category'] ?? ''));
        $category = null;

        if ($categoryName !== '') {
            $category = Category::query()->firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName), 'status' => true]
            );
        }

        $price = max(0, (int) ($item['price'] ?? $item['base_price'] ?? 0));

        return Product::create([
            'category_id' => $category?->id,
            'name' => $fallbackName,
            'slug' => $fallbackSlug,
            'price' => $price,
            'stock' => 100,
            'status' => true,
            'description' => trim((string) ($item['description'] ?? '')) !== ''
                ? $item['description']
                : 'Sản phẩm được tạo tự động để hỗ trợ thanh toán.',
        ]);
    }

    protected function prepareOrderItems(array $cart): array
    {
        $items = [];

        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $cartKey;

            if (! is_numeric($productId)) {
                throw new \RuntimeException('Giỏ hàng có sản phẩm chưa tồn tại trong kho. Vui lòng xóa sản phẩm đó và chọn lại từ danh sách.');
            }

            $product = Product::query()
                ->lockForUpdate()
                ->whereKey((int) $productId)
                ->where('status', true)
                ->first();

            if (! $product) {
                throw new \RuntimeException('Một sản phẩm trong giỏ đã ngừng bán. Vui lòng kiểm tra lại giỏ hàng.');
            }

            $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));

            if (Schema::hasColumn('products', 'stock')) {
                $stock = (int) ($product->stock ?? 0);

                if ($stock < $quantity) {
                    throw new \RuntimeException("Sản phẩm {$product->name} chỉ còn {$stock} món trong kho.");
                }

                $product->decrement('stock', $quantity);
            }

            $unitPrice = $this->currentUnitPriceForCheckoutItem($item, $product);

            $items[] = [
                'product_id' => (int) $productId,
                'product_size_id' => $this->resolveProductSizeId((int) $productId, $item, $unitPrice),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'ice_level' => (int) ($item['ice_level'] ?? 100),
                'sugar_level' => (int) ($item['sugar_level'] ?? 100),
            'toppings' => $item['toppings'] ?? [],
            'item_note' => $item['note'] ?? null,
            ];
        }

        if (empty($items)) {
            throw new \RuntimeException('Giỏ hàng trống, vui lòng thêm sản phẩm trước khi thanh toán.');
        }

        return $items;
    }

    protected function hydrateCheckoutCart(array $cart): array
    {
        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $cartKey;

            if (! is_numeric($productId)) {
                continue;
            }

            $product = Product::query()->whereKey((int) $productId)->first();

            if (! $product) {
                continue;
            }

            $cart[$cartKey]['product_id'] = (int) $product->id;
            $cart[$cartKey]['name'] = $product->name;
            $cart[$cartKey]['price'] = $this->currentUnitPriceForCheckoutItem($item, $product);
        }

        return $cart;
    }

    protected function cartSubtotal(array $cart): int
    {
        return (int) collect($cart)->sum(
            fn (array $item) => max(0, (int) ($item['price'] ?? 0)) * max(1, (int) ($item['quantity'] ?? 1))
        );
    }

    protected function currentUnitPriceForCheckoutItem(array $item, ?Product $product = null): int
    {
        $productId = $product?->id ?? (int) ($item['product_id'] ?? 0);
        $fallbackPrice = max(0, (int) ($item['price'] ?? $product?->price ?? 0));
        $toppingTotal = max(0, (int) ($item['topping_total'] ?? collect($item['toppings'] ?? [])->sum('price')));
        $sizeExtra = max(0, (int) ($item['size_extra'] ?? 0));

        if (
            $productId <= 0
            || ! Schema::hasTable('product_sizes')
            || ! Schema::hasTable('sizes')
        ) {
            return $fallbackPrice;
        }

        if (! empty($item['product_size_id'])) {
            $productSizePrice = ProductSize::query()
                ->whereKey((int) $item['product_size_id'])
                ->where('product_id', $productId)
                ->value('price');

            if (is_numeric($productSizePrice)) {
                return max(0, (int) $productSizePrice + $toppingTotal);
            }
        }

        $sizeCode = strtoupper(trim((string) ($item['size'] ?? '')));
        if ($sizeCode !== '') {
            $sizeNames = array_values(array_unique([$sizeCode, "Size {$sizeCode}"]));
            $productSizePrice = ProductSize::query()
                ->where('product_id', $productId)
                ->whereHas('size', fn ($query) => $query->whereIn('name', $sizeNames))
                ->value('price');

            if (is_numeric($productSizePrice)) {
                return max(0, (int) $productSizePrice + $toppingTotal);
            }
        }

        if ($product && is_numeric($product->price ?? null)) {
            return max(0, (int) $product->price + $sizeExtra + $toppingTotal);
        }

        return $fallbackPrice;
    }

    protected function recordOrderItemToppings(int $orderItemId, array $toppings): void
    {
        if ($orderItemId <= 0 || empty($toppings) || ! Schema::hasTable('order_item_toppings') || ! Schema::hasTable('toppings')) {
            return;
        }

        foreach ($toppings as $topping) {
            $name = trim((string) ($topping['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $price = max(0, (int) ($topping['price'] ?? 0));
            $toppingId = DB::table('toppings')->where('name', $name)->value('id');

            if (! $toppingId) {
                $toppingId = DB::table('toppings')->insertGetId([
                    'name' => $name,
                    'price' => $price,
                    'status' => 1,
                    'created_at' => now(),
                ]);
            }

            DB::table('order_item_toppings')->insert([
                'order_item_id' => $orderItemId,
                'topping_id' => (int) $toppingId,
                'price' => $price,
            ]);
        }
    }

    protected function resolveProductSizeId(int $productId, array $item, int $unitPrice): ?int
    {
        if (! Schema::hasColumn('order_items', 'product_size_id')) {
            return null;
        }

        if (! Schema::hasTable('product_sizes') || ! Schema::hasTable('sizes')) {
            throw new \RuntimeException('Thiếu bảng size sản phẩm, chưa thể tạo chi tiết đơn hàng.');
        }

        if (! empty($item['product_size_id'])) {
            $productSizeId = ProductSize::query()
                ->whereKey((int) $item['product_size_id'])
                ->where('product_id', $productId)
                ->value('id');

            if ($productSizeId) {
                return (int) $productSizeId;
            }
        }

        $sizeCode = strtoupper((string) ($item['size'] ?? 'M'));
        $sizeNames = array_values(array_unique([$sizeCode, "Size {$sizeCode}"]));
        $productSize = ProductSize::query()
            ->where('product_id', $productId)
            ->whereHas('size', fn ($query) => $query->whereIn('name', $sizeNames))
            ->first();

        if ($productSize) {
            return (int) $productSize->id;
        }

        $size = Size::query()
            ->whereIn('name', $sizeNames)
            ->first();

        if (! $size) {
            $size = Size::create([
                'name' => $sizeCode,
                'multiplier' => 1,
            ]);
        }

        return (int) ProductSize::firstOrCreate(
            [
                'product_id' => $productId,
                'size_id' => $size->id,
            ],
            [
                'price' => $unitPrice,
            ]
        )->id;
    }

    protected function orderItemData(int $orderId, array $item): array
    {
        $data = [
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
        ];

        foreach (['product_size_id', 'unit_price', 'total_price', 'ice_level', 'sugar_level', 'item_note'] as $column) {
            if (Schema::hasColumn('order_items', $column)) {
                $data[$column] = $item[$column];
            }
        }

        if (Schema::hasColumn('order_items', 'price')) {
            $data['price'] = $item['unit_price'];
        }

        return $data;
    }

    protected function removeCheckedOutItems(array $fullCart, mixed $selectedKeys): void
    {
        if (is_array($selectedKeys) && count($selectedKeys) > 0) {
            foreach ($selectedKeys as $cartKey) {
                unset($fullCart[$cartKey]);
            }

            if (empty($fullCart)) {
                session()->forget('cart');
            } else {
                session()->put('cart', $fullCart);
            }

            session()->forget('checkout_cart_keys');

            return;
        }

        session()->forget(['cart', 'checkout_cart_keys']);
    }

    protected function selectedCartKeys(mixed $keys, array $cart): array
    {
        $keys = is_array($keys) ? $keys : [$keys];

        return array_values(array_filter(
            array_map('strval', $keys),
            fn (string $key) => array_key_exists($key, $cart)
        ));
    }

    protected function cartForCheckout(array $cart, mixed $selectedKeys): array
    {
        if (! is_array($selectedKeys) || empty($selectedKeys)) {
            return $cart;
        }

        return array_intersect_key($cart, array_flip($this->selectedCartKeys($selectedKeys, $cart)));
    }

    protected function invalidCheckoutCartKeys(array $cart): array
    {
        $invalidKeys = [];

        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $cartKey;

            if (! is_numeric($productId)) {
                $invalidKeys[] = (string) $cartKey;
            }
        }

        return $invalidKeys;
    }
}
