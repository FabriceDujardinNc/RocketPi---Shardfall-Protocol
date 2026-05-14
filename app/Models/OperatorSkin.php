<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorSkin extends Model
{
    use HasAutoSlug, HasFactory;

    protected $guarded = [];

    public const GENERATION_STATUSES = ['pending', 'queued', 'generating', 'ready', 'failed'];
    public const RARITIES            = ['common', 'rare', 'epic', 'legendary'];

    protected function casts(): array
    {
        return [
            'palette_json'       => 'array',
            'material_overrides' => 'array',
            'is_active'          => 'boolean',
            'is_default'         => 'boolean',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function isReady(): bool
    {
        return $this->generation_status === 'ready' && filled($this->texture_url);
    }
}
