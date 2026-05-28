# Power-ups — remplacer les icônes par de vrais modèles 3D

Par défaut, les power-ups affichent des **icônes composées de primitives** (croix,
sphère+mèche, diamant...). Tu peux les remplacer par de **vrais modèles 3D** sans
toucher au code.

## Où trouver des modèles gratuits

⚠️ **Mixamo ne convient PAS** — il ne fait que des personnages humanoïdes animés.
Pour des objets/props, utilise :

| Source | Format | Note |
|---|---|---|
| **https://poly.pizza** (recommandé) | `.glb` | Low-poly CC0, download direct, idéal power-ups |
| **https://kenney.nl** | `.fbx` / `.obj` | Packs cohérents gratuits |
| **https://sketchfab.com** | `.glb` | Filtre "Downloadable" + "Free" |

Cherche : `shield`, `bomb`, `heart` / `medkit`, `lightning` / `bullet`, `star` /
`diamond`, `boot` / `arrow`.

## Convention de nommage (CRITIQUE)

Place les modèles dans **`Assets/Models/PowerUps/`** en les nommant **exactement**
comme le type (sensible à la casse) :

| Power-up | Fichier attendu | Idée de modèle |
|---|---|---|
| Bouclier | `Shield.glb` | bouclier / écu |
| Méga-bombe | `MegaBomb.glb` | bombe ronde |
| Balles rebondissantes | `BouncingBullets.glb` | balle / sphère métal |
| Tir rapide | `RapidFire.glb` | munitions / éclair |
| Quad dégâts | `QuadDamage.glb` | cristal / diamant |
| Vitesse | `SpeedBoost.glb` | botte ailée / flèche |
| Soin | `HealthPack.glb` | croix médicale / cœur |

(extensions acceptées : `.glb`, `.fbx`, `.prefab`)

## Appliquer

1. Dépose les `.glb` dans `Assets/Models/PowerUps/`
2. Focus Unity (laisse importer)
3. `Tools > RocketPi > Add PowerUps to Scene` → chaque pickup récupère
   automatiquement son modèle (sinon garde l'icône primitive)
4. Si un modèle est trop grand/petit : sélectionne le `PowerUp_*` dans la scène →
   composant `PowerUpPickup` → ajuste **Model Scale**

Les modèles tournent + flottent comme les icônes, et conservent le comportement
(ramassage, effet, respawn). Aucun code à modifier.
