<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\PointTransaction;
use App\Models\Voucher;
use App\Models\UserVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyPointController extends Controller
{
    /**
     * Display loyalty points page
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để xem điểm thưởng.');
        }
        
        // Get or create loyalty points
        $loyaltyPoint = LoyaltyPoint::getOrCreateForUser($user->id);
        
        // Get redeemable vouchers (vouchers that can be exchanged for points)
        $redeemableVouchers = Voucher::query()
            ->where('is_redeemable', 1)
            ->where('status', 1)
            ->where('point_cost', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('point_cost', 'asc')
            ->get();
        
        // Get transaction history
        $transactions = PointTransaction::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('profile.partials.loyalty-points', [
            'loyaltyPoint' => $loyaltyPoint,
            'redeemableVouchers' => $redeemableVouchers,
            'transactions' => $transactions,
        ]);
    }
    
    /**
     * Redeem a voucher using points
     */
    public function redeemVoucher(Request $request, Voucher $voucher)
    {
        $user = auth()->user();
        
        // Validate voucher is redeemable
        if (!$voucher->is_redeemable || $voucher->point_cost <= 0) {
            return back()->with('error', 'Voucher này không thể đổi bằng điểm.');
        }
        
        // Check voucher is active and not expired
        if (!$voucher->status) {
            return back()->with('error', 'Voucher không còn khả dụng.');
        }
        
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            return back()->with('error', 'Voucher đã hết hạn.');
        }
        
        // Get loyalty points
        $loyaltyPoint = LoyaltyPoint::getOrCreateForUser($user->id);
        
        // Check user has enough points
        if ($loyaltyPoint->total_points < $voucher->point_cost) {
            return back()->with('error', 'Bạn không đủ điểm để đổi voucher này.');
        }
        
        // Check if user already has this voucher (not used)
        $existingVoucher = UserVoucher::query()
            ->where('user_id', $user->id)
            ->where('voucher_id', $voucher->id)
            ->whereNull('used_at')
            ->first();
            
        if ($existingVoucher) {
            return back()->with('error', 'Bạn đã có voucher này trong tài khoản.');
        }
        
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Deduct points
            $success = $loyaltyPoint->deductPoints(
                points: $voucher->point_cost,
                type: 'spend',
                description: "Đổi voucher: {$voucher->code}",
                referenceType: 'voucher',
                referenceId: $voucher->id
            );
            
            if (!$success) {
                throw new \Exception('Không thể trừ điểm. Vui lòng thử lại.');
            }
            
            // Give voucher to user
            UserVoucher::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'received_at' => now(),
                'used_at' => null,
                'guest_identifier' => null,
            ]);
            
            DB::commit();
            
            return back()->with('success', "Đổi voucher thành công! Đã trừ {$voucher->point_cost} điểm. Voucher đã được thêm vào tài khoản của bạn.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    /**
     * Get loyalty context for checkout
     */
    public static function getLoyaltyContext(?int $userId = null): array
    {
        if (!$userId) {
            $userId = auth()->id();
        }
        
        if (!$userId) {
            return [
                'points' => 0,
            ];
        }
        
        $loyaltyPoint = LoyaltyPoint::where('user_id', $userId)->first();
        
        if (!$loyaltyPoint) {
            return [
                'points' => 0,
            ];
        }
        
        return [
            'points' => $loyaltyPoint->total_points,
        ];
    }
}
