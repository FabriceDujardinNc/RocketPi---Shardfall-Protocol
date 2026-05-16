<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accessory extends Model
{
    use HasAutoSlug, HasFactory;

    protected $guarded = [];

    public const SLOTS = ['head', 'face', 'back', 'hands', 'legs'];
    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];
    public const GENERATION_STATUSES = ['pending', 'queued', 'generating', 'ready', 'failed'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class, 'operator_accessories')
            ->using(OperatorAccessoryPivot::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function isReady(): bool
    {
        return $this->generation_status === 'ready' && filled($this->base_model_url);
    }
}
