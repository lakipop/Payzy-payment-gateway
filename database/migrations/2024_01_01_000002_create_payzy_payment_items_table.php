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
        Schema::create('payzy_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payzy_payments')->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->nullable(); // Reference to your product system
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2);
            $table->json('product_data')->nullable(); // Store additional product information
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['payment_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payzy_payment_items');
    }
};