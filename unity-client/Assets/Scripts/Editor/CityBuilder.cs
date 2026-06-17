// CityBuilder.cs — Construit une mini-ville industrielle (kits Kenney CC0) autour
// de l'arène centrale. Menu : Tools > RocketPi > Build Mini City.
//
// - Agrandit le sol.
// - Place les bâtiments (CityIndustrial) en grille/anneau autour du fort, rues entre eux.
// - Disperse des props (Factory + SurvivalKit) comme couverts.
// - Matériaux Standard partagés (1 atlas colormap par kit) → compatible Built-in RP
//   (cf. memory : le projet rend en Built-in, pas URP → pas de shader URP sinon magenta).
// - Ajoute des BoxCollider AABB (le joueur/les balles ne traversent pas).
// - Re-bake le NavMesh (les NPCs contournent les bâtiments, circulent dans les rues).

#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using System.Linq;
using Unity.AI.Navigation;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.AI;

namespace Rocketpi.Editor
{
    public static class CityBuilder
    {
        private const string DecorRoot  = "Assets/Models/Decor";
        private const string CityDir    = DecorRoot + "/CityIndustrial/FBX";
        private const string FactoryDir = DecorRoot + "/Factory/FBX";
        private const string SurvivalDir= DecorRoot + "/SurvivalKit/FBX";
        private const string NatureDir  = DecorRoot + "/Nature/FBX";

        // Disposition : anneau de ville autour du fort central (laissé jouable).
        private const float Lot         = 26f;    // pas de la grille (blocs plus grands)
        private const float PlazaRadius = 28f;    // rayon central gardé libre (fort + spawn)
        private const float CityOuter   = 54f;    // rayon externe (gap +20m avec montagnes @74 → pas de chevauchement)
        private const float GroundScale = 15f;    // Plane 10×10 → 150×150
        private const float MaxBuildingHeight = 30f; // évite les cheminées géantes

        // Emprises XZ des bâtiments placés (centre, rayon) — utilisé par PlaceProps pour
        // éviter de poser des props dans les murs.
        private static readonly List<(Vector3 pos, float radius)> _buildingFootprints = new();

        [MenuItem("Tools/RocketPi/Build Mini City")]
        public static void BuildMiniCity()
        {
            if (!Directory.Exists(CityDir))
            {
                EditorUtility.DisplayDialog("Build Mini City",
                    $"Dossier introuvable : {CityDir}\nDépose les kits Kenney (version FBX) dans {DecorRoot}/.", "OK");
                return;
            }

            var cityMat     = EnsureKitMaterial(CityDir,     "CityIndustrial");
            var factoryMat  = EnsureKitMaterial(FactoryDir,  "Factory");
            var survivalMat = EnsureKitMaterial(SurvivalDir, "SurvivalKit");

            // Agrandit le sol existant.
            var ground = GameObject.Find("Ground");
            if (ground != null)
            {
                ground.transform.localScale = new Vector3(GroundScale, 1f, GroundScale);
                Debug.Log($"[RocketPi] Sol agrandi → {GroundScale * 10f}×{GroundScale * 10f}.");
            }

            // Root idempotent.
            var old = GameObject.Find("MiniCity");
            if (old != null) Object.DestroyImmediate(old);
            var root = new GameObject("MiniCity");
            var colRoot = new GameObject("CityColliders");          // AABB sans rotation
            colRoot.transform.SetParent(root.transform, false);

            // Reset des emprises de bâtiments → les props éviteront ces zones.
            _buildingFootprints.Clear();

            var buildings = Directory.GetFiles(CityDir, "*.fbx").Select(Fix).ToList();
            var factory   = Directory.Exists(FactoryDir)  ? Directory.GetFiles(FactoryDir,  "*.fbx").Select(Fix).ToList() : new List<string>();
            var survival  = Directory.Exists(SurvivalDir) ? Directory.GetFiles(SurvivalDir, "*.fbx").Select(Fix).ToList() : new List<string>();

            int b = PlaceBuildings(root.transform, colRoot.transform, buildings, cityMat);
            int p = PlaceProps(root.transform, colRoot.transform, factory, survival, factoryMat, survivalMat);

            RebakeNavMesh();
            EditorSceneManager.MarkSceneDirty(EditorSceneManager.GetActiveScene());

            Debug.Log($"[RocketPi] Mini-ville construite : {b} bâtiments + {p} props. NavMesh re-baké.");
            EditorUtility.DisplayDialog("Build Mini City",
                $"✓ {b} bâtiments + {p} props placés\n✓ Sol agrandi + NavMesh re-baké\n\n" +
                "Lance le Play pour explorer la ville.", "OK");
        }

