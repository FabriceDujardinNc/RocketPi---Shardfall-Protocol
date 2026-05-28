// WeaponBuilder.cs — Construit un prefab d'arme par FBX du dossier Weapons/
// (HitscanWeapon + Muzzle + visuel) et les assigne PAR CLASSE aux opérateurs.
//
// Menu : Tools > RocketPi > Build Weapon Prefabs
//
// Source : Quaternius "Scifi Gun Pack" via Poly Pizza (FBX low-poly, couleurs
// embarquées dans les matériaux du FBX — pas de texture séparée).

#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using System.Linq;
using Rocketpi.Gameplay.Operators;
using Rocketpi.Gameplay.Weapons;
using UnityEditor;
using UnityEngine;

namespace Rocketpi.Editor
{
    public static class WeaponBuilder
    {
        private const string WeaponsDir = "Assets/Models/Weapons";
        private const string PrefabsDir = "Assets/Prefabs/Weapons";

        [MenuItem("Tools/RocketPi/Build Weapon Prefabs")]
        public static void BuildWeaponPrefabs()
        {
            if (!Directory.Exists(WeaponsDir))
            {
                EditorUtility.DisplayDialog("Build Weapon Prefabs",
                    $"Dossier introuvable : {WeaponsDir}", "OK");
                return;
            }
            EnsureFolder(PrefabsDir);

            // Force l'import des FBX nouvellement déposés.
            AssetDatabase.ImportAsset(WeaponsDir, ImportAssetOptions.ImportRecursive);
            AssetDatabase.Refresh();

            var fbxFiles = Directory.GetFiles(WeaponsDir, "*.fbx", SearchOption.AllDirectories)
                .Select(p => p.Replace('\\', '/')).ToList();

            var byKey = new Dictionary<string, GameObject>();   // clé normalisée → prefab arme

            foreach (var path in fbxFiles)
            {
                var fbx = AssetDatabase.LoadAssetAtPath<GameObject>(path);
                if (fbx == null) continue;

                var key  = Normalize(Path.GetFileNameWithoutExtension(path));     // ex: "sniper_rifle"
                var name = $"Weapon_{ToPrefabName(Path.GetFileNameWithoutExtension(path))}";
                var prefabPath = $"{PrefabsDir}/{name}.prefab";

                // Racine de l'arme
                var weapon = new GameObject(name);
                var hw = weapon.AddComponent<HitscanWeapon>();

                // Visuel : FBX instancié, colliders retirés, scale normalisé.
                var visual = (GameObject)PrefabUtility.InstantiatePrefab(fbx, weapon.transform);
                visual.name = "Visual";
                visual.transform.localPosition = Vector3.zero;
                visual.transform.localRotation = Quaternion.identity;
                foreach (var col in visual.GetComponentsInChildren<Collider>(true))
                    Object.DestroyImmediate(col);

                bool isPistol = key.Contains("pistol");
                float targetLength = isPistol ? 0.45f : 0.95f;
                var b = WorldBounds(visual);
                float longest = Mathf.Max(b.size.x, b.size.y, b.size.z);
                if (longest > 0.001f) visual.transform.localScale = Vector3.one * (targetLength / longest);

                // Décale le visuel pour que la CROSSE (arrière, axe le plus long) soit au
                // niveau du socket (épaule) → plus de clipping de l'arrière du fusil dans
                // l'épaule. La crosse à 0, le canon vers l'avant.
                ShiftBackToZero(visual);

                // Muzzle : calé au VRAI bout du canon (calculé depuis les bounds après scaling
                // ET après le décalage de la crosse).
                var muzzle = new GameObject("Muzzle");
                muzzle.transform.SetParent(weapon.transform, false);
                muzzle.transform.localPosition = ComputeFrontPoint(visual);

                var so = new SerializedObject(hw);
                so.FindProperty("_muzzle").objectReferenceValue = muzzle.transform;
                ApplyStatsForKey(so, key);
                so.ApplyModifiedPropertiesWithoutUndo();

                var prefab = PrefabUtility.SaveAsPrefabAsset(weapon, prefabPath);
                Object.DestroyImmediate(weapon);
                byKey[key] = prefab;
                Debug.Log($"[RocketPi] Weapon prefab créé : {prefabPath}  (key={key})");
            }

            // Affectation par classe d'opérateur (Role).
            var roleToKey = new Dictionary<OperatorRole, string>
            {
                { OperatorRole.Sniper,      "sniper_rifle" },
                { OperatorRole.Healer,      "pistol" },
                { OperatorRole.Scout,       "longpistol_small" },
                { OperatorRole.Tank,        "rifle" },
                { OperatorRole.Explosives,  "lightning_gun" },
                { OperatorRole.Assault,     "rifle" },
                { OperatorRole.Infiltrator, "longpistol" },
                { OperatorRole.Hacker,      "ray_gun" },
            };

            int assigned = 0;
            foreach (var g in AssetDatabase.FindAssets("t:OperatorData"))
            {
                var p = AssetDatabase.GUIDToAssetPath(g);
                var op = AssetDatabase.LoadAssetAtPath<OperatorData>(p);
                if (op == null) continue;
                if (!roleToKey.TryGetValue(op.Role, out var key)) continue;

                GameObject chosen = null;
                if (!byKey.TryGetValue(key, out chosen))
                {
                    // Fallback : sous-chaîne (sniper_rifle → "sniper", etc.).
                    chosen = byKey.FirstOrDefault(kv => kv.Key.Contains(key)).Value;
                }
                if (chosen == null) continue;

                var so = new SerializedObject(op);
                var prop = so.FindProperty("WeaponPrefab");
                if (prop == null) continue;
                prop.objectReferenceValue = chosen;
                so.ApplyModifiedPropertiesWithoutUndo();
                EditorUtility.SetDirty(op);
                assigned++;
            }
            AssetDatabase.SaveAssets();

            EditorUtility.DisplayDialog("Build Weapon Prefabs",
                $"✓ {byKey.Count} prefabs créés dans {PrefabsDir}/\n" +
                $"✓ Assignés à {assigned} opérateurs (par classe)", "OK");
        }

