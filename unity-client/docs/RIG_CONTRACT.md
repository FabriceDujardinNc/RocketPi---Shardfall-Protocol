# RIG_CONTRACT.md — Contrat rig & sockets opérateurs

Document de référence figeant la convention partagée entre la génération 3D
(Meshy.ai → retargeting Mixamo → import Unity) et l'assemblage runtime côté
Unity (`OperatorAssembler`).

Toute modification de ce contrat impose une régénération coordonnée des assets
ET une mise à jour synchronisée d'`OperatorAssembler` / `AttachmentPointManager`.

---

## 1. Squelette

- **Type** : Humanoid Mecanim (Unity standard).
- **T-pose** au repos (bras horizontaux, paumes face au sol, jambes droites).
- **Échelle** : 1 unité Unity = 1 mètre. Hauteur cible opérateur ≈ **1.85 m**.
  Tolérance ±5 % — au-delà, post-process de rescale au build.
- **Origine** (0,0,0) : entre les deux pieds, **Y up**, **Z forward** (regard du
  personnage vers +Z).
- **Polycount cible** : 20-40k triangles, mesh décimé via Draco au build.

---

## 2. Sockets (attachment points)

Tous les opérateurs DOIVENT exposer ces transforms enfants, nommés exactement
ainsi (le naming est sensible à la casse). Ils sont attachés aux bones Mecanim
humanoid via le composant `AttachmentPointManager`.

| Socket name | Bone parent (Mecanim) | Offset local approx. | Usage |
|---|---|---|---|
| `Hand_R` | `RightHand` | (0, 0, 0.05) | Arme principale |
| `Hand_L` | `LeftHand` | (0, 0, 0.05) | Arme secondaire / pistolet |
| `Head_Top` | `Head` | (0, 0.18, 0) | Casque, chapeau, lunettes |
| `Face_Front` | `Head` | (0, 0.04, 0.12) | Masque, visière, accessoires faciaux |
| `Back_Center` | `Spine` | (0, 0.05, -0.12) | Sac à dos, jetpack, holster long |
| `Hip_R` | `RightUpperLeg` | (0.08, 0.05, 0) | Holster pistolet, étui couteau |
| `Hip_L` | `LeftUpperLeg` | (-0.08, 0.05, 0) | Pochette munitions, grenade |

Offsets indicatifs — l'artiste / le rigger peut affiner par opérateur ;
seul le **naming** est strict.

---

## 3. Format de livraison

### Mesh de base (par opérateur)

- Fichier : `operators/{operator_slug}/base.glb`
- Format : **glTF 2.0 binary** (`.glb`)
- Contient : mesh + rig humanoid + sockets ci-dessus en empty transforms
- Compression mesh : **Draco** (build-time, pas dans le .glb source)
- Textures : embedded **KTX2 / Basis Universal**, max 2048×2048 pour le diffuse
- Animations : aucune dans le `.glb` opérateur (les anims sont sur des clips
  Mecanim partagés, stockés côté projet Unity, pas par opérateur)

### Skin (texture variant)

- Fichier : `operators/{operator_slug}/skins/{skin_slug}/texture.ktx2`
- Applique : remap du material principal de la base via `MaterialPropertyBlock`
  (slot `_BaseMap`). Si le skin remplace plusieurs slots, un manifest JSON
  l'accompagne :
  ```
  operators/{operator_slug}/skins/{skin_slug}/manifest.json
  {
    "_BaseMap": "texture.ktx2",
    "_MetallicGlossMap": "metallic.ktx2",
    "_BumpMap": "normal.ktx2"
  }
  ```

### Arme

- Fichier : `weapons/{weapon_slug}/base.glb`
- T-pose / orientation neutre, origine sur le **grip** de l'arme.
- L'arme s'attache à `Hand_R` (par défaut) — son origine local devient
  l'identité du socket.
- Pas de rig, pas d'anims dans le `.glb` (anims de tir / rechargement sont
  Mecanim côté projet).

### Accessoire

- Fichier : `accessories/{accessory_slug}/base.glb`
- Origine local sur le **point d'attache anticipé** (centre haut du crâne pour
  un casque, centre dos pour un sac, etc.).
- Le `socket_name` en BDD détermine où il s'attache. Slots autorisés :
  `head`, `face`, `back`, `hands`, `legs`.

---

## 4. Versioning

Chaque opérateur a une colonne `base_rig_version` (string, ex. `humanoid-v1`).
Un changement de squelette / convention de sockets ⇒ incrément en `v2`. Le
`OperatorAssembler` refuse de charger un mesh dont la version diffère de la
version supportée par le client → page de mise à jour client.

## 5. Pipeline cible (rappel)

```
Meshy.ai (mesh + texture)
   ↓
Mixamo Auto-Rigger (humanoid)
   ↓
Validation Unity (T-pose, scale, sockets présents)
   ↓
Storage Laravel (storage/app/public/models/...)
   ↓
Runtime Unity : GLTFast.GltfImport → OperatorAssembler.AttachAll(...)
```

Étape **validation Unity** : script éditeur `Tools > RocketPi > Validate Rig`
qui vérifie pour un GLB donné :
- présence de tous les sockets nommés
- hauteur dans tolérance ±5 %
- T-pose (heuristique : main_R.Y > hand_R.Y - 0.05)
- humanoid Mecanim configuré

À écrire en Phase 6.

---

## 6. Placeholder de test

Tant qu'aucun asset Meshy n'est généré, le projet utilise un placeholder :

- Modèle : **Unity Asset Store > Mixamo Standard Character** (gratuit) exporté
  en `.glb` via Blender, sockets ajoutés manuellement.
- Path placeholder : `storage/app/public/models/_placeholder/base.glb`
- Tout opérateur dont `base_model_url IS NULL` retombe sur ce placeholder côté
  Unity (`OperatorLoader` détecte et substitue).

Le fichier physique sera déposé manuellement par un développeur Unity — il
n'est pas généré par le code Laravel.
