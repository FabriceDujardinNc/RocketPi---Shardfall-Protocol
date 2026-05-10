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
- [ ] Laravel Policies + Gates configurés
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
- [ ] Pest 3 (composer require --dev pestphp/pest)
- [x] Vitest (déps installées)
- [ ] Storybook 8 (config `.storybook/`)
- [x] Code versionné GitHub
- [x] Procédure `git pull && docker compose up -d --build`

---

## 2. Infrastructure VPS

- [ ] VPS Hostinger provisionné
- [ ] Domaine `rocketpi.pro` (DNS → VPS)
- [ ] Domaine `rocketpi-test.pro` (DNS → VPS)
- [ ] Sous-domaine `db.rocketpi.pro`
- [ ] Sous-domaine `db.rocketpi-test.pro`
- [ ] Docker + Docker Compose installés sur VPS
- [ ] Réseau Docker `proxy` créé
- [ ] Nginx Proxy Manager déployé en façade
- [ ] SSL Let's Encrypt automatique sur 4 domaines
- [ ] Stack prod déployée dans `/opt/rocketpi-prod/`
- [ ] Stack dev déployée dans `/opt/rocketpi-test/`
- [ ] phpMyAdmin sécurisé : auth HTTP basique sur dev
- [ ] phpMyAdmin sécurisé : auth HTTP + IP whitelist sur prod
- [ ] Sauvegardes auto MySQL (cron dump)
- [ ] Sauvegardes auto Redis (snapshots)
- [ ] **`docs/VPS_SETUP.md` rédigé pas à pas**

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
- [ ] **ESLint configuré** (interdit valeurs hardcodées hex/arbitraires)
- [ ] **Stylelint configuré**

### Règles non-négociables (appliquer au code)
- [ ] Aucune couleur hex hors tokens.json
- [ ] Aucune valeur arbitraire `mt-[13px]`, `bg-[#FF5733]`
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
- [ ] Configuration `.storybook/`
- [ ] Première story (`Button.stories.tsx`)
- [ ] Stories pour tous les composants (à activer dès 15+ composants)

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
- [x] operators
- [x] banners
- [x] gacha_pulls (audit-legal, immutable)
- [x] player_operators
- [x] currencies
- [x] transactions (append-only)
- [x] referrals + referral_rewards
- [x] daily_logins
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
- [x] `OperatorSeeder` (8 opérateurs avec lore + abilities complets)
- [x] `BannerSeeder` (bannière permanente + 1 événementielle)
- [x] `MissionSeeder` (5 missions ≥ 3 demandées)
- [x] `LeaderboardSeasonSeeder` (6 saisons ≥ 1 demandée)

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
- [ ] Policies + Gates
- [ ] 2FA admin (recommandée)

