<?php

namespace PayzyLaravel\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayzyPayment extends Model
{
    use HasFactory;

    protected $table = 'payzy_payments';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method',
        'transaction_id',
        'amount',
        'currency',
        'reference',
        'payment_status',
        'payment_data',
        'response_data',
        'paid_at',
        'shipment_charges',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payment_data' => 'array', // Cast to array for JSON storage
        'response_data' => 'array', // Cast to array for JSON storage
        'amount' => 'decimal:2',
        'shipment_charges' => 'decimal:2',
    ];

    /**
     * Get the payment items for this payment
     */
    public function paymentItems(): HasMany
    {
        return $this->hasMany(PayzyPaymentItem::class, 'payment_id');
    }

    /**
     * Get the user who made this payment
     */
    public function user()
    {
        return $this->belongsTo(config('payzy.user_model', 'App\Models\User'));
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->payment_status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->payment_status === self::STATUS_CANCELLED;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'payment_status' => self::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'payment_status' => self::STATUS_FAILED,
            'response_data' => array_merge($this->response_data ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now(),
            ]),
        ]);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', self::STATUS_FAILED);
    }

    /**
     * Scope for payments by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}