        // ── Mix Ishikawa pour les classes "standard" (Pistol/Rifle) ────────
        // Halo/Wraith reçoivent des Pistols Ishikawa (snow/black) et Crag/Iron des
        // Rifles Ishikawa (tan/black). Les autres gardent leurs armes exotiques
        // Quaternius (sniper, long pistol, lightning gun, ray gun).
        [MenuItem("Tools/RocketPi/Apply Ishikawa Guns Mix")]
        public static void ApplyIshikawaGunsMix()
        {
            const string IshikawaDir = "Assets/Ishikawa1116/LOW-POLY GUNS PACK_SAMPLE/Prefabs";
            (string srcPrefab, string opName, string outName, float length)[] mix =
            {
                ($"{IshikawaDir}/Snow/Pistol/Pistol_00.prefab",                "Halo",   "Weapon_Ishikawa_Pistol_Snow",  0.45f),
                ($"{IshikawaDir}/Black and common color/Pistol/Pistol_00.prefab","Wraith", "Weapon_Ishikawa_Pistol_Black", 0.45f),
                ($"{IshikawaDir}/Tan/Rifle/Rifle_00.prefab",                  "Crag",   "Weapon_Ishikawa_Rifle_Tan",    0.95f),
                ($"{IshikawaDir}/Black and common color/Rifle/Rifle_00.prefab", "Iron",   "Weapon_Ishikawa_Rifle_Black",  0.95f),
            };

            EnsureFolder(PrefabsDir);
            int created = 0, assigned = 0;

            foreach (var (srcPath, opName, outName, length) in mix)
            {
                var src = AssetDatabase.LoadAssetAtPath<GameObject>(srcPath);
                if (src == null) { Debug.LogWarning($"[RocketPi] Visuel introuvable : {srcPath}"); continue; }

                var outPath = $"{PrefabsDir}/{outName}.prefab";
                var weapon = new GameObject(outName);
                var hw = weapon.AddComponent<HitscanWeapon>();

                var visual = (GameObject)PrefabUtility.InstantiatePrefab(src, weapon.transform);
                visual.name = "Visual";
                visual.transform.localPosition = Vector3.zero;
                visual.transform.localRotation = Quaternion.identity;
                foreach (var col in visual.GetComponentsInChildren<Collider>(true))
                    Object.DestroyImmediate(col);

                var b = WorldBounds(visual);
                float longest = Mathf.Max(b.size.x, b.size.y, b.size.z);
                if (longest > 0.001f) visual.transform.localScale = Vector3.one * (length / longest);

                ShiftBackToZero(visual);   // crosse au socket, canon vers l'avant

                var muzzle = new GameObject("Muzzle");
                muzzle.transform.SetParent(weapon.transform, false);
                muzzle.transform.localPosition = ComputeFrontPoint(visual);

                var so = new SerializedObject(hw);
                so.FindProperty("_muzzle").objectReferenceValue = muzzle.transform;
                // Stats : pistolet plus lent, rifle cadence normale.
                ApplyStatsForKey(so, outName.ToLowerInvariant().Contains("pistol") ? "pistol" : "rifle");
                so.ApplyModifiedPropertiesWithoutUndo();

                var prefab = PrefabUtility.SaveAsPrefabAsset(weapon, outPath);
                Object.DestroyImmediate(weapon);
                created++;

                // Assignation à l'opérateur (par DisplayName).
                foreach (var g in AssetDatabase.FindAssets("t:OperatorData"))
                {
                    var p = AssetDatabase.GUIDToAssetPath(g);
                    var op = AssetDatabase.LoadAssetAtPath<OperatorData>(p);
                    if (op == null || op.DisplayName != opName) continue;
                    var oso = new SerializedObject(op);
                    var prop = oso.FindProperty("WeaponPrefab");
                    if (prop == null) break;
                    prop.objectReferenceValue = prefab;
                    oso.ApplyModifiedPropertiesWithoutUndo();
                    EditorUtility.SetDirty(op);
                    assigned++;
                    break;
                }
            }
            AssetDatabase.SaveAssets();
            EditorUtility.DisplayDialog("Apply Ishikawa Guns Mix",
                $"✓ {created} prefabs Ishikawa créés + assignés à {assigned} opérateurs (Halo, Wraith, Crag, Iron).\n" +
                "Vex/Drift/Brick/Echo gardent leurs armes exotiques Quaternius.", "OK");
        }

