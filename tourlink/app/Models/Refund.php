<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends TourLinkModel
{
    protected $fillable = ['payment_id', 'amount', 'reason'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
