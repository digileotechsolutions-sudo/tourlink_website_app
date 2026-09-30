<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAudit extends TourLinkModel
{
    protected $fillable = ['payment_id', 'event_type', 'checkout_request_id', 'callback_hash', 'payload'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
