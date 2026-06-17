<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Génère automatiquement un slug unique pour le model à partir d'une source
 * (name / title / codename / autre, défini par `slugSource()`).
 *
 * Les models qui l'utilisent doivent :
 *  - avoir une colonne `slug` nullable + index unique
 *  - implémenter `slugSource(): string` (optionnel, defaults : name → title → codename)
 *  - utiliser `getRouteKeyName()` pour exposer slug aux routes implicit-bound
 *
 * Stratégie de génération :
 *  - on régénère uniquement si slug est vide OU si la source change
 *  - en cas de collision on suffixe `-2`, `-3` jusqu'à dispo
 *  - les routes admin gardent la stabilité du slug : un renommage du nom
 *    n'invalide les bookmarks que si le slug d'origine ne reflétait plus
 *    l'item (collision déjà gérée).
 */
trait HasAutoSlug
{
    public static function bootHasAutoSlug(): void
    {
        static::saving(function (Model $model) {
            /** @var Model&self $model */
            $source = method_exists($model, 'slugSource')
                ? $model->slugSource()
                : ($model->name ?? $model->title ?? $model->codename ?? null);

            if (! $source) return;

            $needsSlug = empty($model->slug);
            if (! $needsSlug) return;

            $base = Str::slug($source) ?: Str::lower(Str::random(8));
            $slug = $base;
            $suffix = 2;

            while (
                static::query()
                    ->where('slug', $slug)
                    ->when($model->getKey(), fn ($q, $k) => $q->where($model->getKeyName(), '!=', $k))
                    ->exists()
            ) {
                $slug = $base . '-' . $suffix++;
            }

            $model->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
