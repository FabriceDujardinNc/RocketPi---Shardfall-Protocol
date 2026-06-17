# RocketPi — Shardfall Protocol · Design System

## Principe fondamental

Ce Design System est la **source de vérité unique** pour tous les choix visuels du projet. Aucune valeur codée en dur (couleur hex, espacement en px, police) ne doit apparaître dans les composants React. Tout passe par les tokens.

```
tokens.json  →  resources/css/app.css (@theme)  →  classes Tailwind / CSS vars
```

## Structure des fichiers

```
resources/design-system/
├── tokens.json        ← Source de vérité (éditer ici seulement)
├── README.md          ← Ce fichier
├── colors.md          ← Documentation visuelle de la palette
├── typography.md      ← Échelle typographique + usage
├── spacing.md         ← Grille 4px + espacements
└── components.md      ← Catalogue des composants disponibles

resources/css/
└── app.css            ← @theme Tailwind 4 + CSS custom properties

resources/js/
└── Components/
    ├── UI/            ← Composants de base (Button, Input, Card…)
    └── Game/          ← Composants jeu (OperatorCard, BannerCard…)
```

## Utilisation dans les composants React

### Via classes Tailwind (préféré)

```tsx
// ✅ Correct — utilise les tokens
<button className="bg-shard-500 text-text-on-shard font-display rounded-md px-4 py-2">
  Tirer ×10
</button>

// ❌ Interdit — valeur arbitraire
<button className="bg-[#00E5FF] text-[#0A0F1C] rounded-[6px] px-[16px] py-[8px]">
  Tirer ×10
</button>
```

### Via CSS custom properties (pour les cas JS inline)

```tsx
// ✅ Acceptable uniquement quand les classes Tailwind ne suffisent pas
<div style={{ borderColor: 'var(--color-border-shard)', boxShadow: 'var(--shadow-glow-shard)' }}>
```

## Modifier les tokens

1. Éditer `tokens.json` (source de vérité)
2. Mettre à jour les valeurs correspondantes dans `resources/css/app.css` → `@theme {}`
3. Mettre à jour la documentation dans `colors.md`, `typography.md`, etc.
4. Vérifier que les composants existants rendu correctement (Storybook)

**Ne jamais** modifier les valeurs directement dans les composants.

## Règles non-négociables

| Règle | Raison |
|-------|--------|
| Aucune couleur hex dans les composants | Cohérence garantie, refactoring facile |
| Aucune classe Tailwind arbitraire `[#hex]` | ESLint bloquera le build |
| Tout nouveau composant = story Storybook | Documentation vivante |
| Variante d'abord, nouveau composant ensuite | Éviter la prolifération |
| Mode sombre par défaut | Esthétique sci-fi, pas de `dark:` à gérer |

## Palette de couleurs — résumé

| Catégorie | Classe Tailwind | Usage |
|-----------|----------------|-------|
| Shard (brand) | `bg-shard-500` | CTA principaux, accents |
| ORBIT | `bg-orbit-500` | Faction ORBIT |
| FERRO | `bg-ferro-500` | Faction FERRO |
| VEIL | `bg-veil-500` | Faction VEIL |
| Légendaire | `bg-rarity-legendary` | Badge, glow |
| Épique | `bg-rarity-epic` | Badge, accent |
| Rare | `bg-rarity-rare` | Badge |
| Common | `bg-rarity-common` | Badge |
| Fond base | `bg-bg-base` | Body |
| Fond card | `bg-bg-elev1` | Cards, panels |
| Fond élevé | `bg-bg-elev2` | Modales, drawers |

## Typographie

| Variable | Famille | Usage |
|----------|---------|-------|
| `font-display` | Chakra Petch | Titres, boutons, labels UI |
| `font-body` | Inter | Corps de texte, descriptions |
| `font-mono` | JetBrains Mono | Codes, valeurs numériques, stats |

## Outils intégrés

- **CVA** (Class Variance Authority) — variantes de composants typées
- **Radix UI** — primitives accessibles (Modal, Dropdown, Popover)
- **Lucide React** — icônes
- **Framer Motion** — animations gacha, transitions
- **Storybook 8** — catalogue de composants (disponible à `/storybook`)

## Ajouter un composant

```bash
# 1. Créer le composant
touch resources/js/Components/UI/MyComponent.tsx

# 2. Créer la story Storybook
touch resources/js/Components/UI/MyComponent.stories.tsx

# 3. Documenter
# Ajouter une section dans components.md
```

Voir [components.md](./components.md) pour la liste complète et les exemples d'usage.
