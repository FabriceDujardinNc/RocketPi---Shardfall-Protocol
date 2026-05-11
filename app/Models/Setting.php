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

    private const CACHE_KEY = 'app:settings:all';
    private const CACHE_TTL = 600; // 10 min — suffisant, invalidate sur save

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Lit une valeur typée. Mis en cache pour éviter un hit DB par requête.
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember(self::CACHE_KEY, self::CACHE_TTL,
            fn () => self::all()->keyBy('key')->all());

        if (! isset($all[$key])) {
            return $default;
        }

        return self::decode($all[$key]->value, $all[$key]->type);
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
