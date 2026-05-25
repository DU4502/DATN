<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('wallet_id')->nullable()->index();
            $table->integer('user_id');
            $table->integer('order_id')->nullable()->index();
            $table->enum('type', ['deposit', 'withdraw', 'payment', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
