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
- [ ] Laravel Sanctum installé (composer require laravel/sanctum)
- [ ] Laravel Horizon installé (composer require laravel/horizon)
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
- [x] `nginx`
- [x] `mysql` (MySQL 8.4 LTS)
- [x] `phpmyadmin`
- [x] `redis` (Redis 8)
- [x] `queue-worker` (Horizon)
- [x] `scheduler` (artisan schedule:run)
- [x] (dev) `mailpit` capture emails
- [x] (dev) `vite` HMR sur :5173

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

### UI de base — `resources/js/Components/UI/` (1/20)
- [x] Button
- [ ] Input
- [ ] Select
- [ ] Checkbox
- [ ] Radio
- [ ] Toggle
- [ ] Modal
- [ ] Drawer
- [ ] Tooltip
- [ ] Toast
- [ ] Tabs
- [ ] Card
- [ ] Badge
- [ ] Avatar
- [ ] Table
- [ ] Pagination
- [ ] Skeleton
- [ ] Spinner
- [ ] Progress
- [ ] Alert

### Game spécifiques — `resources/js/Components/Game/` (0/12)
- [ ] OperatorCard (variantes par rareté)
- [ ] BannerCard
- [ ] GachaPullAnimation
- [ ] PityCounter
- [ ] RarityBadge
- [ ] FactionBadge
- [ ] LeaderboardRow
- [ ] RankBadge
- [ ] MissionCard
- [ ] BattlePassNode
- [ ] AffinityMeter
- [ ] CurrencyDisplay

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
- [x] `AdminUserSeeder` (compte admin)
- [x] `OperatorSeeder` (8 opérateurs avec lore + abilities complets)
- [x] `BannerSeeder` (bannière permanente + 1 événementielle)
- [x] `MissionSeeder` (5 missions ≥ 3 demandées)
- [x] `LeaderboardSeasonSeeder` (5 saisons ≥ 1 demandée)

---

## 8. Routes

### Web — `routes/web.php`
- [x] Public : `/`, `/r/{code}`, login, register
- [x] Joueur (auth+verified+not.banned) : /dashboard, /collection, /gacha, /shop, /leaderboard, /missions, /battlepass, /referral, /profile, /play
- [x] Admin (auth+admin) : /admin/*

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
- [ ] `Auth\LoginController`
- [ ] `Auth\RegisterController`
- [ ] `Auth\ForgotPasswordController`
- [ ] `Auth\ResetPasswordController`
- [ ] `Auth\VerifyEmailController`
- [ ] `Auth\LogoutController`
- [ ] Passkey (optionnel)

### Pages Inertia — `resources/js/Pages/Auth/`
- [ ] Login.tsx
- [ ] Register.tsx
- [ ] ForgotPassword.tsx
- [ ] ResetPassword.tsx
- [ ] VerifyEmail.tsx

### Système de rôles
- [x] Migration `role` (user / admin / super_admin)
- [x] Middleware `admin`
- [ ] Policies + Gates
- [ ] 2FA admin (recommandée)

### Génération auto
- [ ] Code parrainage `XXX-XXXX-XXXX` à l'inscription (Observer ou hook User)

---

## 10. Pages Inertia React

### Public — `resources/js/Pages/Public/`
- [x] Landing.tsx
- [ ] Referral.tsx (`/r/{code}`)

### Player — `resources/js/Pages/Player/`
- [ ] Dashboard.tsx
- [ ] Collection.tsx
- [ ] Gacha.tsx
- [ ] Shop.tsx
- [ ] Leaderboard.tsx
- [ ] Missions.tsx
- [ ] BattlePass.tsx
- [ ] Referral.tsx
- [ ] Profile.tsx
- [ ] Play.tsx (Unity embed phase 4)

### Admin — `resources/js/Pages/Admin/`
- [ ] Dashboard.tsx (stats globales)
- [ ] Operators (Index, Create, Edit)
- [ ] Banners (Index, Create, Edit)
- [ ] Players (Index, Show, recherche/ban/monnaie/historique)
- [ ] GachaLogs.tsx (audit légal, filtrable)
- [ ] Referrals.tsx (détection patterns suspects)
- [ ] Missions / Events / BattlePass
- [ ] Leaderboards.tsx (saisons, suspects, distribution forcée)
- [ ] Moderation.tsx (signalements, sanctions)
- [ ] Config.tsx (configuration globale)

### Layouts — `resources/js/Layouts/`
- [ ] GuestLayout
- [ ] PlayerLayout
- [ ] AdminLayout

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

### Phase 1 — Fondations *(en cours)*
- [x] Squelette Laravel 13 + Inertia 3 + React 19 + TS + Tailwind 4
- [x] Design System initial (tokens)
- [~] 10 composants de base (1/20 → Button)
- [ ] Auth complète (inscription, login, reset, vérif email)
- [ ] Passkey (optionnel)
- [x] Système rôles via middleware
- [x] Modèles : User, Operator, Banner, GachaPull, PlayerOperator, Currency, Transaction, Referral, ReferralReward
- [ ] Génération auto code parrainage
- [ ] Dashboard joueur basique
- [ ] Premiers écrans admin
- [x] Tables additionnelles : DailyLogin, Mission, MissionProgress, BattlePass, BattlePassProgress, OperatorAffinity, Achievement, UserAchievement, Event, Guild, GuildMember, LeaderboardSeason, LeaderboardEntry, LeaderboardReward

### Phase 2 — Gacha + fidélisation court terme
- [ ] Logique tirage 100% serveur
- [ ] Taux 60/30/8/2
- [ ] Pity Légendaire 80 (soft 60), Épique 10
- [ ] Bannières permanentes + événementielles avec rate-up
- [ ] Animations Framer Motion
- [ ] Historique tirages
- [ ] Transactions MySQL atomiques (`DB::transaction()` + `lockForUpdate()`)
- [ ] Logs détaillés audit légal
- [ ] Page admin logs gacha
- [ ] Storybook installé (à 15+ composants)
- [ ] Connexion quotidienne (calendrier mensuel, paliers J1/J7/J15/J30)
- [ ] Missions journalières (3-5/jour)
- [ ] Missions hebdomadaires (5/sem)
- [ ] XP comptes 1-60+
- [ ] Fragments doublons → opérateur ciblé en boutique
- [ ] Page `/referral`
- [ ] Classement hebdomadaire
- [ ] Classement mensuel
- [ ] Classement collection

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
