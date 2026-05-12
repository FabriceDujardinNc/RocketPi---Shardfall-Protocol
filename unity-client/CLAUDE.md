# CLAUDE.md — Unity client RocketPi: Shardfall Protocol

Règles à respecter pour toute contribution sur le sous-dossier `unity-client/`.

## Stack & versions

- **Unity 6 LTS** (6000.3.12f1+), backend IL2CPP, cible **WebGL**
- **Photon Fusion** (gratuit ≤ 100 CCU) pour le netcode autoritaire
- **C# 11** (Unity 6 supporte les `record`, `init`, pattern matching avancé)
- Assemblies définitions (`*.asmdef`) à utiliser pour cloisonner :
  `Rocketpi.Bridge`, `Rocketpi.Gameplay`, `Rocketpi.Network`, `Rocketpi.UI`

## Contrat d'interface JS ↔ Unity (NE PAS CASSER)

Le client Unity tourne dans un canvas WebGL embarqué dans la page Inertia `/play`
côté Laravel. Le bridge fonctionne dans les deux sens.

### JS → Unity (`unityInstance.SendMessage`)

Le React appelle `unityInstance.SendMessage(gameObject, method, payload)`. Le GameObject
cible est toujours **`RocketpiBridge`** (singleton de scène, scène `Bootstrap`).

| Méthode C# | Payload JSON | Quand |
|---|---|---|
| `OnConfig` | `{ apiBaseUrl, apiToken, userId, locale }` | Au boot, juste après l'instanciation Unity |
| `OnSessionStart` | `{ sessionToken, mode, rankType, operatorUsedId }` | Quand le joueur clique "Lancer un match" |
| `OnSessionAbort` | `""` (vide) | Si le joueur ferme l'onglet / change de page |
| `OnReceiveMessage` | `{ type, data }` | Notifications push (ex. matchmaking found) |

### Unity → JS (jslib `RocketpiBridge.jslib`)

Le C# appelle des fonctions JS déclarées dans `Assets/Plugins/WebGL/RocketpiBridge.jslib`
qui délèguent à `window.rocketpi.*` (exposé côté Laravel via `resources/js/lib/rocketpi-bridge.ts`).

| Fonction jslib | window.rocketpi.* | Effet attendu |
|---|---|---|
| `RocketpiNotifyReady()` | `onReady()` | Unity est prêt, React peut envoyer `OnConfig` |
| `RocketpiSubmitMatchResult(jsonPayload)` | `onMatchFinished(payload)` | Match terminé, React recharge la page Inertia |
| `RocketpiRequestReload()` | `onRequestReload()` | Match expiré / erreur fatale Unity, retour à `/play` |
| `RocketpiLog(level, message)` | `onLog(level, message)` | Logging cross-domain pour debug (level: info/warn/error) |

**Versionner** ce contrat : tout changement de signature DOIT être reflété simultanément
dans :
- `unity-client/Assets/Plugins/WebGL/RocketpiBridge.jslib`
- `unity-client/Assets/Scripts/Bridge/RocketpiBridge.cs`
- `resources/js/lib/rocketpi-bridge.ts` (côté Laravel)
- `resources/js/Components/Game/UnityCanvas.tsx` (côté Laravel)

## Sécurité — règles non-négociables

1. **Aucune logique économique côté Unity.** Currencies, gacha, rewards, XP : 100% serveur.
   Unity ne fait QUE gameplay/affichage et appelle les API REST validées.
2. **Token API jamais hard-codé.** Toujours injecté via `OnConfig` au boot, stocké en mémoire,
   jamais en PlayerPrefs (effacé à chaque session).
3. **Validation autoritaire serveur.** Tout résultat de match est re-validé côté Laravel
   (`MatchService::finish` avec checks anti-cheat). Unity envoie ses kills/score, le serveur
   décide si c'est plausible.
4. **Pas d'imports natifs non whitelistés** (.dll natifs WebGL = surface d'attaque).
5. **Logs production filtrés.** `RocketpiLog('debug', ...)` ne doit rien envoyer en build release.

## Conventions code