        // ── Placement bâtiments (grille en anneau) ─────────────────────────
        private static int PlaceBuildings(Transform root, Transform colRoot, List<string> models, Material mat)
        {
            if (models.Count == 0) return 0;
            int n = Mathf.CeilToInt(CityOuter / Lot);
            int placed = 0;

            for (var gx = -n; gx <= n; gx++)
            for (var gz = -n; gz <= n; gz++)
            {
                var pos = new Vector3(gx * Lot, 0f, gz * Lot);
                var dist = new Vector2(pos.x, pos.z).magnitude;
                if (dist < PlazaRadius || dist > CityOuter) continue; // centre libre + bord
                if (Random.value < 0.18f) continue;                  // trous = rues/variété

                var path = models[Random.Range(0, models.Count)];
                var inst = InstantiateDecor(path, root, mat);
                if (inst == null) continue;

                inst.transform.rotation = Quaternion.Euler(0f, Random.Range(0, 4) * 90f, 0f);
                ScaleToFootprint(inst, Lot * Random.Range(0.65f, 0.78f)); // un peu plus petit → rues plus larges
                CapHeight(inst, MaxBuildingHeight);                        // cheminées pas géantes
                var jitter = new Vector3(Random.Range(-1.2f, 1.2f), 0f, Random.Range(-1.2f, 1.2f)); // jitter réduit
                GroundAt(inst, pos + jitter);
                AddBoxCollider(inst, colRoot);

                // Enregistre l'emprise XZ pour que les props l'évitent.
                var b = WorldBounds(inst);
                var fpRadius = Mathf.Max(b.size.x, b.size.z) * 0.5f + 1.5f;   // +1.5 m marge
                _buildingFootprints.Add((new Vector3(b.center.x, 0f, b.center.z), fpRadius));
                placed++;
            }
            return placed;
        }

        // ── Dispersion props (couverts) ────────────────────────────────────
        private static int PlaceProps(Transform root, Transform colRoot,
            List<string> factory, List<string> survival, Material fMat, Material sMat)
        {
            int target = 90;
            int placed = 0;
            for (var i = 0; i < target; i++)
            {
                bool useFactory = factory.Count > 0 && (survival.Count == 0 || Random.value < 0.5f);
                var list = useFactory ? factory : survival;
                if (list.Count == 0) continue;

                // Cherche une position libre (loin des bâtiments) en quelques essais.
                Vector3 pos = Vector3.zero;
                bool freeSpot = false;
                for (var t = 0; t < 20; t++)
                {
                    var a = Random.value * Mathf.PI * 2f;
                    var r = Random.Range(PlazaRadius - 6f, CityOuter - 4f);
                    var candidate = new Vector3(Mathf.Cos(a) * r, 0f, Mathf.Sin(a) * r);
                    if (!OverlapsBuilding(candidate, 1.2f)) { pos = candidate; freeSpot = true; break; }
                }
                if (!freeSpot) continue;   // pas de place libre, skip ce prop

                var path = list[Random.Range(0, list.Count)];
                var inst = InstantiateDecor(path, root, useFactory ? fMat : sMat);
                if (inst == null) continue;

                inst.transform.rotation = Quaternion.Euler(0f, Random.Range(0f, 360f), 0f);
                ScaleToHeight(inst, Random.Range(1.4f, 2.4f));   // couvert humain (plus de minuscules)
                GroundAt(inst, pos);
                AddBoxCollider(inst, colRoot);
                placed++;
            }
            return placed;
        }

        /// <summary>True si la position XZ est dans l'emprise d'un bâtiment placé.
        /// Public pour que d'autres scripts (AddPowerUps) évitent ces zones.</summary>
        public static bool OverlapsBuilding(Vector3 worldPos, float propRadius)
        {
            var p = new Vector3(worldPos.x, 0f, worldPos.z);
            foreach (var (bp, br) in _buildingFootprints)
                if (Vector3.Distance(p, bp) < br + propRadius) return true;
            return false;
        }

