# RocketPi — Suivi du projet

**Légende :** `[x]` fait · `[~]` partiel · `[ ]` à faire

> Ce document suit la spec complète RocketPi — Shardfall Protocol. Chaque case correspond à une demande explicite de la spec. Cocher au fur et à mesure pour garder une vision d'ensemble.

---

## 0. Contexte fixé (référence — ne pas modifier)

- [x] Titre : RocketPi — Shardfall Protocol
- [x] Lore 2087 / Shards / Opérateurs
- [x] 3 factions de départ : ORBIT, FERRO, VEIL
- [x] FPS hero-shooter navigateur + gacha
- [x] Maintenable solo / petite équipe / budget minimal

---

## 1. Stack technique (10 mai 2026)

### Backend / Méta
- [x] Laravel 13
- [x] PHP 8.5
- [x] Inertia.js 3.1
- [x] React 19 + TypeScript
- [x] Tailwind CSS 4
- [x] Laravel Sanctum (composer.json `^4.3`, migration personal_access_tokens créée)
- [x] Laravel Horizon (composer.json `^5.46`)
- [x] **Laravel Policies + Gates configurés** — `AppServiceProvider::boot()` enregistre 6 gates (`access-admin`, `manage-content`, `view-gacha-logs`, `flag-referrals`, `reset-leaderboards`, `change-roles`) + `Gate::before` super_admin (sauf `ban`/`promote`). `UserPolicy` (view, update, ban, promote) et `ReferralRewardPolicy` (claim) câblées dans `AdminPlayerController::ban/unban` et `ReferralController::claim`. 10 tests Pest couvrent la matrice rôles + bénéficiaire.
- [x] Vite 8 (compat plugin-react 6.x)
- [x] Panel admin : Inertia + React main (pas de Filament)

### Données
- [x] MySQL 8.4 LTS (Docker)
- [x] phpMyAdmin (Docker)
- [x] Redis 8 (Docker)

### Jeu (phase 4+)
- [ ] Unity 6 LTS WebGL (build 6000.3.12f1+)
- [ ] Photon Fusion (gratuit ≤ 100 CCU)

### Tooling
- [x] Node.js 22 LTS
- [x] **Pest 4.7** installé + **19 fichiers de tests (171 tests / 503 assertions, tous au vert)** — couverture services métier + policies + admin CRUD + pages joueur. Ajout : **AdminAchievementControllerTest (6)** et **AdminEventControllerTest (5)**. Inclus aussi : XpService / DailyLoginService (+ fix SQLite) / GachaService / MissionService / AffinityService / AchievementService / ShopService / BattlePassService / LeaderboardService (Redis DB 15 isolée) / ReferralService (first-purchase) / AuthorizationTest (Policies + Gates) / AdminOperatorControllerTest / AdminBannerControllerTest (somme taux = 1) / AdminMissionControllerTest / AdminBattlePassControllerTest (anti-overlap dates) / AdminDailyLoginRewardControllerTest / AdminFactionControllerTest / Player FactionPageTest.
- [x] **Isolation tests durcie** — `tests/bootstrap.php` force `$_SERVER`/`$_ENV`/`putenv` avant l'autoload (PHPUnit `<env force>` ne touche pas `$_SERVER`, donc Docker injection prenait le dessus → les tests `RefreshDatabase` essuyaient la dev MySQL).
- [x] **URLs SEO-friendly via slug** — refactor 11/05/2026 : trait `App\Concerns\HasAutoSlug` + colonne `slug` sur operators/banners/missions/battle_passes/events/leaderboard_seasons (+ unique index), `getRouteKeyName` override sur ces models et sur Achievement (via `key`). Toutes les URLs admin et joueur passent maintenant par slug : `/admin/operators/vex`, `/gacha/signal-shard-standard`, `/admin/battle-passes/saison-1-eveil-des-shards`, etc. Génération automatique sur save, collision-safe (suffixe `-2`/`-3`).
- [x] Vitest (déps installées)
- [x] **Storybook 9 + premières stories** — `.storybook/main.ts` + `preview.tsx`, 3 stories : Button (5 variants × 3 sizes + icon/loading), OperatorCard (4 raretés + roster grid), BattlePassNode (locked/unlocked/claimed/premium + roadmap). Storybook 8 ne supporte pas Vite 8 (peer dep `^4 || ^5 || ^6`), Storybook 9 installé avec `--legacy-peer-deps` ; `npm run build-storybook` passe (9.5s).
- [x] Code versionné GitHub
- [x] Procédure `git pull && docker compose up -d --build`
- [x] **ESLint 9** flat config — interdit `bg-[#hex]`, `mt-[13px]`, inline `style={{color:'#hex'}}` (no-restricted-syntax)
- [x] **Stylelint 17** — interdit `color-no-hex` + `color-named` partout sauf source DS
- [x] Scripts `npm run lint`, `lint:fix`, `stylelint`

---

## 2. Infrastructure VPS

