# Pipeline Mixamo — Rigger les opérateurs RocketPi

**Objectif :** transformer les 8 meshes Meshy (`.glb` non-riggés, T-pose statique) en
modèles humanoïdes riggés avec animations Idle/Walk/Run/Death, importables dans Unity 6
en mode `Humanoid` Mecanim.

> **Pourquoi Mixamo et pas Meshy ?**
> Meshy ne produit que des meshes statiques. Mixamo (Adobe, gratuit) ajoute un squelette
> humanoid en quelques minutes et fournit des centaines d'animations retargetables.

---

## Pré-requis

- Compte Adobe gratuit (https://www.mixamo.com)
- Les 8 `.glb` sont dans `unity-client/Assets/Models/Operators/Raw/` ✅ (déjà copiés)

---

## A. Auto-Rig de chaque opérateur

À refaire pour chacun des 8 opérateurs : **Vex, Halo, Drift, Crag, Brick, Iron, Wraith, Echo**.

### 1. Préparer le `.glb`

⚠️ Mixamo accepte `.fbx`, `.obj`, `.zip` mais **pas `.glb`**. Conversion rapide :

**Option A — Blender (gratuit) :**
```
1. File > Import > glTF 2.0 (.glb)
2. Sélectionner Vex.glb
3. File > Export > FBX (.fbx)
4. Limit to: Selected Objects, Apply Modifiers, Path Mode: Copy, Embed Textures
5. Sauvegarder sous Vex.fbx
```

**Option B — Online converter :** https://anyconv.com/glb-to-fbx-converter/ (rapide mais pas de réglages).

### 2. Upload sur Mixamo

1. Aller sur https://www.mixamo.com
2. Onglet **Characters** → bouton **Upload Character**
3. Drop le `.fbx` → attendre la jauge d'upload (~30s à 1 min)

### 3. Auto-Rigger

Placer 5 marqueurs sur le mesh :
- **Chin** : sous le menton du mesh
- **Wrists** (gauche + droit) : sur le centre du poignet
- **Elbows** (gauche + droit) : sur le pli du coude
- **Knees** (gauche + droit) : sur la rotule
- **Groin** : entre les jambes, à la base du bassin

Régler :
- **Skeleton LOD** : Standard (65 bones) — recommandé pour Unity Humanoid
- **No fingers** si pas de doigts détaillés dans le mesh (cas Meshy probable) → 25 bones suffisent

Cliquer **Next** → l'auto-rigger calcule le squelette (~1 min) → preview de la T-pose.

### 4. Valider + Download

- Cliquer **Next** → **Finish**
- Onglet **Download** : format `FBX Binary (.fbx)`, Pose `T-pose`, Frame Rate `30`
- Sauvegarder sous `unity-client/Assets/Models/Operators/Rigged/Vex.fbx`

**Répéter pour les 7 autres opérateurs.**

---

## B. Pack d'animations partagées (à faire 1 seule fois)

Les animations Humanoid sont retargetables → un seul pack pour tous les opérateurs.

Sur Mixamo, **avec un personnage déjà uploadé en T-pose** (Vex par exemple) :

| Animation Mixamo | Nom Unity | Loop | Notes |
|---|---|---|---|
| `Breathing Idle` | `Idle.fbx` | Yes | Posture neutre debout |
| `Walking` | `Walk.fbx` | Yes | Cycle 0.5s |
| `Running` | `Run.fbx` | Yes | Cycle 0.3s |
| `Sprinting` | `Sprint.fbx` | Yes | Optionnel |
| `Strafe Left` | `StrafeLeft.fbx` | Yes | Pour blend tree 2D |
| `Strafe Right` | `StrafeRight.fbx` | Yes | Pour blend tree 2D |
| `Walking Backwards` | `WalkBack.fbx` | Yes | Pour blend tree 2D |
| `Jump` | `Jump.fbx` | No | One-shot |
| `Falling` | `Fall.fbx` | Yes | Loop |
| `Death From Front` | `Death.fbx` | No | One-shot |
| `Rifle Aiming Idle` | `AimIdle.fbx` | Yes | Combat stance |
| `Firing Rifle` | `Fire.fbx` | No | One-shot |

Pour chaque animation :
1. Onglet **Animations** → rechercher le nom (ex. "Walking")
2. Sélectionner le personnage Vex (uploadé en étape A)
3. **Régler** : Trim, Overdrive, Character Arm-Space (laisser par défaut)
4. Cocher **In Place** pour les cycles loopables (Walk, Run, Sprint, Idle) — sinon ils translatent
5. Bouton **Download** → cocher **Without Skin** (on a déjà le mesh)
6. Format `FBX Binary`, Pose `T-pose`, FPS `30`
7. Sauvegarder sous `unity-client/Assets/Animations/Locomotion/Walk.fbx`

---

## C. Import settings Unity (CRITIQUE)

Une fois les `.fbx` dans Unity, lancer **`Tools > RocketPi > Configure Operator Imports`**
(à créer en Task #9). Ce script configure :

### Pour chaque `Rigged/{Op}.fbx` :

- **Model** tab :
  - Scale Factor : `1` (Mixamo exporte en cm, Unity attend des m → utiliser `useFileScale`)
  - Mesh Compression : `Off`
  - Read/Write Enabled : `On` (nécessaire pour batching dynamique côté WebGL)
  - Optimize Game Objects : **`Off`** (sinon impossible d'attacher des accessoires aux sockets)

- **Rig** tab :
  - Animation Type : **`Humanoid`**
  - Avatar Definition : `Create From This Model`
  - Skin Weights : `Standard (4 Bones)`

- **Animation** tab :
  - Decocher Import Animation (pas d'anim sur le mesh seul)

### Pour chaque `Animations/Locomotion/{Anim}.fbx` :

- **Rig** tab :
  - Animation Type : **`Humanoid`**
  - Avatar Definition : `Copy From Other Avatar` → pointer vers `Vex.fbx` (le premier)
    (Tous les opérateurs partagent le même rig humanoid Mixamo → 1 avatar source)

- **Animation** tab :
  - Cocher Import Animation
  - Loop Time : Yes pour Idle/Walk/Run/Strafe*/Fall, No pour Jump/Death/Fire
  - Root Transform Rotation / Position Y / Position XZ : **Bake Into Pose** + **Original** (préserve l'animation in-place)

---

## D. AnimatorController

Lancer **`Tools > RocketPi > Build Locomotion Controller`** (Task #9). Ce script crée
`Assets/Animations/OperatorLocomotion.controller` avec :

- **Parameters** :
  - `Speed` (float, 0..1)
  - `IsGrounded` (bool)
  - `IsSprinting` (bool)
  - `Vertical` (float, vélocité Y)
  - `Die` (trigger)
  - `Fire` (trigger)

- **Layer 0 (Base) — Locomotion Blend Tree** sur `Speed` :
  - 0.0 → `Idle`
  - 0.5 → `Walk`
  - 1.0 → `Run` (ou `Sprint` si présent)

- **Layer 1 (Combat) — additive** : `AimIdle` + override `Fire` (trigger)

- **Transitions universelles** :
  - Any State → `Death` (Die trigger), Exit Time No
  - Any State → `Jump` (Vertical > 0.5 & !IsGrounded), Exit Time No

---

## E. Prefab Operator Body

Pour chaque opérateur, créer manuellement (ou via script) un prefab :

```
Assets/Prefabs/Operators/VexBody.prefab
├── (root, scale 1,1,1, position 0,0,0)
│   ├── Animator (controller=OperatorLocomotion, avatar=Vex_avatar)
│   ├── OperatorBody (rocketpi script — drive l'animator)
│   └── Vex_Skin_Mesh (le SkinnedMeshRenderer du fbx)
```

**Assigner** ce prefab dans `OperatorData_Vex.BodyPrefab` (champ ajouté en Task #2 ✅).

---

## F. Checklist par opérateur

| Op | .glb→.fbx | Mixamo Auto-Rig | Import Humanoid | BodyPrefab assigné |
|---|---|---|---|---|
| Vex    | [ ] | [ ] | [ ] | [ ] |
| Halo   | [ ] | [ ] | [ ] | [ ] |
| Drift  | [ ] | [ ] | [ ] | [ ] |
| Crag   | [ ] | [ ] | [ ] | [ ] |
| Brick  | [ ] | [ ] | [ ] | [ ] |
| Iron   | [ ] | [ ] | [ ] | [ ] |
| Wraith | [ ] | [ ] | [ ] | [ ] |
| Echo   | [ ] | [ ] | [ ] | [ ] |

---

## G. Validation

1. Ouvrir `Assets/Scenes/Training.unity`
2. Sélectionner un NPC (`NPC_Vex` par exemple) → Inspector
3. Si `BodyPrefab` est assigné sur `OperatorData_Vex` :
   - Au play, le placeholder capsule disparaît, le mesh riggé apparaît
   - Les jambes bougent quand le NPC patrouille
4. Lancer `Tools > RocketPi > Bake NavMesh (Active Scene)` si on a modifié l'environnement

---

## Risques connus

1. **Meshy meshes peuvent avoir une topologie pas Auto-Rig friendly** (multi-meshes
   séparés, normales inversées). Si Mixamo refuse :
   - Fusionner les meshes dans Blender (`Ctrl+J`)
   - Recalculer les normales (`Mesh > Normals > Recalculate Outside`)
   - Re-exporter
2. **Optimize Game Objects** ne doit JAMAIS être activé : on perd l'accès au transform
   `Spine`, `LeftHand`, etc. nécessaires pour attacher les armes/accessoires.
3. **Scale Factor** : si l'opérateur arrive géant ou minuscule, ajuster le `Scale Factor`
   dans Model tab. Cible : ~1.85m de haut (mesuré pied au sommet du crâne).
4. **Build WebGL** : Mecanim Humanoid + Animator coûte ~150 KB par opérateur. Pour 8
   opérateurs c'est OK (~1.2 MB), mais surveiller si extension du roster.
