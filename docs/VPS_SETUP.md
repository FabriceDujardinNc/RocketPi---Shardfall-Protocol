# RocketPi — Setup VPS

Procédure complète pour un VPS Linux nu jusqu'au site en HTTPS.

Profil cible : **VPS Hostinger Ubuntu 24.04 LTS**, root SSH activé, 4 GB RAM minimum, IPv4 publique.

> 💡 Cible la **configuration actuelle de production**, telle qu'observée sur `rocketpi.pro` au 11/05/2026.

---

## 1. Prérequis DNS

Avant toute chose, pointe les A-records des domaines vers l'IP publique du VPS :

| Type | Host                       | Cible           |
|------|----------------------------|-----------------|
| A    | `rocketpi.pro`             | `IP_DU_VPS`     |
| A    | `www.rocketpi.pro`         | `IP_DU_VPS`     |
| A    | `rocketpi-test.pro`        | `IP_DU_VPS`     |
| A    | `www.rocketpi-test.pro`    | `IP_DU_VPS`     |

Les sous-domaines `db.*` sont optionnels et viendront plus tard (phpMyAdmin sécurisé).

Vérifie la propagation : `dig +short rocketpi.pro @1.1.1.1` doit renvoyer l'IP du VPS.

---

## 2. Système & dépendances

```bash
apt update && apt upgrade -y
apt install -y curl git ufw vim htop

# Docker + plugin compose officiel
curl -fsSL https://get.docker.com | sh
docker --version          # ≥ 25.x
docker compose version    # plugin v2

# Le service docker démarre tout seul
systemctl enable --now docker
```

---

## 3. Firewall

Seuls 22, 80, 443 sont ouverts. Tout le reste (MySQL, Redis, PHP-FPM, Vite) reste interne au réseau Docker.

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
ufw status verbose
```

---

## 4. Caddy reverse proxy (façade unique)

Caddy 2.8 tourne dans son propre stack, en façade des deux stacks applicatives. Il gère le TLS Let's Encrypt automatique pour tous les domaines.

```bash
mkdir -p /root/proxy
cd /root/proxy
```

Crée `/root/proxy/Caddyfile` :

```caddyfile
{
    email TON_EMAIL@example.com

    servers {
        max_header_size 16KB
    }
}

# Production + test partagent actuellement la même app
# (à séparer quand /opt/rocketpi-prod et /opt/rocketpi-test seront en place)
rocketpi.pro, www.rocketpi.pro, rocketpi-test.pro, www.rocketpi-test.pro {
    encode zstd gzip

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Content-Type-Options "nosniff"
        Referrer-Policy "strict-origin-when-cross-origin"
        -Server
        -X-Powered-By
    }

    @www host www.rocketpi.pro www.rocketpi-test.pro
    redir @www https://{labels.1}.{labels.0}{uri} permanent

    reverse_proxy nginx:80 {
        header_up X-Forwarded-Proto {scheme}
        header_up X-Forwarded-For {remote_host}
        header_up Host {host}
    }
}
```

Crée `/root/proxy/docker-compose.yml` :

```yaml
services:
  caddy:
    image: caddy:2.8-alpine
    container_name: proxy-caddy
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
      - "443:443/udp"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy_data:/data
      - caddy_config:/config
    networks:
      - proxy

volumes:
  caddy_data:
  caddy_config:

networks:
  proxy:
    external: true
    name: proxy
```

Crée le réseau Docker partagé puis démarre Caddy :

```bash
docker network create proxy
cd /root/proxy && docker compose up -d
docker compose logs -f caddy
```

À la première résolution, Caddy obtient les certs Let's Encrypt automatiquement et les stocke dans le volume `caddy_data`.

> ⚠️ Si tu redémarres souvent en testant, attention au rate-limit Let's Encrypt (5 certs par domaine et par semaine).

---

## 5. Stack applicative

```bash
cd /root
git clone git@github.com:fabdj/RocketPi-Shardfall-Protocol.git
cd RocketPi-Shardfall-Protocol
```

> Cible long terme : `/opt/rocketpi-prod` (prod) et `/opt/rocketpi-test` (dev). Pour l'instant la stack tourne depuis `/root/RocketPi-Shardfall-Protocol` en mode dev override.

Copie et adapte le `.env` :

```bash
cp .env.example .env
vim .env
```

Variables à ajuster impérativement :

| Variable           | Valeur attendue                              |
|--------------------|----------------------------------------------|
| `APP_ENV`          | `local` (dev override) ou `production`       |
| `APP_KEY`          | `base64:...` — `php artisan key:generate`    |
| `APP_URL`          | `https://rocketpi.pro`                       |
| `APP_DEBUG`        | `false` en prod, `true` en dev               |
| `MYSQL_ROOT_PASSWORD` / `MYSQL_PASSWORD` | mots de passe forts uniques     |
| `REDIS_PASSWORD`   | mot de passe fort — **identique** côté app et démon Redis |

Si tu n'as pas encore d'`APP_KEY`, lance-le après le boot initial :

```bash
docker compose run --rm laravel-app php artisan key:generate --show
# puis colle la valeur dans .env
```

---

## 6. Démarrage de la stack

Le service `installer` (override dev) s'occupe d'orchestrer composer/npm/migrate.

```bash
docker compose up -d --build
docker compose logs -f installer
```

