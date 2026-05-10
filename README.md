# RocketPi — Shardfall Protocol

> FPS hero-shooter jouable dans le navigateur, avec collection de personnages (gacha) et méta-jeu Laravel.

**Univers :** En 2087, la station spatiale **RocketPi** se désintègre dans la haute atmosphère et disperse ses cristaux **Shards**. Les civils exposés deviennent les **Opérateurs**. Trois factions — ORBIT, FERRO, VEIL — s'affrontent pour les fragments. Le joueur recrute ses Opérateurs via le **Recrutement par Signal Shard** et les envoie en mission.

---

## Stack

| Domaine | Techno |
|---|---|
| Backend | Laravel 13 · PHP 8.5 · Sanctum · Horizon · Policies/Gates |
| Frontend | Inertia.js 3.1 · React 19 · TypeScript · Tailwind CSS 4 · Vite 8 |
| Données | MySQL 8.4 LTS · Redis 8 (cache/sessions/queues/leaderboards) |
| Infra | Docker · Docker Compose · Nginx Proxy Manager · VPS Hostinger |
| Tests | Pest 3 · Vitest · Storybook 8 |
| Jeu (phase 4) | Unity 6 LTS WebGL · Photon Fusion |

---

## Architecture Docker

Pattern override standard :

| Commande | Stack |
|---|---|
| `docker compose up` | Base + `override.yml` (dev — auto) |
| `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d` | Prod |

**Services par stack :** `laravel-app` (PHP-FPM 8.5), `nginx`, `mysql`, `phpmyadmin`, `redis`, `queue-worker` (Horizon), `scheduler`. En dev : `mailpit` + `vite` HMR.

Réseau interne uniquement — DB et Redis jamais exposés à internet.

---

## Démarrage rapide (dev local)

Prérequis : Docker Desktop + WSL2 (Ubuntu).

```bash
docker compose up -d --build
```

C'est tout. Au premier démarrage le service `installer` :
1. Lance `composer install` si `vendor/` manque
2. Copie `.env.example` → `.env` si manquant
3. Génère `APP_KEY` si manquant
4. Lance `npm install` si `node_modules/` manque
5. Lance `php artisan migrate` (idempotent)

Les autres services attendent que l'installer ait terminé (`service_completed_successfully`).

Pour seed les données de démo (admin, opérateurs, bannières) :

```bash
docker compose exec laravel-app php artisan db:seed
```

| Endpoint | URL |
|---|---|
| App | http://localhost (via Nginx Proxy Manager ou expose port nginx) |
| Mailpit (capture emails) | http://localhost:8025 |
| Vite HMR | http://localhost:5173 (auto) |
| MySQL | `127.0.0.1:3307` |
| Redis | `127.0.0.1:6380` |

---

## Domaines (production)

| Domaine | Cible |
|---|---|
| `rocketpi.pro` | Stack prod |
| `rocketpi-test.pro` | Stack dev |
| `db.rocketpi.pro` | phpMyAdmin prod (auth HTTP + IP whitelist) |
| `db.rocketpi-test.pro` | phpMyAdmin dev (auth HTTP) |

Routage via **Nginx Proxy Manager** unique en façade, SSL Let's Encrypt automatique.

---

## Design System

Source de vérité unique : [`resources/design-system/`](resources/design-system/)

- [`tokens.json`](resources/design-system/tokens.json) — couleurs (OKLCH), typographie, spacing, radius, animations
- [`colors.md`](resources/design-system/colors.md), [`typography.md`](resources/design-system/typography.md), [`spacing.md`](resources/design-system/spacing.md), [`components.md`](resources/design-system/components.md)
- Tailwind 4 `@theme` dans [`resources/css/app.css`](resources/css/app.css)

**Règles non-négociables :**
- Aucune couleur hex hardcodée hors `tokens.json`
- Aucune valeur arbitraire (`mt-[13px]` ❌)
- Story Storybook + doc Markdown obligatoires pour tout nouveau composant
- Variantes sur composants existants > nouveaux composants

