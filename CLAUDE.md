# CLAUDE.md — Règles projet RocketPi: Shardfall Protocol

Instructions à respecter pour toute contribution sur ce projet, humaine ou IA.

## Stack & versions verrouillées

- **PHP 8.3+**, Laravel 13.7, Inertia 3.1, Sanctum 4.3, Horizon 5.46
- **React 19**, TypeScript strict, Tailwind v4 (`@theme`), Vite 8
- **Tests** : Pest 4.7 + RefreshDatabase + SQLite `:memory:`
- **Storybook 9** (Vite 8 incompatible avec Storybook 8)
- **Conteneurs** : Docker Compose dev + prod override, MySQL 8.4, Redis 8

Si une dépendance manque, l'ajouter via `composer require` / `npm install` —
ne JAMAIS retirer ou downgrader une dépendance sans accord explicite.

## Workflow obligatoire

### Avant chaque commit

1. `docker compose exec -T laravel-app php artisan test` doit être 100% vert
2. `docker compose exec -T vite npx tsc --noEmit` doit être clean sur les fichiers modifiés
3. Mettre à jour `docs/PROJECT_CHECKLIST.md` si une tâche est cochée/livrée

### Conventions de commits

Format Conventional Commits :

- `feat(scope): description` — nouvelle fonctionnalité
- `fix(scope): description` — correction de bug
- `docs(scope): description` — documentation pure
- `refactor(scope): description` — refacto sans changement fonctionnel
- `test(scope): description` — tests uniquement

**Ne jamais** ajouter `Co-Authored-By: Claude` ou attribution IA dans les commits
(préférence utilisateur explicite, persistante).

## Règles design system

### Composants UI

Chaque nouveau composant React dans `resources/js/Components/{UI,Game}/` DOIT s'accompagner :

1. **Story Storybook** dans le même dossier (`Button.stories.tsx` à côté de `Button.tsx`)
   - Couvrir au minimum toutes les variantes principales
   - Inclure une story `AllVariants` qui montre la matrice complète
2. **Doc Markdown** (optionnel pour composants triviaux, obligatoire pour composants stateful ou complexes)
3. Utiliser **cva** (`class-variance-authority`) pour les variants, jamais des `className` arbitraires

### Réutilisation > création

Avant de créer un composant, vérifier s'il existe déjà ou si une variante peut être ajoutée :

- `ls resources/js/Components/UI/` (20 composants existants)
- `ls resources/js/Components/Game/` (12 composants existants)
- `npm run storybook` pour visualiser

Si un design rapproché existe, ajouter une variant plutôt que dupliquer.

### Tokens design

Toutes les couleurs / espacements / typo passent par les **tokens** définis dans
`resources/css/app.css` via `@theme`. **Aucune valeur arbitraire** (`bg-[#abc123]`,
`p-[17px]`) n'est tolérée — utiliser les classes Tailwind générées depuis les tokens.

Mode clair : activer la classe `.theme-light` sur `<html>` (cf. `resources/js/theme.ts`).
Les tokens sont surchargés mais les couleurs de marque restent identiques.

## Règles backend

### Patterns à respecter

- **Transactions atomiques** : tout flux qui touche plusieurs tables liées (gacha,
  shop, transferts) doit être enroulé dans `DB::transaction` + `lockForUpdate` sur
  les rows critiques (PityCounter, Currency).
- **Services** : la logique métier va dans `app/Services/`, jamais dans les controllers.
- **Form Requests** : valider via classes dédiées dans `app/Http/Requests/`.
- **Policies + Gates** : autorisations via Policies (`Gate::policy`) ou helpers
  `$this->authorize()` dans les controllers.
- **Slugs partout** : nouvelle ressource exposée publiquement → `HasAutoSlug` trait
  + `getRouteKeyName('slug')`. Pas d'IDs dans les URLs publiques.

### 2FA admin

- Les routes `/admin/*` sont protégées par le middleware `2fa` qui force le setup
  TOTP au premier accès et un challenge à chaque nouvelle session.
- Ne jamais désactiver ce middleware sur une route admin.

### Logs & audit

- Tout débit/crédit de currency passe par `Transaction::create` avec `reason`
  explicite (`admin_grant`, `gacha_pull`, `shop_purchase`, etc.) + IP + description.
- Les actions admin sensibles (ban, grantCurrency) loguent l'admin ID dans la description.

## Règles tests

### Pattern Pest

- Tests Feature dans `tests/Feature/{Admin,Auth,Player,Services,Seo}/`
- `makeUser($attrs)` pour créer un user (auto-configure 2FA pour admins)
- `RefreshDatabase` actif globalement
- `beforeEach` global : `session()->put('2fa.passed', true)`
- Pas de dépendance entre tests — chacun doit pouvoir tourner en isolation

### Avant de livrer une feature

- 1 test par chemin nominal + 1 test par cas d'erreur
- Tester explicitement les permissions (utilisateur banni, role player vs admin)
- Pour les actions atomiques : tester qu'un rollback préserve l'état initial

## Anti-patterns interdits

- **Hardcoder** des valeurs arbitraires en CSS, préférer les tokens
- **Skip hooks** (`--no-verify`, `--no-gpg-sign`) sans accord explicite
- **`force-push`** sur main/master
- **Commits sans test** vert
- **Stockage de secrets** non chiffrés (utiliser cast `encrypted` Eloquent)
- **N+1 queries** : toujours `with()` ou `selectRaw` pour les relations en boucle
- **Exposer PII** dans les réponses publiques (pages SEO, sitemap, /top public)

## Mémoire externe

L'utilisateur travaille avec une mémoire persistante (`/root/.claude/projects/-root/memory/`).
Respecter en particulier :

- **No AI attribution in commits** (`feedback_no_ai_attribution.md`)

## Compteurs actuels (2026-05-12)

- **Tests Pest** : 264 / 937 assertions verts (26 fichiers)
- **Stories Storybook** : 32 / 32 composants couverts
- **Routes publiques SEO** : 5 (Landing, /lore, /lore/factions/{slug}, /lore/operators/{slug}, /top)
- **Migrations** : 30 (dernière : `add_two_factor_to_users_table`)
