# Typographie — RocketPi Design System

## Familles de polices

### font-display — Chakra Petch
- **Usage** : Titres, noms d'opérateurs, labels boutons, badges, toute l'UI gaming
- **Poids disponibles** : 400, 500, 600, 700
- **Caractère** : Tech militaire, légèrement italique naturel, lisibilité gaming
- **Classe Tailwind** : `font-display`
- **Fallback** : Rajdhani → system-ui

```tsx
<h1 className="font-display font-bold text-2xl tracking-wide uppercase">
  VEX — OPÉRATEUR LÉGENDAIRE
</h1>
```

### font-body — Inter
- **Usage** : Corps de texte, descriptions de lore, paragraphes, labels de formulaires
- **Poids disponibles** : 400, 500, 600, 700
- **Caractère** : Neutre, très lisible, conçu pour les écrans
- **Classe Tailwind** : `font-body`
- **Fallback** : system-ui → -apple-system

```tsx
<p className="font-body text-base text-text-medium leading-normal">
  En 2087, une station spatiale expérimentale baptisée RocketPi…
</p>
```

### font-mono — JetBrains Mono
- **Usage** : Codes d'opérateur, valeurs numériques, stats, pity counter, monnaie, codes de parrainage
- **Poids disponibles** : 400, 500, 600
- **Caractère** : Ligatures de programmation, chiffres tabulaires
- **Classe Tailwind** : `font-mono`
- **Fallback** : ui-monospace → monospace

```tsx
<span className="font-mono text-sm text-shard-300 tabular-nums">
  VX-01 · 1 234 SHARDS
</span>
```

## Échelle typographique

Base : **16px**. Ratios modulaires cohérents.

| Token | Tailwind | Valeur | Usage |
|-------|---------|--------|-------|
| `xs` | `text-xs` | 11px / 0.6875rem | Labels uppercase, fine print |
| `sm` | `text-sm` | 13px / 0.8125rem | Corps secondaire, captions |
| `base` | `text-base` | 15px / 0.9375rem | Corps principal |
| `md` | `text-md` | 17px / 1.0625rem | Corps mis en avant |
| `lg` | `text-lg` | 20px / 1.25rem | Sous-titres, noms d'opérateurs |
| `xl` | `text-xl` | 24px / 1.5rem | Titres de section |
| `2xl` | `text-2xl` | 32px / 2rem | Titres de page |
| `3xl` | `text-3xl` | 44px / 2.75rem | Titres hero |
| `display` | `text-display` | 64px / 4rem | Landing, moments épiques |

## Graisses

| Token | Tailwind | Valeur | Usage |
|-------|---------|--------|-------|
| `regular` | `font-regular` | 400 | Corps de texte |
| `medium` | `font-medium` | 500 | Labels, navigation |
| `semibold` | `font-semibold` | 600 | Boutons, titres secondaires |
| `bold` | `font-bold` | 700 | Titres principaux, noms |

## Interligne

| Token | Tailwind | Valeur | Usage |
|-------|---------|--------|-------|
| `tight` | `leading-tight` | 1.1 | Titres display, noms |
| `snug` | `leading-snug` | 1.25 | Titres de section |
| `normal` | `leading-normal` | 1.5 | Corps de texte |
| `relaxed` | `leading-relaxed` | 1.7 | Texte long, lore |

## Espacement des lettres

| Token | Tailwind | Valeur | Usage |
|-------|---------|--------|-------|
| `tight` | `tracking-tight` | -0.02em | Titres display larges |
| `normal` | `tracking-normal` | 0 | Corps de texte |
| `wide` | `tracking-wide` | 0.04em | Boutons, noms |
| `mega` | `tracking-mega` | 0.16em | Labels uppercase (catégories) |

## Patterns typographiques récurrents

### Label de catégorie (ex: faction, rareté)
```tsx
<span className="font-display font-semibold text-xs tracking-mega uppercase text-text-medium">
  FACTION
</span>
```

### Nom d'opérateur
```tsx
<h2 className="font-display font-bold text-lg tracking-wide uppercase text-text-high leading-tight">
  VEX
</h2>
```

### Valeur numérique (monnaie, stats)
```tsx
<span className="font-mono font-semibold text-sm tabular-nums text-text-high">
  1 234
</span>
```

### Titre de page
```tsx
<h1 className="font-display font-bold text-2xl tracking-tight uppercase text-text-high">
  ROSTER — COMMANDEMENT
</h1>
```

### Sous-titre lore
```tsx
<p className="font-body text-sm text-text-low leading-relaxed">
  Les Opérateurs exposés aux radiations des Shards ont développé des capacités hors-normes.
</p>
```