        // ── Helpers ────────────────────────────────────────────────────────
        private static string Normalize(string s)
        {
            var b = new System.Text.StringBuilder();
            foreach (var c in s.ToLowerInvariant())
            {
                if (char.IsLetterOrDigit(c)) b.Append(c);
                else if (c == ' ' || c == '-' || c == '_') b.Append('_');
                // parenthèses et autres : ignorés
            }
            // collapse __
            var raw = b.ToString();
            while (raw.Contains("__")) raw = raw.Replace("__", "_");
            return raw.Trim('_');
        }

        private static string ToPrefabName(string s)
        {
            var b = new System.Text.StringBuilder();
            foreach (var c in s)
            {
                if (char.IsLetterOrDigit(c)) b.Append(c);
                else if (c == ' ' || c == '-' || c == '_') b.Append('_');
            }
            var r = b.ToString();
            while (r.Contains("__")) r = r.Replace("__", "_");
            return r.Trim('_');
        }

        // Stats par catégorie d'arme : pistolet lent, rifle cadence normale, sniper très lent
        // mais gros dégâts, ray gun lent type énergie, etc.
        //   (fireRate, baseDamage, magazineSize, reloadTime, maxRange)
        private static readonly Dictionary<string, (float rate, int dmg, int mag, float reload, float range)> Stats = new()
        {
            { "pistol",            (4.0f,  22,  12, 1.4f, 50f) },  // Ishikawa Pistol + Quaternius Pistol
            { "longpistol_small",  (6.0f,  18,  20, 1.6f, 55f) },  // SMG/burst pistol
            { "longpistol",        (5.0f,  24,  15, 1.6f, 60f) },
            { "rifle",             (8.0f,  18,  30, 2.0f, 80f) },  // automatique - défaut
            { "sniper_rifle",      (1.4f,  85,   5, 2.6f, 150f) }, // très lent, gros dégâts
            { "lightning_gun",     (6.0f,  22,  25, 1.8f, 60f) },  // énergie fast-medium
            { "ray_gun",           (3.0f,  35,  15, 2.0f, 70f) },  // énergie lente, fort
        };

