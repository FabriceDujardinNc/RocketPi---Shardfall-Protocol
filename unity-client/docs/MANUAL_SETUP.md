# Étapes manuelles dans l'éditeur Unity

Checklist pas-à-pas des actions à faire **dans Unity Editor** pour passer du scaffolding
actuel à un build WebGL jouable. Ces étapes ne peuvent pas être automatisées sans risquer
de corrompre le projet (références entre GameObjects et prefabs).

**Temps estimé : ~30 minutes** pour quelqu'un qui connaît Unity, ~1h pour un débutant.

---

## Phase A — Premier lancement (5 min)

### A.1 Installer Unity
- [ ] Télécharger **Unity Hub** : https://unity.com/download
- [ ] Installer **Unity 6 LTS** (6000.3.12f1+) via Unity Hub
- [ ] Cocher le module **WebGL Build Support** pendant l'installation
  (sinon : Unity Hub → engrenage à côté de la version → Add Modules → WebGL Build Support)

### A.2 Ouvrir le projet
- [ ] Unity Hub → "Add project from disk" → sélectionner `unity-client/`
- [ ] Premier ouverture : Unity télécharge les packages (~5 min selon connexion)
- [ ] À l'ouverture, le script `FirstRunSetup` s'exécute automatiquement et configure :
  - PlayerSettings : `companyName=RocketPi`, `productName=Shardfall Protocol`
  - WebGL : compression Brotli, template `RocketPi`, mémoire 512 MB
  - Colorspace : Linear
- [ ] **Vérifier dans la Console** : tu dois voir `[RocketPi] First-run setup done.`

### A.3 Switcher la plateforme cible
- [ ] `File > Build Profiles` (ou `Build Settings` sur les anciennes versions)
- [ ] Sélectionner **WebGL** dans la liste
- [ ] Cliquer **Switch Platform** (réimporte tous les assets, peut prendre 5-10 min la première fois)
- [ ] Attendre que la barre de progression en bas à droite disparaisse

**✅ Validation Phase A** : tu peux ouvrir l'onglet Console sans erreur rouge.

---

## Phase B — Scaffolders automatiques (2 min)

### B.1 Générer le roster d'opérateurs
- [ ] Menu **`Tools > RocketPi > Scaffold Roster`**
- [ ] Vérifier dans Console : `[RocketPi] Roster scaffold: 8 créés, 0 déjà existants.`
- [ ] Vérifier dans Project window : `Assets/Resources/Operators/` contient 8 fichiers :
  - `Op_VX01_Vex.asset` (Legendary, ORBIT, 100 HP)
  - `Op_HL02_Halo.asset` (Epic, ORBIT, 110 HP)
  - `Op_DR03_Drift.asset` (Rare, ORBIT, 105 HP)
  - `Op_CR04_Crag.asset` (Legendary, FERRO, 140 HP)
  - `Op_BK05_Brick.asset` (Epic, FERRO, 130 HP)
  - `Op_IR06_Iron.asset` (Common, FERRO, 120 HP)
  - `Op_WR07_Wraith.asset` (Epic, VEIL, 90 HP)
  - `Op_EC08_Echo.asset` (Rare, VEIL, 95 HP)

### B.2 Générer les scènes
- [ ] Menu **`Tools > RocketPi > Scaffold Scenes`**
- [ ] Vérifier dans Console : `[RocketPi] Scenes scaffolded.`
- [ ] Vérifier dans `Assets/Scenes/` : `Bootstrap.unity` et `Training.unity` existent
- [ ] Vérifier `File > Build Profiles > Scene List` : les 2 scènes sont cochées dans l'ordre
  1. `Scenes/Bootstrap` (index 0)
  2. `Scenes/Training` (index 1)

**✅ Validation Phase B** : double-clic sur `Bootstrap.unity` → tu vois un GameObject `RocketpiBridge` dans la Hierarchy.

---

## Phase C — Création des 2 prefabs (10 min)

### C.1 Prefab `PracticeTarget`

- [ ] Dans la Hierarchy : `GameObject > 3D Object > Cube`, renommer `PracticeTarget`
- [ ] Dans Inspector, ajouter ces components :
  - `HealthSystem` (Max Hp = **10**)
  - `PracticeTarget` (Score On Kill = **100**, Respawn Delay = **1.5**, Respawn After Death = ✅)
- [ ] Configurer :
  - **Tag** : `PracticeTarget` (créer le tag si absent : Inspector → Tag dropdown → Add Tag…)
  - **Layer** : `PracticeTarget` (déjà créé par TagManager)
  - Scale : `(1, 1, 1)`
- [ ] Glisser le GameObject **depuis la Hierarchy vers `Assets/Prefabs/`** (créer le dossier si besoin)
  → ça crée `Assets/Prefabs/PracticeTarget.prefab`
- [ ] Supprimer le GameObject de la Hierarchy (le prefab suffit)

