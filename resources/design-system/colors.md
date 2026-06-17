# Palette de couleurs — RocketPi Design System

Toutes les couleurs sont en **OKLCH** (Oklab Lightness Chroma Hue) pour une uniformité perceptuelle. Les niveaux de luminosité sont cohérents entre les teintes.

## Brand — Shard (Cyan signature)

Hue de base : **188°** (cyan électrique). Tweakable via `--shard-hue`.

| Token | Tailwind | OKLCH | Usage |
|-------|---------|-------|-------|
| `shard-50`  | `bg-shard-50`  | `oklch(0.97 0.04 188)` | Tints sur fond clair |
| `shard-100` | `bg-shard-100` | `oklch(0.93 0.08 188)` | Hover très léger |
| `shard-200` | `bg-shard-200` | `oklch(0.88 0.12 188)` | Accent secondaire |
| `shard-300` | `bg-shard-300` | `oklch(0.82 0.15 188)` | Texte sur fond sombre |
| `shard-400` | `bg-shard-400` | `oklch(0.76 0.17 188)` | Icônes, bordures actives |
| **shard-500** | `bg-shard-500` | `oklch(0.70 0.18 188)` | **Base — CTA primaire** |
| `shard-600` | `bg-shard-600` | `oklch(0.62 0.17 188)` | Hover du CTA |
| `shard-700` | `bg-shard-700` | `oklch(0.52 0.14 188)` | Dégradé sombre |
| `shard-800` | `bg-shard-800` | `oklch(0.40 0.10 188)` | Background accent |
| `shard-900` | `bg-shard-900` | `oklch(0.28 0.07 188)` | Background profond |
| `shard-950` | `bg-shard-950` | `oklch(0.18 0.04 188)` | Plus sombre |

## Factions

### ORBIT — Ex-agence spatiale (Bleu profond)
Hue **220°** · Technophile, précis, hexagones.

| Token | Tailwind | OKLCH |
|-------|---------|-------|
| `orbit-100` | `bg-orbit-100` | `oklch(0.92 0.06 220)` |
| `orbit-300` | `bg-orbit-300` | `oklch(0.78 0.13 220)` |
| `orbit-500` | `bg-orbit-500` | `oklch(0.62 0.18 220)` |
| `orbit-700` | `bg-orbit-700` | `oklch(0.42 0.14 220)` |
| `orbit-900` | `bg-orbit-900` | `oklch(0.24 0.08 220)` |

### FERRO — Milice indépendante (Rouge rouille)
Hue **32°** · Brutaliste, industriel, triangles.

| Token | Tailwind | OKLCH |
|-------|---------|-------|
| `ferro-100` | `bg-ferro-100` | `oklch(0.92 0.05 32)` |
| `ferro-300` | `bg-ferro-300` | `oklch(0.78 0.13 32)` |
| `ferro-500` | `bg-ferro-500` | `oklch(0.66 0.18 32)` |
| `ferro-700` | `bg-ferro-700` | `oklch(0.50 0.16 32)` |
| `ferro-900` | `bg-ferro-900` | `oklch(0.28 0.09 32)` |

### VEIL — Société secrète (Violet + Vert acide)
Hue **290°** + accent acide **140°** · Mystérieux, courbes, infiltration.

| Token | Tailwind | OKLCH |
|-------|---------|-------|
| `veil-100` | `bg-veil-100` | `oklch(0.92 0.05 290)` |
| `veil-300` | `bg-veil-300` | `oklch(0.74 0.14 290)` |
| `veil-500` | `bg-veil-500` | `oklch(0.56 0.20 290)` |
| `veil-700` | `bg-veil-700` | `oklch(0.38 0.16 290)` |
| `veil-900` | `bg-veil-900` | `oklch(0.22 0.10 290)` |
| `veil-acid` | `bg-veil-acid` | `oklch(0.82 0.22 140)` |

## Raretés

Chaque rareté a une couleur foreground (texte/badge), background (fond du badge), et un glow CSS.

| Rareté | Tailwind fg | OKLCH fg | Usage |
|--------|-----------|---------|-------|
| **COMMON** | `text-rarity-common` | `oklch(0.72 0.01 250)` | Gris neutre |
| **RARE** | `text-rarity-rare` | `oklch(0.72 0.16 235)` | Bleu lumineux |
| **EPIC** | `text-rarity-epic` | `oklch(0.68 0.22 305)` | Violet vibrant |
| **LEGENDARY** | `text-rarity-legendary` | `oklch(0.82 0.16 85)` | Doré chaud |

### Glows (box-shadow) — via CSS var uniquement

```css
--shadow-glow-rare:   0 0 16px oklch(0.72 0.16 235 / 0.55);
--shadow-glow-epic:   0 0 20px oklch(0.68 0.22 305 / 0.60);
--shadow-glow-legend: 0 0 28px oklch(0.82 0.16 85  / 0.70);
```

## Couleurs sémantiques

| Rôle | Tailwind | OKLCH | Icône Lucide |
|------|---------|-------|-------------|
| Success | `text-success` | `oklch(0.72 0.18 155)` | `CheckCircle` |
| Warning | `text-warning` | `oklch(0.78 0.17 78)` | `AlertTriangle` |
| Danger | `text-danger` | `oklch(0.66 0.22 25)` | `XCircle` |
| Info | `text-info` | `oklch(0.68 0.16 235)` | `Info` |

## Backgrounds UI

Hiérarchie d'élévation — fond le plus sombre = le plus profond.

| Niveau | Tailwind | OKLCH | Usage |
|--------|---------|-------|-------|
| Base | `bg-bg-base` | `oklch(0.13 0.018 245)` | Body, arrière-plan page |
| Elev 1 | `bg-bg-elev1` | `oklch(0.17 0.020 245)` | Cards, panels |
| Elev 2 | `bg-bg-elev2` | `oklch(0.21 0.022 245)` | Modales, drawers |
| Elev 3 | `bg-bg-elev3` | `oklch(0.26 0.024 245)` | Tooltips, popovers |

## Texte

| Rôle | Tailwind | OKLCH | Usage |
|------|---------|-------|-------|
| High | `text-text-high` | `oklch(0.97 0.01 235)` | Titres, valeurs principales |
| Medium | `text-text-medium` | `oklch(0.78 0.02 235)` | Corps de texte |
| Low | `text-text-low` | `oklch(0.58 0.02 235)` | Captions, helpers |
| Dim | `text-text-dim` | `oklch(0.40 0.02 235)` | Éléments désactivés |
| On Shard | `text-text-on-shard` | `oklch(0.13 0.02 245)` | Texte sur bouton shard |

## Bordures

| Rôle | CSS Var | Usage |
|------|--------|-------|
| Subtle | `var(--color-border-subtle)` | Séparateurs, dividers |
| Default | `var(--color-border-default)` | Cards, inputs |
| Strong | `var(--color-border-strong)` | Focus rings, accentuation |
| Shard | `var(--color-border-shard)` | BannerCard, éléments brand |
