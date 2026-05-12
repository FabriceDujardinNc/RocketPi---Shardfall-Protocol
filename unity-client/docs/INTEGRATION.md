# Contrat d'intégration JS ↔ Unity

Ce document décrit le contrat complet entre le client Unity WebGL et l'hôte
Laravel/Inertia. Toute modification doit être appliquée **simultanément** côté
Unity et côté Laravel.

## Vue d'ensemble du flow

```
┌─────────────────────┐   1. Pageload /play       ┌────────────────────┐
│ Laravel (Inertia)   │ ──────────────────────────│ Browser / React    │
│ PlayController      │   props rank/daily/...    │ Pages/Player/Play  │
└─────────────────────┘                            └─────────┬──────────┘
                                                             │ 2. mount <UnityCanvas/>
                                                             ▼
                                                   ┌────────────────────┐
                                                   │ window.rocketpi.*  │ (rocketpi-bridge.ts)
                                                   │ + Unity instance   │
                                                   └─────────┬──────────┘
                                                             │ 3. NotifyReady (jslib)
                                                             ▼
┌─────────────────────┐   4. SendMessage OnConfig ┌────────────────────┐
│ React (rocketpi)    │ ──────────────────────────│ RocketpiBridge.cs  │
│  apiToken Bearer    │                            │ (singleton Unity)  │
└─────────────────────┘                            └─────────┬──────────┘
                                                             │ 5. Gameplay
                                                             ▼
                                                   POST /api/unity/session/start
                                                   POST /api/unity/match/result
```

## 1. Côté Laravel — exposer le bridge

### Module JS `resources/js/lib/rocketpi-bridge.ts`

```ts
export function installBridge(handlers: {
  onMatchFinished?(payload: MatchResultPayload): void;
  onRequestReload?(): void;
  onLog?(level: string, message: string): void;
  onReady?(): void;
}): () => void;  // returns uninstall fn
```

Doit installer `window.rocketpi = { onReady, onMatchFinished, onRequestReload, onLog }`.
À utiliser dans `useEffect` du `<UnityCanvas/>` avec cleanup au unmount.

### Composant React `<UnityCanvas/>`

Props :
- `apiToken: string` — Bearer Sanctum injecté depuis `PlayController` props
- `apiBaseUrl: string` — Origin de l'API
- `userId: number`
- `mode?: 'deathmatch' | 'pve' | ...`
- `onMatchFinished?(p: MatchResultPayload): void` — callback parent

Responsabilités :
1. Charger `/unity/Build/Build.loader.js` (cache-busté via `/unity/manifest.json`)
2. Appeler `createUnityInstance(canvas, config)`
3. Sur `window.rocketpi.onReady()` → `unityInstance.SendMessage('RocketpiBridge', 'OnConfig', JSON.stringify({...}))`
4. Quand le joueur clique "Lancer" → call POST `/api/unity/session/start` puis
   `unityInstance.SendMessage('RocketpiBridge', 'OnSessionStart', JSON.stringify({...}))`
5. Sur `window.rocketpi.onMatchFinished(payload)` → `router.reload({ only: ['history', 'rank'] })`

## 2. Côté Unity — recevoir et émettre

### Réception JS → Unity (SendMessage)

GameObject cible : `RocketpiBridge` (singleton de scène)

```csharp
public void OnConfig(string json);          // ConfigPayload
public void OnSessionStart(string json);    // SessionPayload
public void OnSessionAbort(string _);       // empty
public void OnReceiveMessage(string json);  // generic { type, data }
```

### Émission Unity → JS (jslib)

```csharp
RocketpiNotifyReady();
RocketpiSubmitMatchResult(string json);     // MatchResultPayload
RocketpiRequestReload();
RocketpiLog(string level, string message);
```

## 3. Format des payloads JSON

### ConfigPayload (JS → Unity)

```json
{
  "apiBaseUrl": "https://rocketpi.pro",
  "apiToken": "1|abc...",
  "userId": 42,
  "locale": "fr",
  "photonAppId": "abc-123-def"
}
```

### SessionPayload (JS → Unity)

```json
{
  "sessionToken": "deadbeef...",
  "mode": "deathmatch",
  "rankType": "ranked",
  "operatorUsedId": 7
}
```

Note JsonUtility Unity : pas de support `null` pour les `int`. Convention : `-1`
signifie "absent". Côté React, omettre la clé est OK car JsonUtility ignore les
champs absents (gardent leur default C#).

### MatchResultPayload (Unity → JS, et POST `/api/unity/match/result`)

```json
{
  "sessionToken": "deadbeef...",
  "score": 12500,
  "kills": 17,
  "deaths": 9,
  "assists": 4,
  "won": true,
  "isMvp": false,
  "durationSeconds": 540
}
```

## 4. Sécurité — checks obligatoires

- Le `apiToken` est éphémère (TTL 1h), généré par `PlayController` via
  `$user->createToken('unity-webgl', ['unity:*'], expiresAt: now()->addHour())`.
- Unity ne stocke JAMAIS le token en PlayerPrefs / localStorage. En mémoire uniquement.
- Toute écriture (`match/result`, `score/submit`) est revalidée serveur
  (`MatchService::finish` avec anti-cheat).
- Le `sessionToken` est usage unique : `MatchService::finish` le marque `finished`,
  un second appel renvoie une erreur 422.

## 5. Versionning du contrat

| Version | Date | Changement |
|---------|------|------------|
| 1.0 | 2026-05-12 | Initial scaffold (NotifyReady, OnConfig, OnSessionStart, SubmitMatchResult) |

Toute modification : bumper la version, mettre à jour les TROIS implémentations
(jslib, C#, TS) **dans le même commit**.