### Génération auto
- [x] Code parrainage `XXX-XXXX-XXXX` à l'inscription (booted hook sur User model)

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
- [x] Collection.tsx (stub)
- [x] Gacha.tsx + GachaBanner.tsx (stubs)
- [x] Shop.tsx (stub)
- [x] Leaderboard.tsx (stub)
- [x] Missions.tsx (stub avec sections daily/weekly)
- [x] BattlePass.tsx (stub)
- [x] Referral.tsx (avec copie du lien fonctionnelle)
- [x] Profile.tsx (formulaire d'édition fonctionnel) + ProfilePublic.tsx
- [x] Play.tsx (placeholder Unity)

### Admin — `resources/js/Pages/Admin/`
- [x] Dashboard.tsx (stats globales)
- [x] Operators (Index, Create, Show, Edit)
- [x] Banners (Index, Create, Show, Edit)
- [x] Players (Index avec recherche fonctionnelle, Show avec ban/unban)
- [x] GachaLogs.tsx (audit légal, filtrable)
- [x] Referrals.tsx (détection patterns suspects)
- [x] Missions (Index, Create, Show, Edit)
- [x] Leaderboards (Index, Show, reset)
- [x] Settings.tsx (configuration globale)
- [ ] Moderation.tsx (signalements, sanctions) — Phase 5
- [ ] Events / BattlePass admin pages — Phase 3

### Layouts — `resources/js/Layouts/`
- [x] GuestLayout
- [x] PlayerLayout
- [x] AdminLayout

---

## 11. Système de parrainage (Phase 1)

### Logique
- [ ] Code unique format `XXX-XXXX-XXXX` à l'inscription
- [ ] Page publique `/r/{code}`
- [ ] Cookie/session pour propager le parrain à l'inscription

### Récompenses filleul (à la vérif email)
- [ ] 5 tirages gratuits permanents
- [ ] 1 Opérateur Rare au choix parmi 3
- [ ] 500 monnaie premium

### Récompenses parrain (paliers)
- [ ] Niv 5 filleul → 10 tirages premium
- [ ] Niv 15 → 1 Épique + 1000 prem
- [ ] Niv 30 → 1 Légendaire au choix
- [ ] 1er achat filleul → +50% prem au parrain

### Anti-abus
- [ ] Vérif email obligatoire
- [ ] Délai 7j d'activité réelle
- [ ] Détection multi-comptes IP/fingerprint (flag admin)
- [ ] Max 50 parrainages actifs par compte
- [ ] Logs complets pour audit
- [ ] Aucune récompense réelle

### Pages
- [ ] Joueur `/referral`
- [ ] Admin `/admin/referrals`

---

## 12. Système de classements (leaderboards)

### Classements
- [ ] Hebdomadaire (reset lundi 00h UTC)
- [ ] Mensuel (reset 1er du mois)
- [ ] Annuel "Hall of Fame" (activé après 6+ mois)
- [ ] Compétitif saisonnier (Bronze → Master, ELO/MMR, reset trimestriel)
- [ ] Collection (permanent)
- [ ] Par faction (ORBIT/FERRO/VEIL)
- [ ] Guildes (phase 5)

### Système de points
- [ ] Victoires PvP ×3
- [ ] Missions ×1
- [ ] Défis hebdo ×5
- [ ] Bonus MVP
- [ ] Plafond quotidien
- [ ] Calcul 100% serveur

### Récompenses (paliers de %)
- [ ] Top 1 : titre exclusif + monnaie + skin exclusif
- [ ] Top 10 : monnaie + bordure profil
- [ ] Top 100 : monnaie modérée
- [ ] Top 1% : monnaie + fragments
- [ ] Top 10% : monnaie modérée
- [ ] Top 50% : participation symbolique

### Anti-triche
- [ ] Validation autoritaire serveur
- [ ] Détection auto anomalies
- [ ] Limite 50 matchs classés/jour
- [ ] Cooldown anti-smurf
- [ ] Logs détaillés
- [ ] Système de signalement joueur

### Architecture
- [ ] Redis Sorted Sets actifs
- [ ] Worker Laravel async pour mises à jour
- [ ] Pagination (top 100 + voisins)
- [ ] Snapshots MySQL post-reset (cron)
- [ ] Distribution auto récompenses post-reset

### Pages
- [ ] Joueur `/leaderboard` (onglets, top 100, voisins, historique, récompenses)
- [ ] Admin `/admin/leaderboards`

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
- [ ] Page admin logs gacha (controller stub présent — UI table à câbler)
- [ ] Storybook installé (à 15+ composants)
- [x] **Connexion quotidienne** — `DailyLoginService` calcule streak global et jour mensuel (1-30), paliers spéciaux J1/J7/J15/J30 avec rewards boostés (shards + tickets premium), bouton "Réclamer" sur le dashboard
- [x] **Missions journalières** (3 actives seedées) — auto-progressées via `MissionService::progressFor('pull', n)` à chaque tirage, claim avec rewards + XP via `MissionService::claim()`
- [x] **Missions hebdomadaires** (2 seedées : Signal Shard hebdo + Commandant actif)
- [x] **XP comptes 1-99** — `XpService` avec formule linéaire (100×N XP par niveau), gain auto sur pull (10/25/75/200 par rareté) et claim mission (xp_reward), level-up cascade géré
- [x] **Fragments doublons** — `gacha_duplicate` reward 1/5/20/100 fragments selon rareté, currency type `fragments_{codename}` créée à la volée, log Transaction immuable
- [ ] Échange fragments → opérateur ciblé en boutique (boutique pas implémentée)
- [ ] Page `/referral` (controller stub avec liens fonctionnels — UI à compléter)
- [ ] Classement hebdomadaire
- [ ] Classement mensuel
- [ ] Classement collection

### Services métier créés
- [x] `App\Services\GachaService` — pull atomique avec pity/rate-up/XP/missions/fragments
- [x] `App\Services\RewardService` — applique tableau de rewards (currencies + log Transaction)
- [x] `App\Services\XpService` — award + level-up cascade
- [x] `App\Services\DailyLoginService` — record + streak + claim
- [x] `App\Services\MissionService` — progressFor / claim / listForUser

### Pages joueur réelles (avec data live)
- [x] **Dashboard** — niveau + XP bar + currencies + opérateurs count + streak + daily reward claim + missions journalières/hebdo avec MissionCard fonctionnel
- [x] **Collection** — grid d'OperatorCard pour chaque PlayerOperator, badge `+N` pour duplicates
- [x] **Missions** — page dédiée avec tabs daily/weekly et claim
- [x] **Gacha** + **GachaBanner** (déjà fait phase précédente)

### Phase 3 — Méta-jeu
- [ ] Inventaire visuel
- [ ] Boutique
- [ ] Profil public
- [ ] Système amis
- [ ] Achievements visibles
- [ ] Battle Pass saisonnier (gratuit + premium ~10€, 50 paliers, 8 sem)
- [ ] Affinité Opérateurs (0-10, lore, skins gratuits, voicelines)
- [ ] Classements par faction
- [ ] MCP Design System custom

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
- [ ] `README.md` projet (présentation, install, déploiement)
- [ ] `LICENSE`
- [ ] CI/CD GitHub Actions (optionnel — lint + tests)