        private static void ApplyStatsForKey(SerializedObject so, string key)
        {
            // Match exact d'abord, puis sous-chaîne (sniper_rifle contient "sniper_rifle").
            if (!Stats.TryGetValue(key, out var s))
            {
                var match = Stats.Keys.FirstOrDefault(k => key.Contains(k));
                if (match == null) return;
                s = Stats[match];
            }
            Set(so, "_fireRate",    s.rate);
            Set(so, "_baseDamage",  s.dmg);
            Set(so, "_magazineSize", s.mag);
            Set(so, "_reloadTime",  s.reload);
            Set(so, "_maxRange",    s.range);
        }

        private static void Set(SerializedObject so, string name, float v)
        { var p = so.FindProperty(name); if (p != null) p.floatValue = v; }
        private static void Set(SerializedObject so, string name, int v)
        { var p = so.FindProperty(name); if (p != null) p.intValue = v; }

        /// <summary>Décalage PARTIEL (50%) de l'arme vers l'avant : la crosse reste un peu
        /// derrière le socket (épaule) sans plus clipper trop loin dans le model, et
        /// l'arme ne dépasse pas exagérément à l'avant non plus.</summary>
        private static void ShiftBackToZero(GameObject visual)
        {
            var b = WorldBounds(visual);
            int axis = 0; float m = b.size.x;
            if (b.size.y > m) { axis = 1; m = b.size.y; }
            if (b.size.z > m) { axis = 2; m = b.size.z; }
            var off = Vector3.zero;
            off[axis] = -b.min[axis] * 0.5f;   // demi-shift : équilibre clipping/avancée
            visual.transform.position += off;
        }

        /// <summary>Point monde au "front" du visuel le long de son axe le plus long
        /// (= probablement le canon pour une arme allongée).</summary>
        private static Vector3 ComputeFrontPoint(GameObject visual)
        {
            var b = WorldBounds(visual);
            int axis = 0; float m = b.size.x;
            if (b.size.y > m) { axis = 1; m = b.size.y; }
            if (b.size.z > m) { axis = 2; m = b.size.z; }
            var p = b.center;
            p[axis] = b.max[axis];
            return p;
        }

        private static Bounds WorldBounds(GameObject go)
        {
            var rs = go.GetComponentsInChildren<Renderer>(true);
            if (rs.Length == 0) return new Bounds(go.transform.position, Vector3.one);
            var b = rs[0].bounds;
            for (var i = 1; i < rs.Length; i++) b.Encapsulate(rs[i].bounds);
            return b;
        }

        private static void EnsureFolder(string path)
        {
            if (AssetDatabase.IsValidFolder(path)) return;
            var parts = path.Split('/');
            var cur = parts[0];
            for (var i = 1; i < parts.Length; i++)
            {
                var nxt = $"{cur}/{parts[i]}";
                if (!AssetDatabase.IsValidFolder(nxt)) AssetDatabase.CreateFolder(cur, parts[i]);
                cur = nxt;
            }
        }
    }
}
#endif
