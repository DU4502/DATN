<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetMonthlyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:reset-monthly-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly points for all users at the start of each month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $currentMonth = now()->format('Y-m');
        
        // Get all loyalty points that haven't been reset this month
        $loyaltyPoints = LoyaltyPoint::query()
            ->where('current_month', '!=', $currentMonth)
            ->get();

        if ($loyaltyPoints->isEmpty()) {
            $this->info('No loyalty points to reset.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($loyaltyPoints as $loyaltyPoint) {
            try {
                $loyaltyPoint->resetMonthlyPoints();
                
                $count++;
                
                Log::info("Reset monthly points for user #{$loyaltyPoint->user_id}", [
                    'user_id' => $loyaltyPoint->user_id,
                ]);
                
            } catch (\Throwable $e) {
                Log::error("Failed to reset monthly points for user #{$loyaltyPoint->user_id}", [
                    'user_id' => $loyaltyPoint->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reset monthly points for {$count} users.");
        
        return self::SUCCESS;
    }
}
