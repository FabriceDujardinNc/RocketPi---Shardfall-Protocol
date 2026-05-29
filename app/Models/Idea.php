<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    use HasAutoSlug;

    public const STATUS_OPEN     = 'open';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DONE     = 'done';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_DONE,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'votes_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function slugSource(): string
    {
        return $this->title ?? '';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IdeaVote::class);
    }

    /**
     * True si l'utilisateur a déjà voté pour cette idée.
     */
    public function isVotedBy(?User $user): bool
    {
        if ($user === null) return false;
        return $this->votes()->where('user_id', $user->id)->exists();
    }
}
