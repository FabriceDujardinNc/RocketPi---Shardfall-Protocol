# Catalogue des composants — RocketPi Design System

Avant de créer un nouveau composant, vérifier qu'il n'existe pas ici. Préférer une variante d'un composant existant à un nouveau.

---

## Composants UI de base (`resources/js/Components/UI/`)

### Button
**Props** : `variant`, `size`, `loading`, `disabled`, `icon`, `fullWidth`

| Variante | Usage |
|---------|-------|
| `primary` | Action principale (Tirer, Acheter) — fond shard-500 |
| `secondary` | Action secondaire — fond elev-2 |
| `ghost` | Liens, actions discrètes |
| `danger` | Suppression, ban admin |
| `shard` | CTA premium avec dégradé + glow |

| Taille | Height | Usage |
|--------|--------|-------|
| `sm` | 28px | Labels, actions dans cards |
| `md` | 38px | Défaut |
| `lg` | 48px | CTA héros, bannières |

```tsx
<Button variant="shard" size="lg" icon={<Sparkles />}>Tirer ×10</Button>
<Button variant="secondary" size="sm" loading>En cours…</Button>
<Button variant="danger">Bannir le joueur</Button>
```

---

### Input
**Props** : `label`, `hint`, `error`, `prefix`, `type`, `disabled`

```tsx
<Input label="Identifiant" prefix="@" placeholder="commandant" />
<Input label="Code de parrainage" hint="Format : XXX-XXXX-XXXX" error="Code invalide" />
```

---

### Select
**Props** : `label`, `options`, `value`, `onChange`, `error`

```tsx
<Select label="Faction" options={[
  { value: 'orbit', label: 'ORBIT' },
  { value: 'ferro', label: 'FERRO' },
  { value: 'veil',  label: 'VEIL' },
]} />
```

---

### Card
**Props** : `elevation` (1–4), `accent` (couleur de bordure), `scanlines`, `hoverLift`, `padding`

```tsx
<Card elevation={2} accent="var(--color-border-shard)" scanlines>
  Contenu premium
</Card>
<Card elevation={1} hoverLift onClick={handleClick}>
  Card cliquable
</Card>
```

---

### Badge
**Props** : `tone`, `size`, `dot`, `glow`

| Tone | Usage |
|------|-------|
| `neutral` | Info générale |
| `shard` | Brand, actif |
| `success` | Validé, en ligne |
| `warning` | Attention, bientôt expiré |
| `danger` | Erreur, banni |
| `info` | Informatif |

```tsx
<Badge tone="shard" dot>RATE-UP ACTIF</Badge>
<Badge tone="warning">EXPIRE DANS 2H</Badge>
```

---

### Modal
**Props** : `open`, `onClose`, `title`, `subtitle`, `footer`, `width`

```tsx
<Modal open={isOpen} onClose={close} title="Confirmation" width={480}
  footer={<><Button variant="ghost" onClick={close}>Annuler</Button><Button variant="danger">Confirmer</Button></>}>
  Voulez-vous vraiment bannir ce joueur ?
</Modal>
```

---

### Toast
Usage via hook `useToast()`. Portail monté au niveau app.

```tsx
const { toast } = useToast();
toast({ message: 'Tirage effectué !', tone: 'success', duration: 3000 });
```

---

### Tabs
**Props** : `tabs`, `activeTab`, `onChange`

```tsx
<Tabs tabs={['Hebdo', 'Mensuel', 'Collection', 'Compétitif']} activeTab={active} onChange={setActive} />
```

---

### Table
**Props** : `columns`, `rows`, `sortable`, `onRowClick`

---

### Pagination
**Props** : `currentPage`, `totalPages`, `onChange`

---

### Skeleton / Spinner / Progress
Loading states — utiliser `Skeleton` pour le chargement de cards, `Spinner` pour les actions inline.

---

### Avatar
**Props** : `src`, `name`, `size`, `rarity` (glow coloré)

---