- [x] VPS provisionné (`187.77.144.82`)
- [x] Domaine `rocketpi.pro` (DNS → VPS)
- [x] Domaine `rocketpi-test.pro` (DNS → VPS)
- [ ] Sous-domaine `db.rocketpi.pro`
- [ ] Sous-domaine `db.rocketpi-test.pro`
- [x] Docker + Docker Compose installés sur VPS
- [x] Réseau Docker `proxy` créé
- [x] **Reverse proxy déployé en façade** — Caddy 2.8 dédié dans `/root/proxy/` (Caddyfile + docker-compose), réseau `proxy` externe (NB : choix Caddy au lieu de Nginx Proxy Manager pour TLS+ACME automatique sans UI)
- [x] SSL Let's Encrypt automatique sur 4 domaines (`rocketpi.pro`, `www.rocketpi.pro`, `rocketpi-test.pro`, `www.rocketpi-test.pro`) — certs réutilisés depuis l'ancien volume `caddy_data` pour éviter le rate-limit LE
- [ ] Stack prod déployée dans `/opt/rocketpi-prod/` (actuellement `/root/RocketPi-Shardfall-Protocol/`, en mode dev override — à migrer)
- [ ] Stack dev déployée dans `/opt/rocketpi-test/` (pas encore de stack dev séparée)
- [ ] phpMyAdmin sécurisé : auth HTTP basique sur dev
- [ ] phpMyAdmin sécurisé : auth HTTP + IP whitelist sur prod
- [ ] Sauvegardes auto MySQL (cron dump) — script + crontab fournis dans `docs/VPS_SETUP.md` §8, reste à activer sur le VPS
- [ ] Sauvegardes auto Redis (snapshots) — idem (RDB déjà actif, copie cron à scheduler)
- [x] **`docs/VPS_SETUP.md` rédigé pas à pas** — 12 sections (DNS, système, UFW, Caddy, stack appli, build assets, seeders, sauvegardes, phpMyAdmin durci, logs, déploiement, checklist post-install)

---

## 3. Architecture Docker (par stack)

### Compose files
- [x] `docker-compose.yml` (base partagée prod/dev)
- [x] `docker-compose.override.yml` (auto-mergé en dev)
- [x] `docker-compose.prod.yml` (overrides prod)
- [x] `docker/Dockerfile.laravel` (prod)
- [x] `docker/Dockerfile.laravel.dev` (dev sans build)
- [x] `docker/php.ini`
- [x] `docker/supervisord.conf`
- [x] `docker/nginx/laravel.conf` (avec `.br`/`.gz` Unity WebGL)
- [x] `docker/mysql/my.cnf`

### Services Docker
- [x] `laravel-app` (PHP-FPM 8.5 + extensions)
- [x] `nginx` (port `127.0.0.1:8000:80` exposé en dev)
- [x] `mysql` (MySQL 8.4 LTS)
- [x] `phpmyadmin`
- [x] `redis` (Redis 8)
- [x] `queue-worker` (Horizon)
- [x] `scheduler` (artisan schedule:run)
- [x] (dev) `mailpit` capture emails (`localhost:8025`)
- [x] (dev) `vite` HMR sur `localhost:5173`
- [x] (dev) `installer` (one-shot : storage perms + composer + npm + APP_KEY + migrate au boot)

### Boot validé en local (2026-05-10)
- [x] Build OK (Dockerfiles refactorés avec `mlocati/php-extension-installer` — fix `cp: can't stat 'modules/*'`)
- [x] MySQL 8.4 healthy (retiré `default-authentication-plugin`, `expire_logs_days`, `query_cache_type` — deprecated/retirés en 8.4)
- [x] Installer exécute `composer install`, `npm install`, `chmod 777 storage`, `migrate`
- [x] App accessible sur `http://localhost:8000`
- [x] Vite HMR connecté (host=`0.0.0.0`, hmr.host=`localhost`, polling pour WSL)
- [x] Réseau Docker `proxy` créé, `internal: false` en dev (besoin internet pour composer/npm)

### Extensions PHP (laravel-app)
- [x] pdo_mysql
- [x] redis (PECL)
- [x] intl
- [x] gd
- [x] opcache
- [x] zip
- [x] bcmath
- [x] mbstring
- [x] pcntl

### Sécurité réseau Docker
- [x] Réseau interne (DB et Redis non exposés internet)
- [x] Volumes persistants : MySQL, Redis, storage Laravel
- [x] (dev) ports DB/Redis exposés `127.0.0.1` uniquement

---

## 4. Design System

### Fichiers fondation
- [x] `resources/design-system/tokens.json`
- [x] `resources/design-system/README.md`
- [x] `resources/design-system/colors.md`
- [x] `resources/design-system/typography.md`
- [x] `resources/design-system/spacing.md`
- [x] `resources/design-system/components.md`
- [x] `resources/css/app.css` (Tailwind 4 `@theme`)

### Tokens
- [x] Couleur marque : Shard cyan signature
- [x] Couleur faction ORBIT (bleu hue 220)
- [x] Couleur faction FERRO (rouge-rouille hue 32)
- [x] Couleur faction VEIL (violet hue 290 + acide)
- [x] Raretés common / rare / epic / legendary
- [x] UI : background, surface, text, border, success, warning, danger, info
- [x] Mode sombre par défaut (sci-fi)
- [ ] Mode clair (option)
- [x] `font-display` (Chakra Petch — équivalent Orbitron/Rajdhani gaming)
- [x] `font-body` (Inter)
- [x] `font-mono` (JetBrains Mono)
- [x] Échelle modulaire typographique
- [x] Spacing 4px (0.25rem) échelle 0–64
- [x] Radius
- [x] Ombres / élévations
- [x] Animations (durations + easings)
- [x] Z-index scale

