# RocketPi: Shardfall Protocol — Unity Client

Client WebGL embarqué dans la page Inertia `/play` du backend Laravel.

> ⚠️ **Avant tout dev** : lire [CLAUDE.md](./CLAUDE.md) — contrat d'interface, sécurité,
> conventions.

## Prérequis

- **Unity Hub** + **Unity 6 LTS** (6000.3.12f1 ou supérieur)
- Module **WebGL Build Support** installé via Unity Hub
- (Optionnel) **Rider** ou **VS Code + extension Unity** pour l'IDE

## Setup en 5 minutes (premier lancement)

1. Unity Hub → "Add project from disk" → sélectionner ce dossier `unity-client/`
2. Premier ouverture : Unity génère `Library/`, télécharge les packages (URP, Input System,
   Newtonsoft.Json, TestFramework). Le script `FirstRunSetup` configure PlayerSettings
   (companyName, productName, WebGL Brotli, template RocketPi) automatiquement.
3. Lancer dans cet ordre les 2 scaffolders :
   - **`Tools > RocketPi > Scaffold Roster`** → crée 8 OperatorData `.asset` dans
     `Assets/Resources/Operators/` (matche le roster Laravel : Vex, Halo, Drift, Crag, Brick,
     Iron, Wraith, Echo)
   - **`Tools > RocketPi > Scaffold Scenes`** → crée `Bootstrap.unity` (singleton
     RocketpiBridge) et `Training.unity` (ground, player capsule, camera, spawner, HUD canvas),
     ajoute les 2 scènes au Build Settings
4. Player Settings → Platform → cocher **WebGL**, puis cliquer "Switch Platform" (peut prendre 5-10 min)
5. **`Tools > RocketPi > Build WebGL (dev)`** → export dans `../public/unity/`
6. Côté Laravel, ouvrir `/play` en local : le canvas Unity se charge avec le menu "Lancer training"

## Ce que tu dois encore faire MANUELLEMENT dans l'éditeur Unity

Le scaffolding crée la structure et les GameObjects principaux, mais quelques liens visuels
sont à brancher à la main car les **références entre GameObjects** dépendent de prefabs et
de placement libre, peu serializable hors éditeur :

1. **Dans `Training.unity`** :
   - Sur le `Player`, glisser dans le champ `Operator` du `PlayerController` un OperatorData
     (ex. `Op_VX01_Vex.asset`)
   - Sur le `Player`, glisser dans `WeaponSocket` le GameObject enfant `Camera/WeaponSocket`
   - Sur le `Canvas` (HudController/MainMenuController/MatchSummaryController), brancher
     les TMP_Text / Slider / Button enfants en draguant les références dans l'inspector
2. **Créer un prefab de cible** :
   - `GameObject > 3D Object > Cube`, nommer `PracticeTarget`
   - Ajouter `HealthSystem` (10 HP), `PracticeTarget`, tag "PracticeTarget", layer "PracticeTarget"
   - Sauver comme prefab dans `Assets/Prefabs/`
   - L'assigner au `TargetSpawner._targetPrefab` dans la scène Training
3. **Créer un prefab d'arme** :
   - `GameObject > Create Empty`, nommer `Weapon_AssaultRifle`
   - Ajouter `HitscanWeapon` (paramètres par défaut conviennent)
   - Sauver comme prefab dans `Assets/Prefabs/`
   - L'assigner au `OperatorData._WeaponPrefab` de chaque opérateur souhaité

Une fois ces 3 étapes faites, le mode training est jouable.

## Build CLI (CI / déploiement)

```bash
/opt/unity/Editor/Unity \
  -batchmode -quit -nographics \
  -projectPath "$(pwd)" \
  -executeMethod Rocketpi.Editor.BuildWebGL.Build \
  -logFile build.log
```

## Test depuis Laravel

Une fois le build copié dans `../public/unity/` :

```bash
docker compose up vite laravel-app
# Visiter http://localhost:8000/play (en étant loggé)
```

Le composant `<UnityCanvas/>` côté React lit `../public/unity/manifest.json`,
charge `Build/Build.loader.js`, instancie Unity et appelle `OnConfig` avec le
token Sanctum éphémère injecté par `PlayController`.

## Arborescence

```
unity-client/
├── Assets/
│   ├── Plugins/WebGL/RocketpiBridge.jslib    # Bridge JS ↔ C# (window.rocketpi.*)
│   ├── Resources/Operators/                  # 8 OperatorData .asset (générés)
│   ├── Scenes/                                # Bootstrap.unity + Training.unity (générés)
│   ├── Settings/                              # Sentinel first-run setup
│   ├── WebGLTemplates/RocketPi/               # Template HTML/CSS du canvas
│   └── Scripts/
│       ├── Bridge/                            # RocketpiBridge + DTOs
│       ├── RestClient/                        # RocketpiApiClient + Dtos
│       ├── Gameplay/                          # Player, Health, Weapons, Abilities, Match
│       ├── UI/                                # HUD, MainMenu, Summary
│       ├── Editor/                            # FirstRunSetup, RosterScaffolder, SceneScaffolder, BuildWebGL
│       └── Tests/                             # NUnit EditMode tests
├── ProjectSettings/                           # Versionné (TagManager, EditorSettings, ProjectVersion)
├── Packages/manifest.json                     # Dépendances Unity
├── docs/INTEGRATION.md                        # Contrat JS ↔ Unity
├── CLAUDE.md
├── README.md
└── .gitignore
```

## Stack côté Unity

| Package | Usage |
|---------|-------|
| URP 17.0 | Pipeline de rendu |
| Input System 1.11 | Bindings WASD/souris/gamepad |
| Newtonsoft.Json 3.2 | Sérialisation REST (préféré à JsonUtility côté API) |
| TextMeshPro 3.0 | UI textuelle |
| Test Framework 1.4 | EditMode tests |

## Communication avec le backend Laravel

Toute la logique économique reste **côté serveur** (Laravel). Unity utilise :

- **REST API** : `${apiBaseUrl}/api/unity/*` (token Bearer injecté par `OnConfig`)
- **Bridge synchrone** : `unityInstance.SendMessage` (JS → Unity) et jslib (Unity → JS)

Voir [CLAUDE.md](./CLAUDE.md) section "Contrat d'interface" pour les signatures complètes
et [docs/INTEGRATION.md](./docs/INTEGRATION.md) pour le flow détaillé.

## Phase actuelle

- ✅ Scaffolding bridge JS ↔ C# + REST client + DTOs
- ✅ Scripts gameplay (Player FPS, HealthSystem, 2 types d'armes, 3 abilities)
- ✅ 8 opérateurs (ScriptableObject, alignés avec OperatorSeeder Laravel)
- ✅ Mode Training fonctionnel (TrainingMatchManager + PracticeTarget + Spawner)
- ✅ UI (HUD, MainMenu, MatchSummary)
- ✅ WebGL template
- ✅ Scaffolders Editor (Roster, Scenes, FirstRunSetup, Build)
- ✅ Tests NUnit EditMode (HealthSystem, BridgePayload)
- ⏳ Branchement manuel des prefabs et références UI (cf. section "Setup")
- ⏳ Mode Deathmatch + Photon Fusion (Phase 5)
- ⏳ Assets graphiques réels (modèles, textures, animations, sons)