### C.2 Prefab `Weapon_AssaultRifle`

- [ ] Dans la Hierarchy : `GameObject > Create Empty`, renommer `Weapon_AssaultRifle`
- [ ] Dans Inspector, ajouter `HitscanWeapon` avec les défauts :
  - Magazine Size = **30**
  - Fire Rate = **8**
  - Reload Time = **2**
  - Base Damage = **18**
  - Max Range = **80**
  - Spread Angle = **0.6**
  - Headshot Multiplier = **2**
- [ ] Glisser un transform enfant vide nommé `Muzzle` (position `(0, 0, 0.5)` relatif au parent)
- [ ] Drag le `Muzzle` dans le champ `_muzzle` du HitscanWeapon
- [ ] Sauver comme prefab dans `Assets/Prefabs/Weapon_AssaultRifle.prefab`

### C.3 Assigner le PracticeTarget au spawner

- [ ] Ouvrir la scène `Training.unity`
- [ ] Hierarchy → sélectionner `TargetSpawner`
- [ ] Inspector → `Target Prefab` → drag `Assets/Prefabs/PracticeTarget.prefab`

### C.4 Assigner l'arme à au moins un opérateur

- [ ] Project window → sélectionner `Op_VX01_Vex.asset`
- [ ] Inspector → `Weapon Prefab` → drag `Assets/Prefabs/Weapon_AssaultRifle.prefab`
- [ ] Répéter pour les autres opérateurs si souhaité (ou laisser pour plus tard)

**✅ Validation Phase C** : les 2 prefabs apparaissent dans `Assets/Prefabs/`, le TargetSpawner a une référence non-null, au moins un OperatorData a une arme.

---

## Phase D — Brancher les références UI (10 min)

La scène `Training.unity` contient un `Canvas` avec les 3 controllers UI déjà attachés,
mais leurs champs `[SerializeField]` sont vides. Il faut les remplir manuellement.

### D.1 Construire la hiérarchie UI

Dans la Hierarchy, sous `Canvas`, créer manuellement :

```
Canvas
├── HUD/                                     ← UI in-match (toujours visible)
│   ├── HpLabel       (TextMeshPro - Text, ancre bas-gauche)
│   ├── HpBar         (Slider, ancre bas-gauche)
│   ├── AmmoLabel     (TextMeshPro - Text, ancre bas-droite)
│   ├── TimerLabel    (TextMeshPro - Text, ancre haut-centre)
│   └── ScoreLabel    (TextMeshPro - Text, ancre haut-droite)
├── MainMenu/                                ← écran d'accueil
│   ├── StatusLabel   (TextMeshPro - Text)
│   └── StartButton   (Button + TMP child "Lancer training")
└── MatchSummary/                            ← fin de match (initialement caché)
    ├── ScoreLabel    (TMP)
    ├── KillsLabel    (TMP)
    ├── DeltaLabel    (TMP)
    ├── TierLabel     (TMP)
    ├── ErrorLabel    (TMP, petite police rouge)
    ├── ReplayButton  (Button)
    └── QuitButton    (Button)
```

**Astuce** : `GameObject > UI > Text - TextMeshPro` génère un TMP_Text. Au premier ajout
Unity propose d'importer les TMP Essentials → accepter.

### D.2 Brancher HudController

- [ ] Canvas → sélectionner `HudController` dans Inspector
- [ ] `_player` ← drag `Player` (GameObject racine de la scène)
- [ ] `_match`  ← drag `TrainingMatchManager`
- [ ] `_hpLabel`    ← drag `Canvas/HUD/HpLabel`
- [ ] `_hpBar`      ← drag `Canvas/HUD/HpBar`
- [ ] `_ammoLabel`  ← drag `Canvas/HUD/AmmoLabel`
- [ ] `_timerLabel` ← drag `Canvas/HUD/TimerLabel`
- [ ] `_scoreLabel` ← drag `Canvas/HUD/ScoreLabel`

### D.3 Brancher MainMenuController

- [ ] Canvas → sélectionner `MainMenuController` dans Inspector
- [ ] `_root`                ← drag `Canvas/MainMenu`
- [ ] `_startTrainingButton` ← drag `Canvas/MainMenu/StartButton`
- [ ] `_statusLabel`         ← drag `Canvas/MainMenu/StatusLabel`

### D.4 Brancher MatchSummaryController

- [ ] Canvas → sélectionner `MatchSummaryController` dans Inspector
- [ ] `_match`        ← drag `TrainingMatchManager`
- [ ] `_root`         ← drag `Canvas/MatchSummary`
- [ ] `_scoreLabel`   ← drag `Canvas/MatchSummary/ScoreLabel`
- [ ] `_killsLabel`   ← drag `Canvas/MatchSummary/KillsLabel`
- [ ] `_deltaLabel`   ← drag `Canvas/MatchSummary/DeltaLabel`
- [ ] `_tierLabel`    ← drag `Canvas/MatchSummary/TierLabel`
- [ ] `_errorLabel`   ← drag `Canvas/MatchSummary/ErrorLabel`
- [ ] `_replayButton` ← drag `Canvas/MatchSummary/ReplayButton`
- [ ] `_quitButton`   ← drag `Canvas/MatchSummary/QuitButton`
- [ ] `_mainMenu`     ← drag `Canvas/MainMenuController` (le component, pas le GameObject)

