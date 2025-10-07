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
            $table->string('order_id')->index(); // Reference to your order system
            $table->unsignedBigInteger('user_id')->nullable()->index(); // Reference to user
            $table->string('payment_method', 50)->default('payzy');
            $table->string('transaction_id', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('LKR');
            $table->string('reference')->nullable();
            $table->enum('payment_status', ['Pending', 'Completed', 'Failed', 'Cancelled'])->default('Pending');
            $table->json('payment_data')->nullable(); // Store Payzy request data
            $table->json('response_data')->nullable(); // Store Payzy response data
            $table->decimal('shipment_charges', 10, 2)->default(0.00);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['payment_status', 'created_at']);
            $table->index(['user_id', 'payment_status']);
            $table->index('order_id');
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