## Composants jeu (`resources/js/Components/Game/`)

### OperatorCard
**Props** : `operator`, `width`, `focused`, `onClick`

L'objet `operator` doit avoir : `name`, `codename`, `faction` (ORBIT|FERRO|VEIL), `rarity` (common|rare|epic|legendary), `role`.

```tsx
<OperatorCard operator={vex} focused onClick={() => setSelected(vex)} />
```

**Variantes visuelles par rareté** :
- Common : bordure grise, pas de glow
- Rare : bordure bleue, glow bleu faible
- Epic : bordure violette, glow violet
- Legendary : bordure dorée, glow doré, shimmer animé + particules

---

### BannerCard
**Props** : `banner`

L'objet `banner` doit avoir : `tag`, `title`, `subtitle`, `featured` (nom opérateur), `countdown`.

```tsx
<BannerCard banner={{ tag: 'SIGNAL SHARD · ÉVÉNEMENT', title: 'OPÉRATION HEXFALL', subtitle: 'VEX RATE-UP ×3', featured: 'VEX', countdown: '6j 14h 22m' }} />
```

---

### RarityBadge
**Props** : `rarity`, `size`

```tsx
<RarityBadge rarity="legendary" />
<RarityBadge rarity="epic" size="sm" />
```

---

### FactionBadge
**Props** : `faction`, `size`

```tsx
<FactionBadge faction="ORBIT" />
<FactionBadge faction="VEIL" size="sm" />
```

---

### PityCounter
**Props** : `current`, `max` (défaut 80), `softPity` (défaut 60)

```tsx
<PityCounter current={64} />
```

Affiche : barre de progression, marqueur de soft pity à 60, message "SOFT PITY ACTIVE" si current >= 60.

---

### GachaPullAnimation
**Props** : `pulls`, `onClose`

Reçoit un tableau de 1 ou 10 opérateurs après tirage. Overlay plein écran avec révélation séquentielle. Déclenché par `BannerScreen`.

---

### AffinityMeter
**Props** : `value`, `max` (défaut 10), `label`

```tsx
<AffinityMeter value={7} label="Affinité VEX" />
```

---

### CurrencyDisplay
**Props** : `amount`, `currency` (shards|credits|tickets), `delta`

```tsx
<CurrencyDisplay amount={1234} currency="shards" />
<CurrencyDisplay amount={8} currency="tickets" delta={+1} />
```

---

### LeaderboardRow
**Props** : `rank`, `player`, `score`, `highlight` (si c'est le joueur courant)

---

### RankBadge
**Props** : `tier` (Bronze|Silver|Gold|Platinum|Diamond|Master)

---

### MissionCard
**Props** : `mission`, `progress`, `onClaim`

---

### BattlePassNode
**Props** : `tier`, `reward`, `claimed`, `unlocked`

---

### AffinityMeter
Voir ci-dessus.

---

## Règles de composition

1. **Vérifier avant de créer** : parcourir cette liste
2. **Variante > nouveau composant** : un bouton "Tirer 10x" et un bouton "Acheter pack" = même `Button` avec variantes
3. **CVA pour les variantes** : utiliser `cva()` de `class-variance-authority`
4. **Radix pour l'accessibilité** : Modal → `Dialog`, Dropdown → `DropdownMenu`, Tooltip → `Tooltip`
5. **Story obligatoire** : tout composant sans story Storybook est considéré non documenté
6. **Markdown obligatoire** : ajouter une section dans ce fichier

## Ajouter un composant — checklist

- [ ] Créer `resources/js/Components/UI/MonComposant.tsx`
- [ ] Exporter depuis `resources/js/Components/UI/index.ts`
- [ ] Créer `resources/js/Components/UI/MonComposant.stories.tsx`
- [ ] Ajouter une section dans ce fichier (props, usage, variantes)
- [ ] Vérifier : aucune valeur hardcodée (hex, px arbitraire)
- [ ] Vérifier : accessibilité (aria, focus ring, keyboard nav)