        // ── Helpers ────────────────────────────────────────────────────────
        private static GameObject InstantiateDecor(string path, Transform parent, Material mat)
        {
            var prefab = AssetDatabase.LoadAssetAtPath<GameObject>(path);
            if (prefab == null) return null;
            var inst = (GameObject)PrefabUtility.InstantiatePrefab(prefab, parent);
            if (inst == null) return null;
            if (mat != null)
                foreach (var r in inst.GetComponentsInChildren<Renderer>(true))
                {
                    var arr = new Material[r.sharedMaterials.Length];
                    for (var i = 0; i < arr.Length; i++) arr[i] = mat;
                    r.sharedMaterials = arr;
                }
            return inst;
        }

        private static Bounds WorldBounds(GameObject go)
        {
            var rends = go.GetComponentsInChildren<Renderer>(true);
            if (rends.Length == 0) return new Bounds(go.transform.position, Vector3.one);
            var b = rends[0].bounds;
            for (var i = 1; i < rends.Length; i++) b.Encapsulate(rends[i].bounds);
            return b;
        }

        private static void ScaleToFootprint(GameObject go, float targetFootprint)
        {
            var b = WorldBounds(go);
            var foot = Mathf.Max(b.size.x, b.size.z);
            if (foot > 0.001f) go.transform.localScale *= targetFootprint / foot;
        }

        private static void ScaleToHeight(GameObject go, float targetHeight)
        {
            var b = WorldBounds(go);
            if (b.size.y > 0.001f) go.transform.localScale *= targetHeight / b.size.y;
        }

        /// <summary>Réduit l'objet si sa hauteur dépasse maxH (évite cheminées géantes).</summary>
        private static void CapHeight(GameObject go, float maxH)
        {
            var b = WorldBounds(go);
            if (b.size.y > maxH) go.transform.localScale *= maxH / b.size.y;
        }

        /// <summary>Pose l'objet à (x,z) en collant sa base au sol (y=0).</summary>
        private static void GroundAt(GameObject go, Vector3 worldXZ)
        {
            go.transform.position = new Vector3(worldXZ.x, 0f, worldXZ.z);
            var b = WorldBounds(go);
            go.transform.position += Vector3.up * (go.transform.position.y - b.min.y);
        }

        /// <summary>BoxCollider AABB posé sous colRoot (sans rotation) → bloque joueur/balles.</summary>
        // MeshCollider précis sur chaque maillage de l'objet : épouse la forme VISIBLE
        // (plus de "mur invisible" comme avec une boîte AABB surdimensionnée sur un objet
        // tourné). colRoot n'est plus utilisé (gardé pour compat de signature).
        private static void AddBoxCollider(GameObject go, Transform colRoot)
        {
            foreach (var mf in go.GetComponentsInChildren<MeshFilter>(true))
            {
                if (mf.sharedMesh == null) continue;
                if (mf.GetComponent<MeshCollider>() != null) continue;
                var mc = mf.gameObject.AddComponent<MeshCollider>();
                mc.sharedMesh = mf.sharedMesh;   // convex=false → collision statique exacte
            }
        }

        private static Material EnsureKitMaterial(string kitFbxDir, string kitName)
        {
            var colormap = $"{kitFbxDir}/Textures/colormap.png";
            var tex = AssetDatabase.LoadAssetAtPath<Texture2D>(colormap);
            var matPath = $"{DecorRoot}/{kitName}_Mat.mat";
            var mat = AssetDatabase.LoadAssetAtPath<Material>(matPath);
            if (mat == null)
            {
                mat = new Material(Shader.Find("Standard"));
                AssetDatabase.CreateAsset(mat, matPath);
            }
            else mat.shader = Shader.Find("Standard");
            if (tex != null) mat.mainTexture = tex;     // _MainTex (Standard built-in)
            mat.SetFloat("_Glossiness", 0.1f);
            mat.SetFloat("_Metallic", 0f);
            EditorUtility.SetDirty(mat);
            return mat;
        }

