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
    protected $description = 'Clean up chat conversations that have been inactive for more than 1 month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = now()->subMonth();
        
        // Find and delete conversations with last message older than 1 month
        $deletedRows = Conversation::where('last_message_at', '<', $cutoffDate)
                                     ->where('status', 'closed') // Normally we only delete closed ones, or all if requested. The prompt said "nếu không có tương tác mới", checking last_message_at over 1 month is good for all.
                                     ->orWhere(function($query) use ($cutoffDate) {
                                         $query->whereNull('last_message_at')
                                               ->where('created_at', '<', $cutoffDate);
                                     }) // cover the case where there is no messages
                                     ->delete();

        // Let's make it simpler matching user's prompt exactly
        $simpleDeletedRows = Conversation::where('last_message_at', '<', $cutoffDate)->delete();
        $nullDeletedRows = Conversation::whereNull('last_message_at')->where('created_at', '<', $cutoffDate)->delete();

        $total = $simpleDeletedRows + $nullDeletedRows;

        $this->info("Successfully deleted {$total} inactive conversations old than 1 month.");
    }
}