### Outils intégrés
- [x] CVA (Class Variance Authority) installé
- [x] Radix UI primitives installées (dialog, dropdown, popover, tabs, tooltip)
- [x] Lucide React installé
- [x] Framer Motion installé
- [x] **ESLint 9 configuré** — `eslint.config.js` flat, règles `no-restricted-syntax` interdisant `bg-[#hex]`, `mt-[13px]`, inline `style={{color:'#hex'}}`. Lint 0 erreur.
- [x] **Stylelint 17 configuré** — `.stylelintrc.json`, `color-no-hex` + `color-named` actifs hors source DS. Lint 0 erreur.

### Règles non-négociables (appliquer au code)
- [x] **Aucune couleur hex hors tokens.json** — enforced par Stylelint + ESLint (échec build CI à venir)
- [x] **Aucune valeur arbitraire `mt-[13px]`, `bg-[#FF5733]`** — enforced par ESLint
- [ ] Toute story Storybook obligatoire pour nouveau composant
- [ ] Toute doc Markdown obligatoire pour nouveau composant
- [ ] Réutiliser variantes existantes plutôt que créer un composant

---

## 5. Bibliothèque de composants

### UI de base — `resources/js/Components/UI/` (20/20 stubs)
- [x] Button (CVA, 5 variantes, 3 tailles, loading state)
- [x] Input (CVA, 3 tailles, état invalid)
- [x] Select (native + design tokens)
- [x] Checkbox (native + design tokens)
- [x] Radio (native + design tokens)
- [x] Toggle (custom switch UI)
- [x] Modal (Radix Dialog)
- [x] Drawer (Radix Dialog, side right/left)
- [x] Tooltip (Radix Tooltip)
- [x] Toast (stub Phase 1, à compléter Phase 2)
- [x] Tabs (Radix Tabs)
- [x] Card (CVA, 3 variantes, padding configurable)
- [x] Badge (6 variantes sémantiques)
- [x] Avatar (avec fallback initiales)
- [x] Table (composants Table.Thead/Tbody/Tr/Th/Td)
- [x] Pagination (links Inertia)
- [x] Skeleton (animate-pulse)
- [x] Spinner (Loader2 Lucide)
- [x] Progress (4 variantes)
- [x] Alert (4 variantes avec icônes Lucide)

### Game spécifiques — `resources/js/Components/Game/` (12/12 stubs)
- [x] OperatorCard (variantes par rareté avec glow)
- [x] BannerCard (avec mode featured)
- [x] GachaPullAnimation (Framer Motion, reveal séquentiel)
- [x] PityCounter (avec soft pity highlight)
- [x] RarityBadge (4 raretés)
- [x] FactionBadge (3 factions)
- [x] LeaderboardRow (mise en avant currentUser)
- [x] RankBadge (top 1/2/3 stylisés)
- [x] MissionCard (progress + claim button)
- [x] BattlePassNode (free/premium, claimed states)
- [x] AffinityMeter (gradient shard)
- [x] CurrencyDisplay (3 monnaies avec icônes)

### Storybook
- [x] **Configuration `.storybook/`** — `main.ts` (glob `Components/**/*.stories.tsx`) + `preview.tsx` (Tailwind 4 chargé, backgrounds dark/elev1/light)
- [x] **Première story** (`Button.stories.tsx` — 5 variants × 3 sizes + icon + loading + roster comparatif)
- [~] Stories pour tous les composants — 3 stubs (Button, OperatorCard, BattlePassNode), reste 29 composants à couvrir

### MCP Design System (phase 3+)
- [ ] `list_components()`
- [ ] `get_design_tokens()`
- [ ] `validate_component(code)`
- [ ] `suggest_component(intent)`
- [ ] `get_patterns(category)`

---

## 6. Base de données — Migrations

- [x] users (avec referral_code, referred_by_user_id, role, account_level, account_xp, display_name, avatar_url, last_active_at, is_banned, ban_reason, banned_at)
- [x] cache (Laravel)
- [x] jobs (Laravel)
- [x] personal_access_tokens (Sanctum)
- [x] operators (+ `lore_unlocks` JSON par palier d'affinité, ajout 11/05/2026 — éditable depuis l'admin)
- [x] **factions** (slug PK ORBIT/FERRO/VEIL, name, tagline, lore, color_hue OKLCH, accent_class, banner_image_url, icon_url — ajout 11/05/2026)
- [x] banners
- [x] gacha_pulls (audit-legal, immutable)
- [x] player_operators
- [x] currencies
- [x] transactions (append-only)
- [x] referrals + referral_rewards
- [x] daily_logins
- [x] **daily_login_rewards** (day_number unique 1-365, rewards JSON, is_milestone, label — sort la const PHP vers la BDD, admin-éditable, ajout 11/05/2026)
- [x] missions + mission_progress
- [x] battle_passes + battle_pass_tiers + battle_pass_progress
- [x] operator_affinities
- [x] achievements + user_achievements
- [x] events
- [x] leaderboard_seasons + leaderboard_entries + leaderboard_rewards
- [x] guilds + guild_members
- [x] pity_counters

---

## 7. Seeders

