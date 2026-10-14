<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSession extends TourLinkModel
{
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'session_fingerprint',
        'session_type',
        'session_id',
        'personal_access_token_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