Attends que `installer` exit avec code 0. Les autres services attendent `service_completed_successfully` avant de démarrer.

Vérifie :

```bash
docker compose ps
curl -sk https://rocketpi.pro/ | head
```

### Build des assets pour servir hors localhost

En mode dev, Vite sert les assets depuis `localhost:5173`, ce qui ne marche **pas** quand un navigateur externe charge `rocketpi.pro`. Build prod-style à chaque déploiement :

```bash
docker compose exec vite npm run build
rm -f public/hot          # bascule Laravel vers public/build/
```

Pour re-passer en HMR local (depuis un SSH tunnel par ex.) :

```bash
docker compose exec vite npm run dev   # recrée public/hot
```

---

## 7. Seeders & comptes admin

```bash
docker compose exec laravel-app php artisan db:seed
```

Crée :
- 1 super_admin via `ADMIN_*` du `.env`
- 8 opérateurs, 2 bannières, 5 missions
- saisons leaderboards + rewards par tier
- 50 paliers Battle Pass + 10 achievements

---

## 8. Sauvegardes

> ⏳ **À mettre en place** — pas encore automatisé.

### MySQL — dump quotidien

```bash
cat > /root/backups/dump-mysql.sh <<'SH'
#!/bin/bash
set -e
TS=$(date +%F-%H%M)
mkdir -p /root/backups/mysql
docker compose -f /root/RocketPi-Shardfall-Protocol/docker-compose.yml \
  exec -T mysql sh -c 'exec mysqldump --single-transaction \
  -u root -p"$MYSQL_ROOT_PASSWORD" --all-databases' \
  | gzip > /root/backups/mysql/dump-$TS.sql.gz
# garde 14 jours
find /root/backups/mysql -name 'dump-*.sql.gz' -mtime +14 -delete
SH
chmod +x /root/backups/dump-mysql.sh
echo "30 3 * * * /root/backups/dump-mysql.sh >> /var/log/rocketpi-mysql-backup.log 2>&1" \
  | crontab -
```

### Redis — snapshot RDB

Redis fait déjà des snapshots automatiques (RDB par défaut). Copie-les périodiquement :

```bash
cat > /root/backups/dump-redis.sh <<'SH'
#!/bin/bash
set -e
TS=$(date +%F-%H%M)
mkdir -p /root/backups/redis
docker run --rm -v rocketpi_redis_data:/data:ro alpine \
  sh -c "cp /data/dump.rdb /backup/dump-$TS.rdb" \
  -v /root/backups/redis:/backup
find /root/backups/redis -name 'dump-*.rdb' -mtime +7 -delete
SH
chmod +x /root/backups/dump-redis.sh
```

(à ajouter au crontab quotidien)

---

## 9. phpMyAdmin — durcissement

Actuellement phpMyAdmin tourne en interne sur le réseau Docker `proxy`. Pour l'exposer en HTTPS protégé :

1. Ajoute un sous-domaine `db.rocketpi.pro` → IP du VPS
2. Dans `Caddyfile`, ajoute un bloc :

   ```caddyfile
   db.rocketpi.pro {
       basicauth {
           admin $2a$14$HASH_BCRYPT_GENERE
       }
       # Filtrage IP optionnel mais recommandé en prod
       @allowed remote_ip 1.2.3.4 5.6.7.8
       handle @allowed {
           reverse_proxy phpmyadmin:80
       }
       respond 403
   }
   ```

   Génère le hash : `docker run --rm caddy:2.8-alpine caddy hash-password --plaintext 'tonmotdepasse'`

3. Reload : `docker compose -f /root/proxy/docker-compose.yml exec caddy caddy reload --config /etc/caddy/Caddyfile`

> Sur la stack dev (`db.rocketpi-test.pro`) garde au moins le basicauth ; pour la prod, ajoute l'IP whitelist.

---

## 10. Logs & monitoring

```bash
# Tail des logs en live
docker compose logs -f laravel-app
docker compose logs -f queue-worker
docker compose -f /root/proxy/docker-compose.yml logs -f caddy

# Vérifier scheduler
tail -F storage/logs/leaderboard-reset.log

# Espace disque
df -h
docker system df
```

---

## 11. Mise à jour & déploiement

```bash
cd /root/RocketPi-Shardfall-Protocol
git pull
docker compose up -d --build
docker compose exec vite npm run build && rm -f public/hot
docker compose exec laravel-app php artisan migrate --force
docker compose exec laravel-app php artisan optimize:clear
```

Pour zéro-downtime, on fera plus tard une stack `/opt/rocketpi-prod` puis on basculera Caddy via `reload`. Pour l'instant, ~5 s d'interruption au rebuild laravel-app.

---

## 12. Checklist post-install

- [ ] DNS résolus (`dig +short rocketpi.pro`)
- [ ] UFW actif, seuls 22/80/443 ouverts
- [ ] Caddy a obtenu les 4 certs (`docker compose logs caddy | grep "certificate obtained"`)
- [ ] `https://rocketpi.pro/login` renvoie 200 avec le bundle `/build/assets/app-*.js`
- [ ] `docker compose exec laravel-app php artisan tinker --execute='echo Redis::connection()->ping();'` renvoie 1
- [ ] `docker compose exec laravel-app vendor/bin/pest` → 110/110 green
- [ ] Cron `dump-mysql.sh` actif
- [ ] phpMyAdmin durci (basicauth + IP whitelist en prod)