- [x] `DatabaseSeeder` (orchestrateur)
- [x] `AdminUserSeeder` — admin via .env + **Fabrice** (`fabricedujardin873@gmail.com` / `dev1234`, super_admin) + 3 comptes test dev (admin, player, banned) seedés en local uniquement
- [x] **`FactionSeeder`** (ORBIT/FERRO/VEIL avec lore + couleur hue OKLCH)
- [x] `OperatorSeeder` (8 opérateurs avec lore + abilities complets + **lore_unlocks 5 paliers backfillés**)
- [x] `BannerSeeder` (bannière permanente + 1 événementielle)
- [x] `MissionSeeder` (5 missions ≥ 3 demandées)
- [x] `LeaderboardSeasonSeeder` (6 saisons ≥ 1 demandée)
- [x] `LeaderboardRewardSeeder` (36 rewards = 6 tiers × 6 saisons actives)
- [x] **`DailyLoginRewardSeeder`** (paliers J1/J7/J15/J30 reproduits depuis l'ancienne const PHP)

### Modèles Eloquent (créés au fil du seeding/gacha)
- [x] `User` (avec MustVerifyEmail + helpers + booted hook referral_code)
- [x] `Operator` (avec casts JSON `abilities` + booleans)
- [x] `Banner` (avec casts JSON `rate_up_operators` + decimals taux + datetimes)
- [x] `Mission` (avec casts JSON `rewards` + booleans + datetimes)
- [x] `LeaderboardSeason` (avec casts datetimes + booleans)
- [x] `Currency` (constantes TYPE_SHARDS/CREDITS/TICKETS_*, relation user)
- [x] `Transaction` (immuable, no UPDATED_AT, morphTo reference)
- [x] `GachaPull` (immuable, audit légal, relations user/banner/operator)
- [x] `PlayerOperator` (relation user/operator, duplicate_count)
- [x] `PityCounter` (relation user/banner)
- [ ] Autres modèles (Referral, ReferralReward, DailyLogin, MissionProgress, BattlePass*, OperatorAffinity, Achievement, Event, Guild*, LeaderboardEntry, LeaderboardReward) — à créer au fur et à mesure

---

## 8. Routes

### Web — `routes/web.php`
- [x] Public : `/`, `/r/{code}` (stocke en session), login, register
- [x] Joueur (auth+verified+not.banned) : /dashboard, /collection, /gacha, /shop, /leaderboard, /missions, /battlepass, /referral, /profile, /play
- [x] Admin (auth+admin) : /admin/*
- [x] Tous les controllers Player (10) et Admin (9) créés (stubs fonctionnels)

### API — `routes/api.php`
- [x] `/health`
- [x] `POST /auth/token` (rate-limited 10/min)
- [x] Routes Sanctum : profile, gacha/pull (60/h), missions, leaderboard, Unity session/match

### Middleware
- [x] `EnsureUserIsAdmin`
- [x] `EnsureUserIsNotBanned`
- [x] Aliases enregistrés (`admin`, `not.banned`) dans `bootstrap/app.php`

---

## 9. Authentification (Phase 1)

### Controllers (à créer)
- [x] `Auth\AuthController` (login, register, reset, verify, logout — unifié)
- [ ] Passkey (optionnel)

### Pages Inertia — `resources/js/Pages/Auth/`
- [x] Login.tsx
- [x] Register.tsx
- [x] ForgotPassword.tsx
- [x] ResetPassword.tsx
- [x] VerifyEmail.tsx

### Système de rôles
- [x] Migration `role` (user / admin / super_admin)
- [x] Middleware `admin`
- [x] Constantes + helpers (`isAdmin`, `isSuperAdmin`) sur User
- [x] **Policies + Gates** — UserPolicy + ReferralRewardPolicy + 6 gates dans AppServiceProvider, appliquées sur ban/unban/claim
- [ ] 2FA admin (recommandée)

### Génération auto
- [x] Code parrainage `XXX-XXXX-XXXX` à l'inscription (booted hook sur User model)
- [x] **Slug d'URL profil** depuis `display_name` → `Str::slug(...)` avec collision suffix `-2`/`-3` (booted hook `saving`). Route `/profile/{user:slug}` (scoped binding, n'impacte pas les routes admin par ID). Le slug est régénéré quand le `display_name` change ; les anciens liens partagés deviennent caducs (trade-off explicité dans la page d'édition).
- [x] **Pseudo unique** : contrainte DB `unique` sur `users.name` (à l'inscription) **et** `users.display_name` (à l'édition profil). Validation Laravel renvoie un message FR si le pseudo est déjà pris.

### Redirections post-auth
- [x] **`/login`, `/register`, `/forgot-password`, `/reset-password`** : si user connecté, redirige vers `/admin` (admin) ou `/dashboard` (joueur) via `redirectUsersTo` configuré dans `bootstrap/app.php`
- [x] **`/`** (landing) : redirige vers `/admin` ou `/dashboard` si user connecté
- [x] `redirectGuestsTo('/login')` configuré globalement

### Dev quick login (mode local uniquement)
- [x] Section "⚡ Mode dev — connexion rapide" sur la page `/login`
- [x] Liste les comptes existants avec rôle visuel (super_admin/admin/banned)
- [x] Click → POST `/login` standard avec flag `dev=true` (pas de route séparée)
- [x] Backend ignore le flag si `APP_ENV !== local` (env check côté serveur)
- [x] Inertia partage `app.env` + `auth.user` + `flash` + `devUsers` via `HandleInertiaRequests`

---

## 10. Pages Inertia React

### Public — `resources/js/Pages/Public/`
- [x] Landing.tsx
- [x] Referral.tsx (`/r/{code}` — stocke code en session puis redirige vers register)

### Player — `resources/js/Pages/Player/`
- [x] Dashboard.tsx (stub avec stats grid)
- [x] Collection.tsx (stub) — **rétrogradée en sous-page de Factions** (lien depuis `/factions` plutôt que top-level nav)
- [x] **Factions/Index + Show** (3 cards avec lore+couleur+collection count, page show groupée par rareté avec indicateur owned/locked façon pokédex)
- [x] Gacha.tsx + GachaBanner.tsx (stubs)
- [x] Shop.tsx (stub)
- [x] Leaderboard.tsx (stub)
- [x] Missions.tsx (stub avec sections daily/weekly)
- [x] BattlePass.tsx (stub)
- [x] Referral.tsx (avec copie du lien fonctionnelle)
- [x] Profile.tsx (formulaire d'édition fonctionnel) + **ProfilePublic.tsx** (header avatar + niveau/XP/date d'inscription, stats grid niveau/collection/honneurs/classements, top 4 opérateurs favoris par affinité, table des meilleurs classements actifs)
- [x] **PlayerLayout** : lien `Parrainage` ajouté dans la nav principale (la page `/referral` était déjà câblée mais inaccessible depuis le menu)
- [x] Play.tsx (placeholder Unity)

### Admin — `resources/js/Pages/Admin/`
- [x] Dashboard.tsx (stats globales)
- [x] **Operators full CRUD** (Index filtrable + Create + Edit avec form complet + lore_unlocks 5 paliers + Show + soft delete + restore)
- [x] **Banners full CRUD** (idem + rate-up multi-select operators + somme des taux = 1.0000 + activate toggle + soft delete)
- [x] Players (Index avec recherche fonctionnelle, Show avec ban/unban)
- [x] GachaLogs.tsx (audit légal, filtrable + export CSV)
- [x] Referrals.tsx (détection patterns suspects)
- [x] **Missions full CRUD** (filtres + form rewards dynamiques + xp_reward + soft delete + restore)
- [x] Leaderboards (Index, Show, reset)
- [x] **Battle Pass full CRUD** (saisons avec anti-overlap dates + éditeur 50 paliers en bulk-update + milestones + filter all/milestones)
- [x] **Factions admin** (3 cards éditables avec lore + couleur OKLCH + page Show listant les ops de la faction groupés par rareté)
- [x] **Daily login rewards** (table éditable inline avec create/edit/delete par jour + fallback service sur défaut)
- [x] **Achievements full CRUD** (clé regex-validée + 5 catégories + rewards dynamiques + flag hidden + filtres + table avec compteur de débloqués)
- [x] **Events full CRUD** (5 types : limited_banner/pvp/pve/story/collaboration + lien optionnel à une Banner + rewards_pool + phase scheduled/current/expired + dates strictes)
- [x] Settings.tsx (configuration globale — stub, à câbler en Lot C)
- [ ] Moderation.tsx (signalements, sanctions) — Phase 5
- [ ] Leaderboard Seasons CRUD complet (Index + reset OK, manque create/update/destroy)
- [ ] grantCurrency joueur (action existante en stub, à câbler en Lot E)

### Layouts — `resources/js/Layouts/`
- [x] GuestLayout
- [x] PlayerLayout
- [x] AdminLayout

---

## 11. Système de parrainage (Phase 1)

### Logique
- [x] Code unique format `XXX-XXXX-XXXX` à l'inscription (booted hook User)
- [x] Page publique `/r/{code}` (stocke en session puis redirige register)
- [x] Cookie/session pour propager le parrain à l'inscription (AuthController lit `session('referral_code')`)

### Récompenses filleul (à la vérif email)
- [x] **5 tirages gratuits permanents** (`tickets_standard: 5` dans `REWARDS_BY_TRIGGER`)
- [x] **1 Opérateur Rare au choix parmi 3** (`tokens_rare_choice: 1`)
- [x] **500 monnaie premium** (`shards: 500`)

### Récompenses parrain (paliers)
- [x] **Niv 5 filleul → 10 tirages premium** (`tickets_premium: 10`)
- [x] **Niv 15 → 1 Épique + 1000 prem** (`shards: 1000` + `tokens_epic_choice: 1`)
- [x] **Niv 30 → 1 Légendaire au choix** (`tokens_legendary_choice: 1`)
- [x] **1er achat filleul → +50% prem au parrain** — `ReferralService::registerFirstPurchase`, idempotent par filleul, montant calculé dynamiquement sur `reward_amount` de la row. Hook dans `ShopService::purchasePack`. 3 tests Pest.

### Anti-abus
- [x] **Vérif email obligatoire** — `validateOnEmailVerified()` n'active les rewards qu'après email vérifié
- [ ] Délai 7j d'activité réelle (simplifié à la vérif email pour l'instant)
- [x] **Détection multi-comptes IP** — flag automatique si même IP qu'un autre filleul du parrain
- [x] **Détection fingerprint** (champ `referee_fingerprint` capturé via header `X-Device-Fingerprint`)
- [x] **Max 50 parrainages actifs par compte** — `MAX_ACTIVE_REFERRALS_PER_USER` enforced dans `createForNewUser`
- [x] **Logs complets pour audit** — table `referral_rewards` immuable + statut `flagged` avec `flag_reason`
- [x] **Aucune récompense réelle** — toutes les rewards sont des currencies in-game (shards, tickets, tokens de choix opérateur)
- [x] Bouton flag manuel dans `/admin/referrals` pour les cas suspects

### Pages
- [x] **Joueur `/referral`** — code visible, **bouton Copier le code** + **bouton Copier le lien** + **bouton Partager** (Web Share API natif avec fallback copy) + **liens directs WhatsApp / Telegram / X / Email**, stats filleuls, rewards en attente avec claim, table filleuls avec niveau/statut/email vérifié
- [x] **Admin `/admin/referrals`** — table filtrable (statut + recherche email), stats (6 KPIs), bouton flag manuel avec raison, badge "Même IP"

---

## 12. Système de classements (leaderboards)

### Classements
- [x] Hebdomadaire (saison seedée, active automatiquement)
- [x] Mensuel (saison seedée, active automatiquement)
- [ ] Annuel "Hall of Fame" (activé après 6+ mois — schéma prêt)
- [x] Compétitif saisonnier (saison "Éclat Primordial", 3 mois)
- [x] Collection (permanent — saison "Hall des Recruteurs")
- [x] Par faction (ORBIT/FERRO/VEIL — 3 saisons faction)
- [ ] Guildes (phase 5)

### Système de points
- [ ] Victoires PvP ×3 (PvP pas encore implémenté — phase 4)
- [x] Missions × 1 (daily) ou × 5 (weekly) → ajouté à toutes saisons weekly/monthly/seasonal actives au claim
- [ ] Défis hebdo ×5 (couverts par les missions weekly = +5 actuellement)
- [ ] Bonus MVP (PvP-related)
- [ ] Plafond quotidien (`daily_score_earned` schema en place — logique à câbler)
- [x] Calcul 100% serveur (toutes les sources de points sont dans des services backend)
- [x] Gacha pull → +1 par opérateur unique nouveau (collection + faction matching)

### Récompenses (paliers de %)
- [x] Logique de tier matching dans `LeaderboardService::distributeRewards()` (top_1, top_10, top_100, top_1pct, top_10pct, top_50pct)
- [x] **Configurer les rewards par tier dans `leaderboard_rewards`** — `LeaderboardRewardSeeder` : 6 tiers × 5 types de saison (weekly/monthly/seasonal/collection/faction), payouts tunés par type, 36 rewards seedés sur les saisons actives, wiré dans `DatabaseSeeder`.
- [~] Skins exclusifs / titres / bordures — types `cosmetic_*` réservés dans le seeder rewards (`cosmetic_title_apex`, `cosmetic_border_legend`, `cosmetic_faction_banner`, etc.), mais traités comme compteurs opaques tant qu'un inventaire cosmétique n'est pas câblé

### Anti-triche
- [x] Validation autoritaire serveur (toutes les sources de points en backend, jamais côté client)
- [x] Logs détaillés via `transactions` table (chaque distribution de reward loggée)
- [ ] Détection auto anomalies
- [ ] Limite 50 matchs classés/jour (PvP — phase 4)
- [ ] Cooldown anti-smurf
- [ ] Système de signalement joueur

### Architecture
- [x] **Redis Sorted Sets actifs** — `LeaderboardService` utilise `Redis::zincrby/zrevrange/zrevrank/zscore`
- [x] **Pagination top 100 + voisins** — `topN(season, 100)` et `neighborsOf(user, season, span=3)`
- [x] **Snapshots MySQL post-reset** — `snapshotToMysql(season)` archive + del Redis ZSET
- [x] **Distribution auto récompenses post-reset** — `distributeRewards(season)` parcourt entries archivées et matche les paliers
- [x] **Commande artisan `leaderboard:reset`** — flags `--season=`, `--type=`, `--expired-only`, prête pour le scheduler
- [x] Schedule Laravel câblé dans `routes/console.php` — weekly lundi 00h UTC + monthly 1er 00h UTC + filet de sécurité quotidien 03h UTC, tous avec `--expired-only`, `withoutOverlapping`, `runInBackground` et log dans `storage/logs/leaderboard-reset.log`
- [ ] Worker Redis dédié pour batching de gros volumes (pas nécessaire avant gros traffic)

### Pages
- [x] Joueur `/leaderboard` — tabs par saison, top 100, voisins (3 avant + user + 3 après), userRank summary, empty state élégant
- [x] **Admin `/admin/leaderboards`** — liste saisons actives + archivées avec participants count, badge expirée, bouton "Reset" (snapshot + distributeRewards)
- [x] **Admin `/admin/leaderboards/{season}`** — détail saison, top 100 avec source Redis ou MySQL selon état
- [ ] Historique post-reset visible côté joueur (snapshot table prête)

---

## 13. Roster de départ (8 Opérateurs — données)

- [x] Vex (ORBIT / Sniper longue portée / Légendaire)
- [x] Halo (ORBIT / Soutien soigneur / Épique)
- [x] Drift (ORBIT / Éclaireur rapide / Rare)
- [x] Crag (FERRO / Tank bouclier / Légendaire)
- [x] Brick (FERRO / Spécialiste explosifs / Épique)
- [x] Iron (FERRO / Assaut polyvalent / Commun)
- [x] Wraith (VEIL / Infiltrateur invisible / Épique)
- [x] Echo (VEIL / Hacker / Rare)

Pour chacun : nom ✅, faction ✅, rôle ✅, rareté ✅, lore ✅, stats (HP/dégâts/mobilité) ✅, arme signature ✅, 2 capacités actives + 1 ultime ✅, image portrait `[ ]` (placeholder à remplacer).

---

## 14. Roadmap par phases

### Phase 1 — Fondations *(quasi-terminée)*
- [x] Squelette Laravel 13 + Inertia 3 + React 19 + TS + Tailwind 4
- [x] Design System initial (tokens)
- [x] 20 composants UI de base (stubs fonctionnels)
- [x] 12 composants Game (stubs fonctionnels)
- [x] Auth complète (inscription, login, reset, vérif email — AuthController)
- [ ] Passkey (optionnel)
- [x] Système rôles via middleware + helpers User
- [x] Modèles : User, Operator, Banner, GachaPull, PlayerOperator, Currency, Transaction, Referral, ReferralReward
- [x] Génération auto code parrainage (booted hook sur User)
- [x] Dashboard joueur basique (stub avec stats grid)
- [x] Premiers écrans admin (Dashboard + Players index/show fonctionnels)
- [x] Tables additionnelles : DailyLogin, Mission, MissionProgress, BattlePass, BattlePassProgress, OperatorAffinity, Achievement, UserAchievement, Event, Guild, GuildMember, LeaderboardSeason, LeaderboardEntry, LeaderboardReward

### Phase 2 — Gacha + fidélisation court terme *(gacha fonctionnel)*
- [x] **Logique tirage 100% serveur** (`App\Services\GachaService`)
- [x] **Taux 60/30/8/2** (configurables par bannière, soft pity boost calculé)
- [x] **Pity Légendaire 80 (soft 60), Épique 10** (configurables par bannière)
- [x] **Bannières permanentes + événementielles avec rate-up** (50% chance chez les rate-up sur epic/legendary)
- [x] **Animations Framer Motion** (`GachaPullAnimation` reveal séquentiel)
- [x] **Historique tirages** (20 derniers affichés sur la page bannière)
- [x] **Transactions MySQL atomiques** (`DB::transaction()` + `lockForUpdate()` sur PityCounter et Currency)
- [x] **Logs détaillés audit légal** (table `gacha_pulls` immuable, no UPDATED_AT, log session_id + ip + flags pity)
- [x] Boutons Tirer ×1 / ×10 fonctionnels avec validation solde
- [x] Throttle 60/60min sur la route `gacha.pull`
- [x] **Page admin logs gacha** — UI complète : 3 stats cards + form filtres (joueur, bannière, rareté, période, flag pity), table avec RarityBadge + flags Pity/Soft/Rate-up, pagination, **export CSV**. Routes `admin/gacha-logs` (index + export) câblées.
- [x] Storybook installé — 3 stories actuelles, à étendre au fur et à mesure
- [x] **Connexion quotidienne** — `DailyLoginService` calcule streak global et jour mensuel (1-30), paliers spéciaux J1/J7/J15/J30 avec rewards boostés (shards + tickets premium), bouton "Réclamer" sur le dashboard
- [x] **Missions journalières** (3 actives seedées) — auto-progressées via `MissionService::progressFor('pull', n)` à chaque tirage, claim avec rewards + XP via `MissionService::claim()`
- [x] **Missions hebdomadaires** (2 seedées : Signal Shard hebdo + Commandant actif)
- [x] **XP comptes 1-99** — `XpService` avec formule linéaire (100×N XP par niveau), gain auto sur pull (10/25/75/200 par rareté) et claim mission (xp_reward), level-up cascade géré
- [x] **Fragments doublons** — `gacha_duplicate` reward 1/5/20/100 fragments selon rareté, currency type `fragments_{codename}` créée à la volée, log Transaction immuable
- [ ] Échange fragments → opérateur ciblé en boutique (constante `FRAGMENTS_TO_OPERATOR` prête dans `ShopService`, échange UI à câbler)
- [x] **Page `/referral`** — UI complète : code + lien + bouton Partager (Web Share API) + liens directs WhatsApp/Telegram/X/Email + stats filleuls + rewards en attente avec claim + table filleuls
- [x] **Classement hebdomadaire** (saison active, points via mission claim)
- [x] **Classement mensuel** (saison active, points via mission claim)
- [x] **Classement collection** (saison active, points via gacha pulls — 1 pt par opérateur unique)
- [x] **Classement par faction** (3 saisons ORBIT/FERRO/VEIL — pts par opérateur faction-matching)

### Services métier créés (10 services + 1 commande)
- [x] `App\Services\GachaService` — pull atomique avec pity/rate-up/XP/missions/fragments + leaderboard + affinité + achievements hooks
- [x] `App\Services\RewardService` — applique tableau de rewards (currencies + log Transaction)
- [x] `App\Services\XpService` — award + level-up cascade + hooks référral milestones + battle pass XP
- [x] `App\Services\DailyLoginService` — record + streak + claim
- [x] `App\Services\MissionService` — progressFor / claim / listForUser + leaderboard hooks
- [x] `App\Services\LeaderboardService` — Redis ZSET addPoints/topN/rankOf/scoreOf/neighborsOf, snapshot MySQL, distributeRewards par paliers
- [x] `App\Services\ReferralService` — createForNewUser (avec anti-abuse), validateOnEmailVerified, checkLevelMilestones, claim, pendingRewardsFor
- [x] `App\Services\BattlePassService` — addXp / purchase (1000 shards) / claim (free + premium si activé)
- [x] `App\Services\AffinityService` — award XP affinité par opérateur (0-10), formule 100×(N+1)
- [x] `App\Services\AchievementService` — track événements, claim avec rewards, listForUser (cache hidden non débloqués)
- [x] `App\Services\ShopService` — listPacks + purchasePack atomique avec lockForUpdate Currency
- [x] `App\Console\Commands\LeaderboardReset` (artisan `leaderboard:reset`) — snapshot + distribution post-reset

### Pages joueur réelles (avec data live)
- [x] **Dashboard** — niveau + XP bar + currencies + opérateurs count + streak + daily reward claim + missions journalières/hebdo avec MissionCard fonctionnel
- [x] **Collection** — grid d'OperatorCard pour chaque PlayerOperator, badge `+N` pour duplicates
- [x] **Missions** — page dédiée avec tabs daily/weekly et claim
- [x] **Gacha** + **GachaBanner** (déjà fait phase précédente)

### Phase 3 — Méta-jeu *(quasi-terminée)*
- [x] **Inventaire visuel** — page Collection avec OperatorCard cliquable → détail, badge duplicate +N, affinity bar par opérateur
- [x] **Boutique** — `ShopService` avec 3 packs hardcodés (starter gratuit, shards pack, événementiel Apex), `ShopController` index/purchase, page `/shop` avec grid de packs cliquables (achat shards in-game, Stripe en Phase 5)
- [x] **Profil public** (page `Player/ProfilePublic.tsx` fonctionnelle, lien depuis ProfileController.show)
- [ ] Système amis (Phase 5)
- [x] **Achievements visibles** — `Achievement` model + `AchievementService::track/claim/listForUser`, 10 achievements seedés (collection × 5, progression × 3, special × 1, social × 1), tracking auto via GachaService. **Page `/achievements`** avec stats, groupement par catégorie, claim button par achievement complété.
- [x] **Battle Pass saisonnier** — `BattlePass` + `BattlePassTier` × 50 + `BattlePassProgress` models, `BattlePassService` (addXp/purchase/claim), saison 1 seedée (8 semaines, 1000 shards premium, paliers milestones 5/10/25/50), page `/battlepass` avec roadmap horizontale + progression XP, hook XpService alimente automatiquement le BP actif
- [x] **Affinité Opérateurs** — `OperatorAffinity` model 0-10 levels, `AffinityService::award` avec formule 100×(N+1) XP par level, hook GachaService (25 XP/pull + 10 XP doublon), affinity bar par opérateur dans Collection
- [x] **Page détail opérateur `/operators/{id}`** — stats HP/dégâts/mobilité, arme signature, capacités (active/passive/ultimate), affinité avec AffinityMeter, lore progressif débloqué (5 paliers : 0/2/5/8/10)
- [x] **Classements par faction** (déjà actifs depuis Phase 2 — 3 saisons ORBIT/FERRO/VEIL avec hook gacha)
- [x] **Lore débloqué progressivement par niveau d'affinité** — 5 paliers générés à la volée par `OperatorController::lorePart()`, UI dans page détail opérateur (à terme : stocker dans operators.lore_unlocks JSON ou table dédiée)
- [ ] Skins gratuits / voicelines par level affinité (assets pas en BDD)
- [ ] MCP Design System custom (Phase 3+ ou plus tard)

### Phase 4 — Intégration Unity + compétitif
- [ ] Page `/play` Unity 6 WebGL
- [ ] Communication JS ↔ Unity
- [ ] Tokens session signés Sanctum
- [ ] Validation autoritaire résultats
- [ ] Événements limités (3-4 sem)
- [ ] Classement compétitif saisonnier (Bronze → Master, reset trimestriel)
- [ ] Notifications email intelligentes

### Phase 5 — Multijoueur, social, long terme
- [ ] Matchmaking Photon Fusion
- [ ] Mode Deathmatch 5v5
- [ ] Mode PvE vagues
- [ ] Stripe via Laravel Cashier
- [ ] Guildes 20-50 membres + chat + missions collectives
- [ ] Co-op PvE 3 joueurs
- [ ] Cadeaux quotidiens entre amis
- [ ] Profil public personnalisable
- [ ] Replay (kill cam, partage Discord/Twitter)
- [ ] Hall of Fame annuel (après 6+ mois)

---

## 15. Sécurité — règles non-négociables

- [x] Aucun port DB/Redis exposé internet (config Docker)
- [ ] Serveur Laravel = seule source de vérité (à vérifier au fil de l'implémentation)
- [ ] Aucun calcul sensible côté client (drops/monnaie/XP/points)
- [ ] Transactions MySQL atomiques avec verrouillage (gacha)
- [ ] Stripe webhooks (achats)
- [ ] Rate limiting API sensibles
- [ ] Logs horodatés gacha (audit légal)
- [ ] Logs horodatés matchs classés (audit légal)
- [ ] Middleware admin sur `/admin/*`
- [ ] 2FA admin recommandée
- [ ] Logs actions admin
- [ ] Routes admin séparées
- [ ] phpMyAdmin auth HTTP (dev + prod)
- [ ] phpMyAdmin IP whitelist (prod uniquement)

---

## 16. Monétisation

- [ ] Free-to-play, **jamais P2W**
- [ ] Monnaie premium pour gacha
- [ ] Skins cosmétiques
- [ ] Battle pass saisonnier (~10€)
- [ ] Pack starter nouveaux joueurs
- [ ] Packs événementiels limités
- [ ] Opérateurs premium : esthétique/stratégie variée — jamais stat supérieures
- [ ] Pity système généreux

---

## 17. GitHub & repo

- [x] `.gitignore` (avec `docker-compose.override.yml` committé)
- [x] `.env.example`
- [x] Memory files (project_rocketpi.md, user_fabrice.md)
- [x] `docs/PROJECT_CHECKLIST.md` (ce fichier)
- [x] **`README.md` projet** — présentation, install, déploiement, design system, monétisation, roadmap, tests, licence
- [x] **`LICENSE`** — Tous droits réservés (propriétaire)
- [ ] CI/CD GitHub Actions (optionnel — lint + tests)
