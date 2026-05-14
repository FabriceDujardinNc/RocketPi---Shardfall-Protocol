# mcp-rocketpi

Serveur **MCP** (Model Context Protocol) exposant le pipeline 3D RocketPi à
Claude Desktop : roster opérateurs, skins, armes, accessoires, statut de
génération Meshy.ai, et déclenchement de génération.

## Install

```bash
cd tools/mcp-rocketpi
npm install
npm run build
```

## Token API Laravel

Le serveur a besoin d'un token Sanctum avec les abilities adéquates :

| Ability     | Permet                                              |
|-------------|-----------------------------------------------------|
| `mcp:read`  | Lister + voir opérateurs / skins / weapons / accessories, statut |
| `mcp:write` | Déclencher une génération Meshy (facturable)        |

Pour générer un token (en attendant une UI admin dédiée), depuis le projet
Laravel :

```bash
docker compose exec laravel-app php artisan tinker
> $admin = \App\Models\User::where('role', 'admin')->first();
> $admin->createToken('mcp-claude-desktop', ['mcp:read', 'mcp:write'])->plainTextToken;
```

Copie le `plainTextToken` retourné — il n'est plus accessible ensuite.

## Configuration Claude Desktop

Ajoute dans `claude_desktop_config.json` (Win: `%APPDATA%\Claude\claude_desktop_config.json`,
Mac: `~/Library/Application Support/Claude/claude_desktop_config.json`) :

```json
{
  "mcpServers": {
    "rocketpi": {
      "command": "node",
      "args": ["C:/Dev/rocketpi/tools/mcp-rocketpi/dist/server.js"],
      "env": {
        "ROCKETPI_API_BASE_URL": "https://rocketpi.pro",
        "ROCKETPI_API_TOKEN": "1|abcdef..."
      }
    }
  }
}
```

Pour test local sans HTTPS, pointe sur `http://localhost:8000` (Docker dev).

Redémarre Claude Desktop après modif config.

## Tools exposés

| Tool                       | Ability     | Effet                                          |
|----------------------------|-------------|------------------------------------------------|
| `list_operators`           | `mcp:read`  | Roster complet avec statut base mesh           |
| `get_operator`             | `mcp:read`  | Détail opérateur + skins + accessoires liés    |
| `list_operator_skins`      | `mcp:read`  | Skins d'un opérateur (filtre `is_active`)      |
| `list_weapons`             | `mcp:read`  | Toutes armes, filtre `category` optionnel      |
| `list_accessories`         | `mcp:read`  | Tous accessoires, filtre `slot` optionnel      |
| `get_generation_status`    | `mcp:read`  | Statut courant d'une génération (pending → ready) |
| `trigger_generation`       | `mcp:write` | Lance une nouvelle génération Meshy.ai         |

## Dev local

```bash
ROCKETPI_API_BASE_URL=http://localhost:8000 ROCKETPI_API_TOKEN=...  npm run dev
```

(le serveur attend sur stdin/stdout, prévu pour Claude Desktop — pour
tester en CLI utilise le MCP Inspector officiel).

## Sécurité

- Le token est envoyé en `Authorization: Bearer` sur chaque requête.
- N'utilise **jamais** un token avec ability `*` ou un token utilisateur
  classique. Un token MCP a uniquement `mcp:read` et/ou `mcp:write`.
- Le serveur Laravel rate-limite les générations (`throttle:20,60`) — 20
  générations/heure max par token, indépendamment de Claude Desktop.
- Si le token fuit : révoque-le côté Laravel
  ```bash
  docker compose exec laravel-app php artisan tinker
  > \Laravel\Sanctum\PersonalAccessToken::where('name', 'mcp-claude-desktop')->delete();
  ```
