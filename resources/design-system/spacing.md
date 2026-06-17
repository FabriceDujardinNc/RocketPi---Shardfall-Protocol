# Espacements — RocketPi Design System

## Système de grille

Base : **4px (0.25rem)**. Toutes les valeurs d'espacement sont des multiples de 4.

| Token | Tailwind | px | rem | Usage typique |
|-------|---------|-----|-----|---------------|
| `0` | `p-0` `m-0` | 0 | 0 | Reset |
| `1` | `p-1` `m-1` | 4px | 0.25rem | Gap minimal, séparateurs fins |
| `2` | `p-2` `m-2` | 8px | 0.5rem | Padding icônes, gap inline |
| `3` | `p-3` `m-3` | 12px | 0.75rem | Padding bouton sm, gap badges |
| `4` | `p-4` `m-4` | 16px | 1rem | Padding card, gap standard |
| `5` | `p-5` `m-5` | 20px | 1.25rem | Padding bouton lg |
| `6` | `p-6` `m-6` | 24px | 1.5rem | Padding modal, section |
| `8` | `p-8` `m-8` | 32px | 2rem | Espacement entre sections |
| `10` | `p-10` `m-10` | 40px | 2.5rem | Header height |
| `12` | `p-12` `m-12` | 48px | 3rem | Sections majeures |
| `16` | `p-16` `m-16` | 64px | 4rem | Espacement grand |
| `20` | `p-20` `m-20` | 80px | 5rem | Hero sections |
| `24` | `p-24` `m-24` | 96px | 6rem | Très grand espacement |

## Grille de mise en page

### Layout principal
```
max-w-screen-xl  = 1280px (contenu principal)
max-w-screen-2xl = 1536px (layouts étendus)
px-4             = 16px padding latéral mobile
px-6             = 24px padding latéral tablet
px-8             = 32px padding latéral desktop
```

### Grille de cards (Roster, Collection)
```tsx
// Grille d'opérateurs — responsive
<div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
  <OperatorCard />
</div>
```

### Sidebar + contenu (Dashboard)
```tsx
<div className="flex">
  <aside className="w-64 shrink-0" />      {/* 256px */}
  <main className="flex-1 min-w-0 p-8" />
</div>
```

## Hauteurs standard

| Élément | Valeur | Tailwind |
|---------|--------|---------|
| Bouton sm | 28px | `h-7` |
| Bouton md | 38px | `h-9` (approx) |
| Bouton lg | 48px | `h-12` |
| Input | 40px | `h-10` |
| Navbar | 64px | `h-16` |
| Card portrait (opérateur) | 210px | `h-[210px]` (exception autorisée pour images) |
| Banner card | 220px min | `min-h-[220px]` |

Note : les hauteurs de média (portraits d'opérateurs) sont les seules exceptions autorisées aux valeurs arbitraires.

## Gap standards

| Contexte | Gap | Tailwind |
|----------|-----|---------|
| Bouton (icône + label) | 6–10px | `gap-2` |
| Badge inline | 6px | `gap-1.5` |
| Éléments dans une card | 8px | `gap-2` |
| Cards dans une grille | 16px | `gap-4` |
| Sections dans un layout | 24–32px | `gap-6` `gap-8` |

## Radius — rappel

| Token | Tailwind | Valeur | Usage |
|-------|---------|--------|-------|
| `sm` | `rounded-sm` | 3px | Badges, tags |
| `md` | `rounded-md` | 6px | Boutons, inputs |
| `lg` | `rounded-lg` | 10px | Cards |
| `xl` | `rounded-xl` | 16px | Modales, drawers |
| `full` | `rounded-full` | 999px | Avatars, pills |
