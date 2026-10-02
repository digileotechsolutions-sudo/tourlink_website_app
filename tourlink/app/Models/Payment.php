<?php

namespace App\Models;

use App\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends TourLinkModel
{
    protected $fillable = ['booking_id', 'status', 'provider', 'payment_method', 'transaction_reference', 'merchant_reference', 'daraja_checkout_request_id', 'amount', 'phone_number', 'provider_response', 'failure_reason', 'paid_at', 'receipt_number', 'received_by', 'notes', 'metadata'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'payment_method' => \App\PaymentMethod::class,
            'amount' => 'integer',
            'provider_response' => 'array',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PaymentAudit::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
