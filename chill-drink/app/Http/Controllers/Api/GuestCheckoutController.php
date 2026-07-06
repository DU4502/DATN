<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Jobs\ProcessGuestOrderEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            // Here, we should Ideally loop through $request->items to calculate real price.
            // Using a simple mock total for this architectural demonstration.
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * 35000; // Mock 35,000 VND per item
            }

            $orderToken = (string) Str::uuid();

            $order = Order::create([
                'user_id' => null,
                'guest_name' => $request->guest_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'guest_token' => $orderToken,
                'subtotal' => $totalAmount,
                'total' => $totalAmount,
                'total_price' => $totalAmount,
                'status' => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
            ]);

            // Save order items...
            foreach ($request->items as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => 35000, 
                    'total_price' => $item['quantity'] * 35000,
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
                    'total_amount' => $order->total,
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

            // Calculate total accrued points
            $linkedOrders = Order::where('user_id', $user->id)->get();
            $totalPointsEarned = 0;
            foreach ($linkedOrders as $o) {
                $totalPointsEarned += $o->pointsEarnable();
            }

            // Sync with loyalty_points table
            DB::table('loyalty_points')->insert([
                'user_id' => $user->id,
                'total_points' => $totalPointsEarned,
                'lifetime_points' => $totalPointsEarned,
                'level' => 'bronze',
            ]);

            DB::commit();

            // Typically you would issue a Sanctum/Passport token here.
            $token = 'dummy_auth_token_string'; 

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
