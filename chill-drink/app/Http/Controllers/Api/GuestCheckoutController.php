<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\ProductSize;
use App\Models\User;
use App\Jobs\ProcessGuestOrderEmail;
use App\Support\OrderDistancePolicy;
use App\Support\ShippingFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestCheckoutController extends Controller
{
    /**
     * Create a guest order.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'branch_id' => 'required|integer|exists:branches,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'shipping_address' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $branch = Branch::availableForLocation()->find($request->branch_id);
        if (! $branch) {
            return response()->json([
                'status' => 'error',
                'errors' => ['branch_id' => ['Chi nhánh không khả dụng hoặc chưa có tọa độ.']],
            ], 422);
        }

        $distance = OrderDistancePolicy::distanceFromBranch(
            $branch,
            $request->latitude,
            $request->longitude
        );

        if ($distance === null) {
            return response()->json([
                'status' => 'error',
                'errors' => ['branch_id' => [OrderDistancePolicy::routingUnavailableMessage()]],
            ], 503);
        }

        if (! OrderDistancePolicy::isInsideServiceRadius($distance)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['branch_id' => [OrderDistancePolicy::message()]],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $itemsData = [];
            foreach ($request->items as $item) {
                $product = \App\Models\Product::findOrFail($item['product_id']);
                $productSize = ProductSize::query()
                    ->where('product_id', $product->id)
                    ->orderBy('id')
                    ->first();

                if (! $productSize) {
                    throw new \RuntimeException("Product {$product->id} has no sellable size.");
                }

                $price = (int) ($productSize->price ?: $product->price);
                $itemTotal = $price * (int) $item['quantity'];
                $totalAmount += $itemTotal;
                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_size_id' => $productSize->id,
                    'quantity' => (int) $item['quantity'],
                    'price' => $price,
                    'total_price' => $itemTotal,
                ];
            }

            $cupCount = max(1, (int) collect($itemsData)->sum('quantity'));
            $shippingQuote = ShippingFee::calculate($distance, 'standard', $cupCount);
            $shippingFee = (int) $shippingQuote['total_fee'];
            $grandTotal = max(0, $totalAmount + $shippingFee);

            $orderToken = (string) Str::uuid();

            $orderData = [
                'user_id' => null,
                'guest_name' => $request->guest_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'guest_token' => $orderToken,
                'branch_id' => $request->branch_id,
                'subtotal' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'total' => $grandTotal,
                'shipping_address_text' => trim((string) $request->input('shipping_address', '')) ?: null,
                'shipping_latitude' => (float) $request->latitude,
                'shipping_longitude' => (float) $request->longitude,
                'fulfillment_type' => 'delivery',
                'delivery_type' => 'now',
                'status' => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
            ];

            if (Schema::hasColumn('orders', 'total_price')) {
                $orderData['total_price'] = $grandTotal;
            }

            $order = Order::create($orderData);

            // Save order items with real product prices
            foreach ($itemsData as $itemData) {
                $order->orderItems()->create([
                    'product_id' => $itemData['product_id'],
                    'product_size_id' => $itemData['product_size_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price'],
                    'total_price' => $itemData['total_price'],
                ]);
            }

            DB::commit();

            // Dispatch job asynchronously
            ProcessGuestOrderEmail::dispatch($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_token' => $order->guest_token,
                    'subtotal' => (int) $order->subtotal,
                    'shipping_fee' => (int) $order->shipping_fee,
                    'distance_km' => $distance,
                    'cup_count' => $cupCount,
                    'total_amount' => (int) $order->total,
                    'potential_points' => $order->pointsEarnable(),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during checkout.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert guest to member by registering with order email and token.
     */
    public function convertToMember(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_token' => 'required|string|size:36',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('guest_token', $request->order_token)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid order token'
            ], 404);
        }

        if ($order->user_id !== null) {
            return response()->json([
                'status' => 'error',
                'message' => 'This order is already linked to a user.'
            ], 400);
        }

        // Check if user already exists
        $existingUser = User::where('email', $order->guest_email)->first();
        if ($existingUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already registered. Please log in to claim points.'
            ], 409);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $order->guest_name,
                'email' => $order->guest_email,
                'phone' => $order->guest_phone,
                'password' => Hash::make($request->password),
                'role_id' => 1,
                'is_active' => true,
            ]);

            // Link all previous orders from this guest email (that are still guests)
            $ordersLinkedCount = Order::where('guest_email', $order->guest_email)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            // Calculate total accrued points from completed orders
            $linkedOrders = Order::where('user_id', $user->id)
                ->where('status', \App\Support\OrderStatus::COMPLETED)
                ->get();
            $totalPointsEarned = 0;
            foreach ($linkedOrders as $o) {
                $totalPointsEarned += $o->pointsEarnable();
            }

            if ($totalPointsEarned > 0) {
                // Sync with loyalty_points table
                \App\Models\LoyaltyPoint::getOrCreateForUser($user->id)->addPoints(
                    points: $totalPointsEarned,
                    type: 'earn',
                    description: 'Tích điểm chuyển đổi thành viên',
                    referenceType: 'convert',
                    referenceId: $order->id
                );
            }

            DB::commit();

            $token = method_exists($user, 'createToken')
                ? $user->createToken('auth_token')->plainTextToken
                : Str::random(64);

            return response()->json([
                'status' => 'success',
                'message' => 'Account created and orders linked successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'loyalty_points' => $totalPointsEarned,
                    ],
                    'access_token' => $token,
                    'orders_linked' => $ordersLinkedCount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert member.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
