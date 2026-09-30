<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorProfile extends TourLinkModel
{
    protected $fillable = ['user_id', 'company_name', 'slug', 'description', 'logo_url', 'website', 'years_active', 'response_rate'];

    protected function casts(): array
    {
        return [
            'years_active' => 'integer',
            'response_rate' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
