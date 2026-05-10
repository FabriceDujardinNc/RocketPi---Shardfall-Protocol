<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $guarded = [];

    public const TYPE_SHARDS = 'shards';            // Premium currency
    public const TYPE_CREDITS = 'credits';          // Soft currency
    public const TYPE_TICKETS_PREMIUM = 'tickets_premium';
    public const TYPE_TICKETS_STANDARD = 'tickets_standard';

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
