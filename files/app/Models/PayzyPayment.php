<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayzyPayment extends Model
{
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
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payment_data' => 'array',
        'response_data' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user who made this payment.
     * Adjust the User model path if your User model is in a different namespace.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ── Status Check Helpers ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->payment_status === self::STATUS_FAILED;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'payment_status' => self::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'payment_status' => self::STATUS_FAILED,
            'response_data' => array_merge($this->response_data ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Get formatted amount with currency (e.g., "LKR 1,500.00").
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    // ── Query Scopes ─────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', self::STATUS_FAILED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
