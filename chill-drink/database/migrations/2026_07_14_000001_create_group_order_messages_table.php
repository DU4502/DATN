<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_member_id')->constrained('group_order_members')->cascadeOnDelete();
            $table->foreignId('recipient_member_id')->nullable()->constrained('group_order_members')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
            $table->index(['group_order_id', 'recipient_member_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_order_messages');
    }
};
