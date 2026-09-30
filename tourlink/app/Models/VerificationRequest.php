<?php

namespace App\Models;

use App\VerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends TourLinkModel
{
    protected $fillable = ['user_id', 'type', 'status', 'notes', 'documents', 'reviewed_at'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'status' => VerificationStatus::class,
            'documents' => 'array',
            'created_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
