<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit immuable des mouvements de monnaie. Append-only.
 * Ne JAMAIS faire d'UPDATE/DELETE sur cette table.
 */
class Transaction extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount'        => 'integer',
            'balance_after' => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
