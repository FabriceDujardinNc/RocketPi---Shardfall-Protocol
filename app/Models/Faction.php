<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faction extends Model
{
    protected $primaryKey = 'slug';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'color_hue' => 'integer',
        ];
    }

    /**
     * Tous les opérateurs de cette faction.
     * Pas de FK : la jointure se fait par slug (ORBIT, FERRO, VEIL),
     * cohérent avec l'enum existant sur operators.faction.
     */
    public function operators(): HasMany
    {
        return $this->hasMany(Operator::class, 'faction', 'slug');
    }
}