Outils : CVA · Radix UI · Lucide React · Framer Motion.

---

## Suivi du projet

[`docs/PROJECT_CHECKLIST.md`](docs/PROJECT_CHECKLIST.md) — case à cocher pour chaque demande de la spec, par phase.

---

## Sécurité (non-négociable)

- Le serveur Laravel est la **seule source de vérité**
- Aucun calcul sensible côté client (drops, monnaie, XP, points)
- Transactions MySQL atomiques avec verrouillage (`DB::transaction()` + `lockForUpdate()`)
- Logs gacha + matchs classés horodatés (audit légal, immutables)
- Rate limiting sur API sensibles
- Aucun port DB/Redis exposé internet
- Panel admin : middleware + 2FA recommandée + logs d'actions

---

## Monétisation

Free-to-play, **jamais pay-to-win**. Monnaie premium (gacha), skins cosmétiques, battle pass saisonnier (~10€), pack starter, packs événementiels limités. Opérateurs premium = esthétique/stratégie variée, **jamais stat supérieures**.

---

## Roadmap

| Phase | État | Contenu |
|---|---|---|
| **1** | ✅ | Fondations Laravel + Auth (login/register/email verify/reset) + dev quick login + Design System (32 composants) + modèles DB (15 tables) |
| **2** | ✅ | Gacha 100% serveur (taux 60/30/8/2, pity 80/10 + soft pity, rate-up, animation Framer Motion), fidélisation (daily login, missions, XP 1-99, fragments doublons), classements Redis Sorted Sets (top 100 + voisins + reset auto), parrainage complet (paliers parrain niv 5/15/30, anti-abuse), pages admin (logs gacha filtrables + leaderboards + referrals), Pest 34/34 tests, ESLint+Stylelint enforcement |
| **3** *(en cours)* | 🟡 | Méta-jeu : Battle Pass saisonnier (50 paliers free + premium 1000 shards, 8 sem, paliers milestones 5/10/25/50, hook XP auto), Affinité opérateurs (0-10, 25 XP/pull + 10 XP doublon, formule 100×N+1), Achievements (10 seedés, 5 catégories, hooks gacha auto), Collection avec affinity bar par opérateur, profil public ; reste : boutique fonctionnelle, lore débloqué progressif, skins/voicelines, système amis (Phase 5) |
| **4** | ⏳ | Intégration Unity 6 WebGL + événements limités + classement compétitif saisonnier |
| **5** | ⏳ | Multijoueur Photon · Stripe (Cashier) · guildes · co-op PvE · Hall of Fame annuel |

## Stack métier — services backend

| Service | Rôle |
|---|---|
| `GachaService` | Pull atomique avec pity/rate-up, hooks XP/missions/fragments/leaderboard |
| `RewardService` | Applique tableau de rewards (currencies + Transaction immuable) |
| `XpService` | Award XP + level-up cascade + hook référral milestones |
| `DailyLoginService` | Streak + paliers J1/J7/J15/J30 + claim |
| `MissionService` | progressFor / claim avec rewards + XP |
| `LeaderboardService` | Redis ZSET (addPoints / topN / neighbors), snapshot MySQL, distribution rewards par paliers % |
| `ReferralService` | Parrainage avec anti-abuse (max 50, IP detection), milestones niv 5/15/30 |
| `BattlePassService` | Saison active + addXp + purchase premium + claim tier (free + premium si payé) |
| `AffinityService` | XP affinité par opérateur (0-10), level-up cascade |
| `AchievementService` | track événement → progress, claim avec rewards, listForUser |

## Tests & qualité

```powershell
docker compose exec laravel-app vendor/bin/pest    # 34 tests / 78 assertions au vert
docker compose exec vite npm run lint               # 0 erreur
docker compose exec vite npm run stylelint          # 0 erreur
```

ESLint 9 interdit `bg-[#hex]`, `mt-[13px]`, inline `style={{color:'#hex'}}`. Stylelint 17 interdit `color: red` et `color: #abc` partout sauf source DS (`resources/css/app.css`).

---

## Licence

À définir.
