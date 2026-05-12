# RocketPi: Shardfall Protocol — Unity Client

Client WebGL embarqué dans la page Inertia `/play` du backend Laravel.

> ⚠️ **Avant tout dev** : lire [CLAUDE.md](./CLAUDE.md) — contrat d'interface, sécurité,
> conventions.

## Prérequis

- **Unity Hub** + **Unity 6 LTS** (6000.3.12f1 ou supérieur)
- Module **WebGL Build Support** installé via Unity Hub
- (Optionnel) **Rider** ou **VS Code + extension Unity** pour l'IDE

## Premier setup

1. Ouvrir Unity Hub → "Add project from disk" → sélectionner ce dossier `unity-client/`
2. Unity génère `Library/`, `Temp/`, etc. (ignorés par git)
3. Ouvrir la scène `Assets/Scenes/Bootstrap.unity` (à créer au premier lancement —
   poser un GameObject vide nommé `RocketpiBridge` avec le component
   `Rocketpi.Bridge.RocketpiBridge` attaché)
4. Player Settings → WebGL → Compression : `Brotli` (prod) ou `Disabled` (dev local)

## Build WebGL

### Depuis l'éditeur

`Tools > RocketPi > Build WebGL` (menu créé par `Assets/Scripts/Editor/BuildWebGL.cs`)

Le build est exporté dans `../public/unity/` (le repo Laravel le sert directement).

### En CLI (CI / scripts)

```bash
/opt/unity/Editor/Unity \
  -batchmode -quit -nographics \
  -projectPath "$(pwd)" \
  -executeMethod Rocketpi.Editor.BuildWebGL.Build \
  -logFile build.log
```

## Test en local

Une fois le build copié dans `../public/unity/` :

```bash
# Depuis la racine du repo Laravel (../)
docker compose up vite laravel-app
# Puis ouvrir http://localhost:8000/play (en étant loggé)
```

Le composant `<UnityCanvas/>` côté React détecte les fichiers, instancie Unity, et
appelle `RocketpiBridge.OnConfig(...)` une fois l'instance prête.

## Arborescence

```
unity-client/
├── Assets/
│   ├── Plugins/
│   │   └── WebGL/
│   │       └── RocketpiBridge.jslib    # Bridge JS ↔ C#
│   ├── Scenes/
│   │   └── Bootstrap.unity              # Scène d'entrée (à créer)
│   ├── Scripts/
│   │   ├── Bridge/
│   │   │   ├── RocketpiBridge.cs       # Singleton MonoBehaviour
│   │   │   └── SessionPayload.cs       # DTOs JSON
│   │   ├── Editor/
│   │   │   └── BuildWebGL.cs           # Script Tools > RocketPi > Build
│   │   ├── Gameplay/                    # (à créer — opérateurs, capacités)
│   │   ├── Network/                     # (à créer — Photon Fusion)
│   │   └── UI/                          # (à créer — HUD, menus)
│   └── Settings/                        # URP / WebGL templates
├── ProjectSettings/                     # Unity (versionné)
├── Packages/                            # manifest.json (versionné), Library cache (ignoré)
├── docs/
│   └── INTEGRATION.md                   # Contrat d'interface détaillé
├── CLAUDE.md
├── README.md
└── .gitignore
```

## Communication avec le backend Laravel

Toute la logique économique reste **côté serveur** (Laravel). Unity utilise :

- **REST API** : `${apiBaseUrl}/api/unity/*` (token Bearer injecté par `OnConfig`)
- **Bridge synchrone** : `unityInstance.SendMessage` (JS → Unity) et jslib (Unity → JS)

Voir [CLAUDE.md](./CLAUDE.md) section "Contrat d'interface" pour les signatures complètes.

## Phase actuelle

- ✅ Scaffolding bridge JS ↔ C# en place
- ✅ DTOs sérialisation prêtes
- ✅ Script build CLI prêt
- ⏳ Scène Bootstrap à créer dans l'éditeur (manuel)
- ⏳ Gameplay : opérateurs, mouvements, tir (Phase 4-5)
- ⏳ Photon Fusion : matchmaking + sync réseau (Phase 5)
