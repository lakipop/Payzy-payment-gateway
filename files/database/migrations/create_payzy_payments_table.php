<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payzy_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();                    // Your app's order ID
            $table->unsignedBigInteger('user_id')->nullable()->index(); // Reference to users table
            $table->string('payment_method', 50)->default('payzy');
            $table->string('transaction_id', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('LKR');
            $table->string('reference')->nullable();
            $table->enum('payment_status', ['Pending', 'Completed', 'Failed', 'Cancelled'])->default('Pending');
            $table->json('payment_data')->nullable();               // Stores the signed request sent to Payzy
            $table->json('response_data')->nullable();              // Stores Payzy's response
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['payment_status', 'created_at']);
            $table->index(['user_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payzy_payments');
    }
};
