<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends TourLinkModel
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_ADMIN = 'admin';

    protected $fillable = ['conversation_id', 'sender_id', 'sender_type', 'message', 'read_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
