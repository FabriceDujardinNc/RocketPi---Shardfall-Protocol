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
cp .env.example .env
docker compose up -d --build
docker compose exec laravel-app php artisan key:generate
docker compose exec laravel-app php artisan migrate --seed
```

- App : http://localhost (via NPM ou direct selon ton setup)
- Mailpit : http://localhost:8025
- Vite HMR : http://localhost:5173 (auto)
- MySQL : `127.0.0.1:3307`
- Redis : `127.0.0.1:6380`

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

| Phase | Contenu |
|---|---|
| **1** *(en cours)* | Fondations Laravel + Auth + Design System + modèles DB |
| **2** | Gacha (taux 60/30/8/2, pity 80/10) + fidélisation + classements basiques |
| **3** | Méta-jeu : inventaire, boutique, profil, amis, achievements, Battle Pass, affinité |
| **4** | Intégration Unity 6 WebGL + événements limités + classement compétitif |
| **5** | Multijoueur Photon · Stripe · guildes · co-op · Hall of Fame annuel |

---

## Licence

À définir.