### D.5 Brancher le Player

- [ ] Hierarchy → sélectionner `Player`
- [ ] PlayerController → `_operator` ← drag `Op_VX01_Vex.asset`
- [ ] PlayerController → `_weaponSocket` ← drag `Player/Camera/WeaponSocket`
- [ ] PlayerController → `_cameraOverride` ← drag `Player/Camera`

### D.6 Brancher le TrainingMatchManager

- [ ] Hierarchy → sélectionner `TrainingMatchManager`
- [ ] `_player` ← drag `Player`

**✅ Validation Phase D** : aucun champ avec un `None (Type)` rouge dans les Inspector des
controllers. Sauver la scène (`Ctrl+S`).

---

## Phase E — Premier build & test (5 min)

### E.1 Build WebGL local

- [ ] Menu **`Tools > RocketPi > Build WebGL (dev)`**
- [ ] Attendre la fin du build (5-15 min selon machine, GC.Mark + IL2CPP)
- [ ] Vérifier dans Console : `[RocketPi] Build OK : XX MB, ...`
- [ ] Vérifier que `../public/unity/` contient :
  - `Build/` (4 fichiers : `.loader.js`, `.data.unityweb`, `.framework.js.unityweb`, `.wasm.unityweb`)
  - `manifest.json`
  - `TemplateData/` (style.css, favicon)
  - `index.html`

### E.2 Lancer Laravel

```bash
# Depuis le dossier racine du repo Laravel
docker compose up vite laravel-app
```

### E.3 Tester `/play`

- [ ] Se logger sur `http://localhost:8000`
- [ ] Naviguer vers `/play`
- [ ] Le canvas Unity doit se charger (barre de progression Unity)
- [ ] Une fois chargé, un menu "Lancer training" apparaît
- [ ] Cliquer → la souris se lock dans le canvas, le joueur peut se déplacer (WASD + souris)
- [ ] Cibles apparaissent autour, tirer dessus pour incrémenter le score
- [ ] Après 2 min, écran de fin avec score + delta de rang
- [ ] La page Laravel se recharge automatiquement avec l'historique mis à jour

**✅ Validation Phase E** : tu vois ton match dans la table "Historique récent" de `/play`
avec le score que tu as fait.

---

## Troubleshooting

### Le canvas Unity reste sur "Build non disponible"
- Vérifier que `public/unity/manifest.json` existe côté Laravel
- Ouvrir l'onglet Network dans le devtools : la requête GET `/unity/manifest.json` doit renvoyer 200

### Erreur "401 Unauthorized" sur `/api/unity/session/start`
- Le token Sanctum a expiré (TTL 1h) : recharger `/play` pour en générer un nouveau
- Vérifier dans la table `personal_access_tokens` qu'il y a bien un token `name=unity-webgl`

### Erreur "Durée match trop courte"
- Côté Laravel, `MatchService::MIN_DURATION_SECONDS = 30`. Ne pas finir un match en moins de 30s.

### Le joueur tombe à travers le sol
- Vérifier que le `Ground` a un `MeshCollider` (créé automatiquement par GameObject.CreatePrimitive)
- Vérifier que le CharacterController du Player a une hauteur > 0 et un radius > 0

### Le tir ne touche rien
- Vérifier que `HitscanWeapon._hittableLayers` n'est pas vide (par défaut `Everything`)
- Vérifier que les cibles ont bien un `Collider` (le Cube en a un par défaut)

### Console plein d'erreurs "Newtonsoft.Json not found"
- Package Manager → "Add package by name" → `com.unity.nuget.newtonsoft-json` → version `3.2.1`

---

## Et après ?

Une fois la phase E validée, le mode training est end-to-end. Pour ajouter du contenu :

- **Plus d'opérateurs jouables** : assigner WeaponPrefab + AbilityPrefabs sur chaque OperatorData
- **Sélection d'opérateur** : étendre `MainMenuController` avec un grid de portraits
- **Visuels** : importer assets depuis Asset Store ou Kenney.nl (gratuits)
- **Audio** : ajouter SFX sur les tirs (AudioSource sur les armes)

Pour la **Phase 5 (Multijoueur)**, voir [unity-client/CLAUDE.md](../CLAUDE.md) section
"Réseau Photon Fusion" — la couche est prévue mais pas encore scaffoldée vu que le
mode training valide déjà la chaîne autoritaire serveur sans complexité réseau.
