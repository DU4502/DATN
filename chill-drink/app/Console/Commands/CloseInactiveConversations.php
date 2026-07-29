<?php

namespace App\Console\Commands;

use App\Events\ConversationClosed;
use App\Models\Conversation;
use Illuminate\Console\Command;

class CloseInactiveConversations extends Command
{
    protected $signature = 'chat:close-inactive';

    protected $description = 'Tự động đóng các phiên chat không hoạt động quá 24h';

    public function handle(): int
    {
        $cutoff = now()->subHours(24);

        $inactiveConversations = Conversation::where('status', 'open')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_message_at', '<', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('last_message_at')
                          ->where('created_at', '<', $cutoff);
                    });
            })
            ->get();

        $count = 0;
        foreach ($inactiveConversations as $conversation) {
            $conversation->update(['status' => 'closed']);
            try {
                broadcast(new ConversationClosed($conversation, 'timeout'))->toOthers();
            } catch (\Throwable) {}
            $count++;
        }

        $this->info("Đã đóng {$count} phiên chat không hoạt động quá 24h.");

        return Command::SUCCESS;
    }
}
