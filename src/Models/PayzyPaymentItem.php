<?php

namespace PayzyLaravel\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayzyPaymentItem extends Model
{
    use HasFactory;

    protected $table = 'payzy_payment_items';

    protected $fillable = [
        'payment_id', 
        'product_id', 
        'product_name',
        'product_sku',
        'quantity', 
        'unit_price',
        'offer_price',
        'total_price',
        'product_data',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
        'product_data' => 'array',
    ];

    /**
     * Get the payment that owns this item
     */
    public function payment()
    {
        return $this->belongsTo(PayzyPayment::class, 'payment_id');
    }

    /**
     * Get the product if using a product model
     */
    public function product()
    {
        $productModel = config('payzy.product_model');
        if ($productModel && class_exists($productModel)) {
            return $this->belongsTo($productModel, 'product_id');
        }
        return null;
    }

    /**
     * Get the effective price (offer price if available, otherwise unit price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->offer_price ?? $this->unit_price;
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    /**
     * Calculate and update total price based on quantity and effective price
     */
    public function calculateTotalPrice(): void
    {
        $this->total_price = $this->quantity * $this->effective_price;
        $this->save();
    }
}