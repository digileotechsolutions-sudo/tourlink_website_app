<?php

namespace App\Models;

use App\OtpChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpChallenge extends TourLinkModel
{
    use Prunable;

    protected $fillable = ['user_id', 'channel', 'purpose', 'code_hash', 'expires_at', 'attempts', 'consumed_at'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'channel' => OtpChannel::class,
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'consumed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('expires_at', '<=', now())
            ->orWhere('consumed_at', '<=', now()->subDay());
    }
}
