<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use SoftDeletes, HasAutoSlug;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'abilities'     => 'array',
            'lore_unlocks'  => 'array',
            'is_available'  => 'boolean',
            'is_rate_up'    => 'boolean',
            'stat_hp'       => 'integer',
            'stat_damage'   => 'integer',
            'stat_mobility' => 'integer',
            'sort_order'    => 'integer',
        ];
    }

    public const LORE_UNLOCK_LEVELS = [0, 2, 5, 8, 10];

    /**
     * Récupère le lore par palier, en garantissant qu'il y a une ligne par
     * niveau attendu (0/2/5/8/10) — fallback vide pour les paliers manquants.
     *
     * @return array<int, array{level:int,title:string,snippet:?string}>
     */
    public function loreUnlocksWithDefaults(): array
    {
        $stored = collect($this->lore_unlocks ?? [])->keyBy('level');

        return collect(self::LORE_UNLOCK_LEVELS)
            ->map(fn (int $level) => [
                'level'   => $level,
                'title'   => $stored[$level]['title']   ?? self::defaultTitle($level),
                'snippet' => $stored[$level]['snippet'] ?? null,
            ])
            ->values()
            ->all();
    }

    private static function defaultTitle(int $level): string
    {
        return match ($level) {
            0  => 'Présentation',
            2  => 'Origines',
            5  => "L'incident Shardfall",
            8  => 'Vie privée',
            10 => 'Confidence ultime',
            default => "Palier {$level}",
        };
    }

    public const FACTIONS = ['ORBIT', 'FERRO', 'VEIL'];
    public const ROLES    = ['sniper', 'healer', 'scout', 'tank', 'explosives', 'assault', 'infiltrator', 'hacker'];
    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];
}
