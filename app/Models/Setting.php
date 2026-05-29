<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public const TYPES = ['bool', 'string', 'int', 'json'];

    // `:v2` — on ne cache plus des modèles Eloquent (sérialisés, ils peuvent
    // revenir en __PHP_Incomplete_Class à la désérialisation Redis) mais un
    // simple tableau de primitives. Le suffixe versionné fait ignorer toute
    // ancienne entrée corrompue, qui expire ensuite d'elle-même.
    private const CACHE_KEY = 'app:settings:all:v2';
    private const CACHE_TTL = 600; // 10 min — suffisant, invalidate sur save

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Lit une valeur typée. Mis en cache pour éviter un hit DB par requête.
     * Le cache ne contient que des primitives : `[key => ['value','type']]`.
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => self::all()
            ->mapWithKeys(fn (self $s) => [$s->key => ['value' => $s->value, 'type' => $s->type]])
            ->all());

        if (! isset($all[$key])) {
            return $default;
        }

        return self::decode($all[$key]['value'], $all[$key]['type']);
    }

    /**
     * Écrit une valeur en l'encodant selon le type stocké.
     */
    public static function put(string $key, mixed $value, string $type = 'string', ?string $label = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::encode($value, $type),
                'type'  => $type,
                'label' => $label,
            ]
        );
    }

    private static function decode(?string $raw, string $type): mixed
    {
        if ($raw === null) return null;
        return match ($type) {
            'bool'   => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'int'    => (int) $raw,
            'json'   => json_decode($raw, true),
            default  => $raw,
        };
    }

    private static function encode(mixed $value, string $type): string
    {
        return match ($type) {
            'bool'  => $value ? '1' : '0',
            'int'   => (string) (int) $value,
            'json'  => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }

    public function typedValue(): mixed
    {
        return self::decode($this->value, $this->type);
    }
}