        private static void RebakeNavMesh()
        {
            var surface = Object.FindAnyObjectByType<NavMeshSurface>();
            if (surface == null)
            {
                surface = new GameObject("NavMeshSurface").AddComponent<NavMeshSurface>();
            }
            surface.collectObjects = CollectObjects.All;
            surface.useGeometry    = NavMeshCollectGeometry.RenderMeshes;
            surface.BuildNavMesh();
        }

        // ═══════════════════════════════════════════════════════════════════
        //  BORDURE DE MAP : montagnes (Nature kit) + sol dégradé + limite invisible
        // ═══════════════════════════════════════════════════════════════════

        private const float BoundaryHalf = 70f;   // mur invisible (le joueur s'arrête ici)
        private const float MountainHalf = 74f;   // 1ère rangée de montagnes (au-delà)

        [MenuItem("Tools/RocketPi/Build Map Border (Mountains)")]
        public static void BuildMapBorder()
        {
            if (!Directory.Exists(NatureDir))
            {
                EditorUtility.DisplayDialog("Build Map Border",
                    $"Dossier introuvable : {NatureDir}\nDépose le kit Nature (FBX) dans {DecorRoot}/Nature/.", "OK");
                return;
            }

            // 0. Force l'import du kit Nature (sinon LoadAssetAtPath renvoie null si les
            //    FBX viennent d'être déposés et pas encore importés par Unity).
            AssetDatabase.ImportAsset(NatureDir, ImportAssetOptions.ImportRecursive);
            AssetDatabase.Refresh();

            // 1. Désactive l'ancienne enceinte (remplacée par les montagnes).
            var castle = GameObject.Find("CastleWalls");
            if (castle != null) castle.SetActive(false);

            // 2. Sol dégradé béton → naturel.
            ApplyGroundBlend();

            // 3. Root idempotent.
            var old = GameObject.Find("MapBorder");
            if (old != null) Object.DestroyImmediate(old);
            var root = new GameObject("MapBorder");
            var colRoot = new GameObject("BorderColliders");
            colRoot.transform.SetParent(root.transform, false);

            var rockMat = EnsureRockMaterial();
            var rocks = Directory.GetFiles(NatureDir, "*.fbx").Select(Fix)
                .Where(p => {
                    var n = Path.GetFileNameWithoutExtension(p).ToLowerInvariant();
                    return n.StartsWith("rock_large") || n.StartsWith("rock_tall")
                        || n == "cliff_large_rock" || n == "cliff_block_rock" || n == "cliff_top_rock";
                }).ToList();
            if (rocks.Count == 0) // fallback : tous les rock_*
                rocks = Directory.GetFiles(NatureDir, "rock*.fbx").Select(Fix).ToList();

            int placed = PlaceMountainRing(root.transform, rocks, rockMat);
            BuildInvisibleBoundary(colRoot.transform);
            RebakeNavMesh();
            EditorSceneManager.MarkSceneDirty(EditorSceneManager.GetActiveScene());

            Debug.Log($"[RocketPi] Bordure montagnes : {placed} rochers + limite invisible. Enceinte castle désactivée.");
            EditorUtility.DisplayDialog("Build Map Border",
                $"✓ {placed} montagnes en anneau\n✓ Sol béton → naturel\n✓ Limite invisible (±{BoundaryHalf})\n✓ NavMesh re-baké", "OK");
        }

        private static int PlaceMountainRing(Transform root, List<string> rocks, Material mat)
        {
            if (rocks.Count == 0) return 0;
            const float step = 8f;
            var rows = new[] { 0f, 8f, 16f };   // 3 rangées vers l'extérieur → masse de montagne
            int placed = 0;

            for (var u = -MountainHalf; u <= MountainHalf; u += step)
            for (var ri = 0; ri < rows.Length; ri++)
            {
                var d = MountainHalf + rows[ri];
                // Les 4 côtés.
                var spots = new[]
                {
                    new Vector3(u, 0f, d), new Vector3(u, 0f, -d),
                    new Vector3(d, 0f, u), new Vector3(-d, 0f, u),
                };
                foreach (var spot in spots)
                {
                    var inst = InstantiateDecor(rocks[Random.Range(0, rocks.Count)], root, mat);
                    if (inst == null) continue;
                    inst.transform.rotation = Quaternion.Euler(0f, Random.Range(0f, 360f), 0f);
                    ScaleToHeight(inst, Random.Range(12f, 24f));      // montagnes hautes (cachent le bord)
                    var jit = new Vector3(Random.Range(-3f, 3f), 0f, Random.Range(-3f, 3f));
                    GroundAt(inst, spot + jit);
                    placed++;
                }
            }
            return placed;
        }

