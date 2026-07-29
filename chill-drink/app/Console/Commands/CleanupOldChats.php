<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;

class CleanupOldChats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xoá toàn bộ conversation (kể cả messages) không có tin nhắn mới sau khoảng thời gian cấu hình (CHAT_EXPIRY_MINUTES)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Lấy số tháng hết hạn từ .env, mặc định 6 tháng
        $expiryMonths = (int) env('CHAT_EXPIRY_MONTHS', 6);
        $cutoff = now()->subMonths($expiryMonths);

        // Xoá conversation có last_message_at < cutoff (tin nhắn cuối quá cũ)
        // Messages bị cascade delete tự động (foreign key onDelete cascade)
        $deleted = Conversation::where('last_message_at', '<', $cutoff)->delete();

        // Xoá conversation chưa có tin nhắn nào và đã tạo lâu hơn cutoff
        $deletedEmpty = Conversation::whereNull('last_message_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $total = $deleted + $deletedEmpty;

        if ($total > 0) {
            $this->info("[chat:cleanup] Đã xoá {$total} conversation hết hạn (ngưỡng: {$expiryMonths} tháng).");
        } else {
            $this->info("[chat:cleanup] Không có conversation nào cần xoá.");
        }
    }
}
