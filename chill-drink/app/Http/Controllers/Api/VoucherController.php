<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\UserVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Receive a voucher for the user (or guest).
     */
    public function receive(Request $request)
    {
        $request->validate([
            'voucher_code' => 'required|string',
            'guest_identifier' => 'nullable|string',
        ]);

        // Find voucher by code
        $voucher = Voucher::where('code', $request->voucher_code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Mã voucher không tồn tại'], 404);
        }

        if (!$voucher->status) {
            return response()->json(['message' => 'Mã voucher không còn hiệu lực'], 400);
        }

        // Check if voucher is expired
        if ($voucher->expires_at && $voucher->expires_at < now()) {
            return response()->json(['message' => 'Mã voucher đã hết hạn'], 400);
        }

        // Check if voucher has remaining uses
        if ($voucher->usage_limit > 0 && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json(['message' => 'Mã voucher đã hết lượt sử dụng'], 400);
        }

        // Check if user is authenticated
        if (Auth::check()) {
            // For logged-in users
            $userId = Auth::id();
            $guestIdentifier = null;

            // Check if user already has this voucher
            $existing = UserVoucher::where('user_id', $userId)
                ->where('coupon_id', $voucher->id)
                ->where('is_used', false)
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Bạn đã nhận voucher này rồi'], 400);
            }
        } else {
            // For guests
            $userId = null;
            $guestIdentifier = $request->guest_identifier ?? session()->getId();

            // Check if guest already has this voucher
            $existing = UserVoucher::where('guest_identifier', $guestIdentifier)
                ->where('coupon_id', $voucher->id)
                ->where('is_used', false)
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Bạn đã nhận voucher này rồi'], 400);
            }
        }

        // Create user voucher record
        $userVoucher = UserVoucher::create([
            'user_id' => $userId,
            'coupon_id' => $voucher->id,
            'guest_identifier' => $guestIdentifier,
            'code' => $voucher->code,
            'redeemed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Nhận voucher thành công',
            'guest_identifier' => $guestIdentifier, // Return for frontend to save
            'voucher' => [
                'id' => $userVoucher->id,
                'code' => $voucher->code,
                'description' => $voucher->description,
                'value' => $voucher->formattedValue(),
            ],
        ], 201);
    }

    /**
     * Get user's received vouchers.
     */
    public function getReceived(Request $request)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $userVouchers = UserVoucher::where('user_id', $userId)
                ->where('is_used', false)
                ->with('voucher')
                ->get();
        } else {
            $guestIdentifier = $request->guest_identifier ?? session()->getId();
            $userVouchers = UserVoucher::where('guest_identifier', $guestIdentifier)
                ->where('is_used', false)
                ->with('voucher')
                ->get();
        }

        $vouchers = $userVouchers->filter(fn ($uv) => $uv->voucher !== null)->map(function ($uv) {
            return [
                'id' => $uv->id,
                'code' => $uv->voucher->code,
                'description' => $uv->voucher->description,
                'value' => $uv->voucher->formattedValue(),
                'raw_value' => (float) $uv->voucher->value,
                'type' => $uv->voucher->type,
                'min_order' => (int) $uv->voucher->min_order,
                'max_discount' => (int) ($uv->voucher->max_discount ?? 0),
            ];
        })->values();

        return response()->json(['vouchers' => $vouchers]);
    }

    /**
     * Mark a voucher as used.
     */
    public function markAsUsed(Request $request, $id)
    {
        $request->validate([
            'guest_identifier' => 'nullable|string',
        ]);

        $userVoucher = UserVoucher::findOrFail($id);

        // Verify ownership
        if (Auth::check()) {
            if ($userVoucher->user_id !== Auth::id()) {
                return response()->json(['message' => 'Không có quyền truy cập'], 403);
            }
        } else {
            $guestIdentifier = $request->guest_identifier ?? session()->getId();
            if ($userVoucher->guest_identifier !== $guestIdentifier) {
                return response()->json(['message' => 'Không có quyền truy cập'], 403);
            }
        }

        $userVoucher->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        return response()->json(['message' => 'Đánh dấu voucher đã sử dụng thành công']);
    }
}