        /// <summary>4 BoxCollider hauts formant le carré-limite (le joueur ne sort pas,
        /// même s'il y a des trous entre les rochers). Invisibles (pas de renderer).</summary>
        private static void BuildInvisibleBoundary(Transform colRoot)
        {
            const float h = 40f, thick = 2f, len = BoundaryHalf * 2f + thick;
            (Vector3 pos, Vector3 size)[] walls =
            {
                (new Vector3(0f, h * 0.5f,  BoundaryHalf), new Vector3(len, h, thick)),
                (new Vector3(0f, h * 0.5f, -BoundaryHalf), new Vector3(len, h, thick)),
                (new Vector3( BoundaryHalf, h * 0.5f, 0f), new Vector3(thick, h, len)),
                (new Vector3(-BoundaryHalf, h * 0.5f, 0f), new Vector3(thick, h, len)),
            };
            foreach (var (pos, size) in walls)
            {
                var go = new GameObject("BoundaryWall");
                go.transform.SetParent(colRoot, false);
                go.transform.position = pos;
                go.AddComponent<BoxCollider>().size = size;
            }
        }

        private static void ApplyGroundBlend()
        {
            var ground = GameObject.Find("Ground");
            if (ground == null) return;
            var sh = Shader.Find("RocketPi/GroundBlend");
            if (sh == null) { Debug.LogWarning("[RocketPi] Shader RocketPi/GroundBlend introuvable."); return; }
            var matPath = $"{DecorRoot}/Ground_Blend.mat";
            var mat = AssetDatabase.LoadAssetAtPath<Material>(matPath);
            if (mat == null) { mat = new Material(sh); AssetDatabase.CreateAsset(mat, matPath); }
            else mat.shader = sh;
            mat.SetFloat("_InnerRadius", 56f);
            mat.SetFloat("_OuterRadius", 72f);
            EditorUtility.SetDirty(mat);
            var r = ground.GetComponent<Renderer>();
            if (r != null) r.sharedMaterial = mat;
        }

        private static Material EnsureRockMaterial()
        {
            var matPath = $"{DecorRoot}/Rock_Mat.mat";
            var mat = AssetDatabase.LoadAssetAtPath<Material>(matPath);
            if (mat == null) { mat = new Material(Shader.Find("Standard")); AssetDatabase.CreateAsset(mat, matPath); }
            else mat.shader = Shader.Find("Standard");
            mat.color = new Color(0.5f, 0.48f, 0.44f);   // roche gris-brun
            mat.SetFloat("_Glossiness", 0.05f);
            mat.SetFloat("_Metallic", 0f);
            EditorUtility.SetDirty(mat);
            return mat;
        }

        // ═══════════════════════════════════════════════════════════════════
        //  FORT CENTRAL : QG industriel avec toit praticable + rampe d'accès
        // ═══════════════════════════════════════════════════════════════════

