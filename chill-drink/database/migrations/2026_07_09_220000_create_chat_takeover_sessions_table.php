<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_takeover_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->integer('super_admin_id');
            $table->integer('impersonate_as_id');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('super_admin_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('impersonate_as_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['conversation_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_takeover_sessions');
    }
};