### Namespaces
```
Rocketpi.Bridge       — jslib bridge + DTO sérialisation
Rocketpi.Network      — Photon Fusion + REST clients
Rocketpi.Gameplay     — opérateurs, capacités, armes, projectiles
Rocketpi.UI           — HUD, menus in-game, scoreboard
Rocketpi.Audio        — mixer, voicelines, SFX
Rocketpi.Editor       — scripts éditeur (ne PAS shipper)
```

### Style
- `PascalCase` pour types/methodes publiques, `_camelCase` pour fields privés
- `[SerializeField] private` plutôt que `public` pour exposer aux inspecteurs
- `async UniTask` (UniTask installé) plutôt que `async void` ou coroutines pour les flows réseau
- Pas de `Singleton<T>` exotique : `RocketpiBridge` est le seul singleton, posé manuellement
  dans la scène Bootstrap

### Anti-patterns interdits
- `GameObject.Find(...)` en runtime — utiliser références sérialisées
- `Resources.Load(...)` — préférer Addressables ou références directes
- Allocations en boucle Update (pas de `new Vector3(...)` répété, cacher)
- Public mutable static fields (race conditions)
- `Application.OpenURL` direct (passer par jslib pour respecter le CSP de la page hôte)

## Workflow build & déploiement

### Build local (dev)

```bash
# Depuis Unity Editor : Tools > RocketPi > Build WebGL
# Ou en CLI :
# /opt/unity/Editor/Unity -batchmode -projectPath unity-client/ \
#   -executeMethod Rocketpi.Editor.BuildWebGL.Build -quit
```

Le script `Assets/Scripts/Editor/BuildWebGL.cs` :
- Builde en mode `Development` (sourcemaps + logs)
- Copie le résultat dans `../public/unity/` (le repo Laravel sert directement)
- Génère un manifest `manifest.json` avec hash + timestamp pour cache-busting

### Build prod

Idem mais sans `-developmentBuild`. Compression Brotli (cf. Player Settings).
Cible un dossier de release séparé `../public/unity/v{N}/` pour permettre rollback.

### Cache-busting côté Laravel

`UnityCanvas.tsx` lit `manifest.json` (hash + version) pour ajouter un query string aux URLs
des fichiers `.loader.js` / `.framework.js.unityweb` / `.data.unityweb` / `.wasm.unityweb`.

## Tests

- **Edit Mode tests** (PlayMode/Test Runner) pour la logique de DTO + validation client
- **Play Mode tests** pour les flows Photon (mock NetworkRunner)
- Pas de test Unity dans la CI Laravel (séparation des cycles de release)

## Anti-patterns interdits côté projet

- Pousser le dossier `Library/` (énorme, cache local, dans `.gitignore`)
- Committer un build WebGL (`Build/`, `WebGLBuild/`) — uniquement `../public/unity/` côté Laravel après vérif
- Modifier les versions stack sans accord (Unity LTS, Photon, packages registry)
- Ajouter des assets sous licence non vérifiée (toujours vérifier l'Asset Store)

## Référence rapide — fichiers clés

| Fichier | Rôle |
|---|---|
| `Assets/Scripts/Bridge/RocketpiBridge.cs` | Singleton GameObject `RocketpiBridge`, reçoit les SendMessage |
| `Assets/Scripts/Bridge/SessionPayload.cs` | DTOs : ConfigPayload, SessionPayload, MatchResultPayload |
| `Assets/Plugins/WebGL/RocketpiBridge.jslib` | Bridge JS, délègue à `window.rocketpi.*` |
| `Assets/Scripts/Editor/BuildWebGL.cs` | Script Tools > RocketPi > Build WebGL |
| `docs/INTEGRATION.md` | Contrat d'interface détaillé (côté Unity + Laravel) |

## Phase actuelle

Le client Unity n'est **pas encore branché** sur un build réel. Le scaffolding (bridge,
DTOs, script de build) est en place pour permettre à un développeur Unity d'ouvrir le
projet, créer la scène Bootstrap, et itérer sur le gameplay sans avoir à câbler
l'intégration Laravel.

Côté Laravel, [resources/js/Components/Game/UnityCanvas.tsx](../resources/js/Components/Game/UnityCanvas.tsx)
charge le build WebGL et installe `window.rocketpi.*` via
[resources/js/lib/rocketpi-bridge.ts](../resources/js/lib/rocketpi-bridge.ts).
