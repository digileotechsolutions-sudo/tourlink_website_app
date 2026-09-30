<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends TourLinkModel
{
    protected $fillable = ['booking_id', 'title'];

    public $timestamps = true;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