        [MenuItem("Tools/RocketPi/Build Central Fort")]
        public static void BuildCentralFort()
        {
            // Remplace l'ancien donjon (cube primitif).
            var donjon = GameObject.Find("Donjon");
            if (donjon != null) donjon.SetActive(false);

            var old = GameObject.Find("CentralFort");
            if (old != null) Object.DestroyImmediate(old);
            var root = new GameObject("CentralFort");
            root.transform.position = Vector3.zero;

            var mat = EnsureConcreteMaterial();

            const float S = 22f;   // côté
            const float H = 6f;    // hauteur des murs
            const float T = 1f;    // épaisseur
            const float door = 5f; // largeur de la porte (mur sud)
            float half = S * 0.5f;

            // Murs N / E / O
            Part(root, mat, new Vector3(0f, H * 0.5f,  half), new Vector3(S, H, T));
            Part(root, mat, new Vector3( half, H * 0.5f, 0f), new Vector3(T, H, S));
            Part(root, mat, new Vector3(-half, H * 0.5f, 0f), new Vector3(T, H, S));
            // Mur sud en 2 segments → porte au milieu
            float seg = (S - door) * 0.5f;
            Part(root, mat, new Vector3(-(door * 0.5f + seg * 0.5f), H * 0.5f, -half), new Vector3(seg, H, T));
            Part(root, mat, new Vector3( (door * 0.5f + seg * 0.5f), H * 0.5f, -half), new Vector3(seg, H, T));

            // Toit praticable (dalle pleine avec collider).
            Part(root, mat, new Vector3(0f, H + 0.3f, 0f), new Vector3(S, 0.6f, S));

            // Créneaux sur le pourtour du toit (couverts).
            AddCrenellations(root, mat, half, H + 0.6f);

            // Rampe d'accès au toit (côté est, vers l'extérieur). Pente ~37° < 45° → grimpable.
            Ramp(root, mat,
                bottom: new Vector3(half + 8f, 0.05f, 0f),
                top:    new Vector3(half - 1f, H + 0.6f, 0f),
                width:  4f);

            RebakeNavMesh();
            EditorSceneManager.MarkSceneDirty(EditorSceneManager.GetActiveScene());

            Debug.Log("[RocketPi] Fort central construit (toit praticable + rampe). Ancien donjon désactivé.");
            EditorUtility.DisplayDialog("Build Central Fort",
                "✓ QG industriel : murs + porte + toit accessible\n✓ Rampe d'accès au toit\n✓ Créneaux (couverts)\n✓ NavMesh re-baké", "OK");
        }

        private static void Part(GameObject root, Material mat, Vector3 localPos, Vector3 size)
        {
            var go = GameObject.CreatePrimitive(PrimitiveType.Cube);   // BoxCollider inclus
            go.name = "Part";
            go.transform.SetParent(root.transform, false);
            go.transform.localPosition = localPos;
            go.transform.localScale = size;
            var r = go.GetComponent<Renderer>();
            if (r != null) r.sharedMaterial = mat;
        }

        private static void Ramp(GameObject root, Material mat, Vector3 bottom, Vector3 top, float width)
        {
            var go = GameObject.CreatePrimitive(PrimitiveType.Cube);
            go.name = "Ramp";
            go.transform.SetParent(root.transform, false);
            var dir = top - bottom;
            go.transform.localPosition = (bottom + top) * 0.5f;
            go.transform.localRotation = Quaternion.LookRotation(dir.normalized, Vector3.up);
            go.transform.localScale = new Vector3(width, 0.5f, dir.magnitude);
            var r = go.GetComponent<Renderer>();
            if (r != null) r.sharedMaterial = mat;
        }

        private static void AddCrenellations(GameObject root, Material mat, float half, float topY)
        {
            const float step = 3f, m = 0.9f, mh = 1.2f;
            for (var u = -half; u <= half; u += step)
            {
                Part(root, mat, new Vector3(u, topY + mh * 0.5f,  half), new Vector3(m, mh, m));
                Part(root, mat, new Vector3(u, topY + mh * 0.5f, -half), new Vector3(m, mh, m));
                Part(root, mat, new Vector3( half, topY + mh * 0.5f, u), new Vector3(m, mh, m));
                Part(root, mat, new Vector3(-half, topY + mh * 0.5f, u), new Vector3(m, mh, m));
            }
        }

        private static Material EnsureConcreteMaterial()
        {
            var matPath = $"{DecorRoot}/Fort_Concrete.mat";
            var mat = AssetDatabase.LoadAssetAtPath<Material>(matPath);
            if (mat == null) { mat = new Material(Shader.Find("Standard")); AssetDatabase.CreateAsset(mat, matPath); }
            else mat.shader = Shader.Find("Standard");
            mat.color = new Color(0.42f, 0.43f, 0.46f);
            mat.SetFloat("_Glossiness", 0.1f);
            mat.SetFloat("_Metallic", 0.1f);
            EditorUtility.SetDirty(mat);
            return mat;
        }

        private static string Fix(string p) => p.Replace('\\', '/');
    }
}
#endif
