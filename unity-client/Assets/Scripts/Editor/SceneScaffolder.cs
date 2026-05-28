// SceneScaffolder.cs — Crée / re-crée les scènes Bootstrap & Training avec :
//   - terrain + obstacles (pour rendre la map intéressante pour le pathfinding)
//   - NavMeshSurface bakable
//   - 6 waypoints positionnés en hexagone autour du joueur
//   - 4 NPC opérateurs spawn sur les waypoints (placeholder capsule jusqu'à
//     ce que les BodyPrefab Mixamo soient assignés sur OperatorData)
//   - joueur 3rd-person : capsule logique + caméra orbitale
//
// Menus :
//   - Tools > RocketPi > Scaffold Scenes              (idempotent)
//   - Tools > RocketPi > Rebuild Training Scene       (DESTRUCTIF, recrée)
//   - Tools > RocketPi > Bake NavMesh (Active Scene)
//   - Tools > RocketPi > Add NPCs to Current Scene    (sans tout recréer)

#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using Rocketpi.Bridge;
using Rocketpi.Gameplay;
using Rocketpi.Gameplay.Abilities;
using Rocketpi.Gameplay.Body;
using Rocketpi.Gameplay.CameraControl;
using Rocketpi.Gameplay.Match;
using Rocketpi.Gameplay.NPC;
using Rocketpi.Gameplay.Operators;
using Rocketpi.Gameplay.PowerUps;
using Rocketpi.Gameplay.UI;
using Rocketpi.UI;
using Unity.AI.Navigation;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.AI;
using UnityEngine.SceneManagement;

namespace Rocketpi.Editor
{
    public static class SceneScaffolder
    {
        private const string ScenesDir     = "Assets/Scenes";
        private const string BootstrapPath = "Assets/Scenes/Bootstrap.unity";
        private const string GameplayPath  = "Assets/Scenes/Training.unity";

        // ── Menus ──────────────────────────────────────────────────────────

        [MenuItem("Tools/RocketPi/Scaffold Scenes")]
        public static void ScaffoldScenes()
        {
            EnsureFolder(ScenesDir);
            CreateBootstrap();
            if (!File.Exists(GameplayPath)) BuildTrainingScene(save: true);
            else Debug.Log("[RocketPi] Training.unity exists, skipping. Use 'Rebuild Training Scene' to force.");

            UpdateBuildSettings();
            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();
        }

        [MenuItem("Tools/RocketPi/Rebuild Training Scene")]
        public static void RebuildTrainingScene()
        {
            if (!EditorUtility.DisplayDialog(
                    "Rebuild Training Scene",
                    "Cette action va ÉCRASER la scène Training.unity actuelle. Tout ce qui est dedans sera perdu. Continuer ?",
                    "Oui, écraser", "Annuler"))
                return;

            BuildTrainingScene(save: true);
            UpdateBuildSettings();
            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();
        }

        [MenuItem("Tools/RocketPi/Bake NavMesh (Active Scene)")]
        public static void BakeActiveNavMesh()
        {
            var scene = EditorSceneManager.GetActiveScene();
            var surface = Object.FindAnyObjectByType<NavMeshSurface>();
            if (surface == null)
            {
                // Idempotent : crée le NavMeshSurface s'il manque (utile quand on
                // ajoute les NPCs à une scène custom CastleWalls/Donjon).
                var go = new GameObject("NavMeshSurface");
                surface = go.AddComponent<NavMeshSurface>();
                Debug.Log("[RocketPi] NavMeshSurface absent — créé automatiquement.");
            }
            // RenderMeshes (pas PhysicsColliders) : plus robuste, fonctionne même si
            // le sol du décor n'a pas de collider — seul le MeshRenderer suffit.
            surface.collectObjects = CollectObjects.All;
            surface.layerMask = ~0;
            surface.useGeometry = NavMeshCollectGeometry.RenderMeshes;
            surface.BuildNavMesh();
            var tri = NavMesh.CalculateTriangulation();
            Debug.Log($"[RocketPi] NavMesh baké : {tri.indices.Length / 3} triangles.");
            EditorSceneManager.MarkSceneDirty(scene);
            Debug.Log("[RocketPi] NavMesh bake terminé.");
        }

        [MenuItem("Tools/RocketPi/Add NPCs to Current Scene")]
        public static void AddNpcsToCurrentScene()
        {
            var scene = EditorSceneManager.GetActiveScene();
            var npcRoot = GameObject.Find("NPCs") ?? new GameObject("NPCs");
            var waypoints = FindOrCreateWaypoints(npcRoot.transform.parent);
            CreateNpcs(npcRoot.transform, waypoints);
            EditorSceneManager.MarkSceneDirty(scene);
        }

        // ── Rôles + capacités ──────────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Setup Roles + Abilities")]
        public static void SetupRolesAndAbilities()
        {
            // Mapping codename → rôle (aligné sur OperatorSeeder Laravel).
            var roleByCode = new Dictionary<string, OperatorRole>
            {
                { "VX-01", OperatorRole.Sniper },
                { "HL-02", OperatorRole.Healer },
                { "DR-03", OperatorRole.Scout },
                { "CR-04", OperatorRole.Tank },
                { "BK-05", OperatorRole.Explosives },
                { "IR-06", OperatorRole.Assault },
                { "WR-07", OperatorRole.Infiltrator },
                { "EC-08", OperatorRole.Hacker },
            };

            var assigned = 0;
            foreach (var g in AssetDatabase.FindAssets("t:OperatorData"))
            {
                var op = AssetDatabase.LoadAssetAtPath<OperatorData>(AssetDatabase.GUIDToAssetPath(g));
                if (op == null || !roleByCode.TryGetValue(op.Codename, out var role)) continue;
                var so = new SerializedObject(op);
                so.FindProperty("Role").enumValueIndex = (int)role;
                so.ApplyModifiedPropertiesWithoutUndo();
                EditorUtility.SetDirty(op);
                assigned++;
            }
            AssetDatabase.SaveAssets();

            var player = GameObject.Find("Player");
            if (player != null)
            {
                var abilities = player.GetComponent<PlayerAbilities>() ?? player.AddComponent<PlayerAbilities>();

                // Réutilise le modèle de bouclier des power-ups pour le mur du Tank/Crag.
                var shield = AssetDatabase.LoadAssetAtPath<GameObject>("Assets/Models/PowerUps/Shield.glb");
                if (shield != null)
                {
                    var aso = new SerializedObject(abilities);
                    aso.FindProperty("_shieldModel").objectReferenceValue = shield;
                    aso.ApplyModifiedPropertiesWithoutUndo();
                    EditorUtility.SetDirty(abilities);
                }
                Debug.Log("[RocketPi] PlayerAbilities prêt (modèle bouclier assigné).");
            }

            // Overlay d'aide des touches (touche I).
            if (GameObject.Find("ControlsOverlay") == null)
            {
                new GameObject("ControlsOverlay").AddComponent<ControlsOverlay>();
                Debug.Log("[RocketPi] ControlsOverlay ajouté (touche I).");
            }

            // HUD jauges de cooldown (F / R) en bas de l'écran.
            if (GameObject.Find("AbilityHud") == null)
            {
                new GameObject("AbilityHud").AddComponent<AbilityHud>();
                Debug.Log("[RocketPi] AbilityHud ajouté (jauges F / R).");
            }

            EditorSceneManager.MarkSceneDirty(EditorSceneManager.GetActiveScene());
            Debug.Log($"[RocketPi] {assigned} rôles assignés aux opérateurs + capacités prêtes.");
        }

        // ── Sélection d'opérateur ──────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Add Operator Selection")]
        public static void AddOperatorSelection()
        {
            var scene = EditorSceneManager.GetActiveScene();
            var existing = GameObject.Find("OperatorSelection");
            if (existing != null) Object.DestroyImmediate(existing);

            var go = new GameObject("OperatorSelection");
            var menu = go.AddComponent<OperatorSelectionMenu>();

            // Tous les OperatorData du projet (ordre stable par codename).
            var guids = AssetDatabase.FindAssets("t:OperatorData");
            var ops = new List<OperatorData>();
            foreach (var g in guids)
            {
                var op = AssetDatabase.LoadAssetAtPath<OperatorData>(AssetDatabase.GUIDToAssetPath(g));
                if (op != null) ops.Add(op);
            }
            ops.Sort((a, b) => string.CompareOrdinal(a.Codename, b.Codename));

            var player = Object.FindAnyObjectByType<PlayerController>();
            var cam    = Object.FindAnyObjectByType<ThirdPersonCamera>();

            var so = new SerializedObject(menu);
            var arr = so.FindProperty("_operators");
            arr.arraySize = ops.Count;
            for (var i = 0; i < ops.Count; i++)
                arr.GetArrayElementAtIndex(i).objectReferenceValue = ops[i];
            so.FindProperty("_player").objectReferenceValue = player;
            so.FindProperty("_camera").objectReferenceValue = cam;
            so.ApplyModifiedPropertiesWithoutUndo();
            EditorUtility.SetDirty(menu);
            EditorSceneManager.MarkSceneDirty(scene);
            Debug.Log($"[RocketPi] Écran de sélection ajouté ({ops.Count} opérateurs).");
        }

        // ── Power-Ups ──────────────────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Add PowerUps to Scene")]
        public static void AddPowerUpsToScene()
        {
            var scene = EditorSceneManager.GetActiveScene();

            // 1. Le joueur doit avoir le gestionnaire d'effets.
            var player = GameObject.Find("Player");
            if (player != null && player.GetComponent<PlayerPowerUps>() == null)
            {
                player.AddComponent<PlayerPowerUps>();
                Debug.Log("[RocketPi] PlayerPowerUps ajouté au Player.");
            }

            // 2. (Re)crée le conteneur de pickups + plateformes.
            var existing = GameObject.Find("PowerUps");
            if (existing != null) Object.DestroyImmediate(existing);
            var root = new GameObject("PowerUps");
            var platforms = new GameObject("Platforms");
            platforms.transform.SetParent(root.transform);

            var types = (PowerUpType[])System.Enum.GetValues(typeof(PowerUpType));
            var rockMat = AssetDatabase.LoadAssetAtPath<Material>("Assets/Models/Decor/Rock_Mat.mat");
            var rocks = LoadPlatformRocks();

            // Beaucoup de bonus EN HAUTEUR, éparpillés partout : chacun sur un rocher-
            // plateforme (à atteindre en sautant). Répartis sur tout le terrain jouable.
            const int count = 30;
            int placed = 0;
            for (var i = 0; i < count; i++)
            {
                // Anneau 22..62 : laisse le fort central + sa porte/rampe totalement dégagés
                // (sinon un rocher peut bloquer la sortie).
                if (!TryRandomNavPointRing(22f, 62f, out var pos)) continue;
                var topY = SpawnPlatformRock(platforms.transform, rocks, rockMat, pos, Random.Range(1.0f, 1.8f));
                CreatePickup(root.transform, types[i % types.Length], new Vector3(pos.x, topY + 0.7f, pos.z));
                placed++;
            }

            // Bonus "forts" sur le TOIT du fort (accès par la rampe).
            CreatePickup(root.transform, PowerUpType.MegaBomb,   new Vector3( 0f, 7.2f,  0f));
            CreatePickup(root.transform, PowerUpType.QuadDamage, new Vector3( 7f, 7.2f,  7f));
            CreatePickup(root.transform, PowerUpType.Shield,     new Vector3(-7f, 7.2f, -7f));

            EditorSceneManager.MarkSceneDirty(scene);
            Debug.Log($"[RocketPi] {placed} bonus sur plateformes + 3 sur le fort.");
        }

        // Rochers low-poly utilisables comme plateformes (petits/moyens → sautables).
        private static List<GameObject> LoadPlatformRocks()
        {
            var list = new List<GameObject>();
            const string dir = "Assets/Models/Decor/Nature/FBX";
            if (!System.IO.Directory.Exists(dir)) return list;
            foreach (var raw in System.IO.Directory.GetFiles(dir, "rock_*.fbx"))
            {
                var n = System.IO.Path.GetFileNameWithoutExtension(raw).ToLowerInvariant();
                if (n.Contains("small") || n.Contains("medium") || n.Contains("tall"))
                {
                    var go = AssetDatabase.LoadAssetAtPath<GameObject>(raw.Replace('\\', '/'));
                    if (go != null) list.Add(go);
                }
            }
            return list;
        }

        /// <summary>Pose un rocher-plateforme au sol (base à pos.y), le scale à la hauteur
        /// cible, lui ajoute un BoxCollider AABB, et renvoie l'altitude de son sommet.</summary>
        private static float SpawnPlatformRock(Transform parent, List<GameObject> rocks, Material mat,
            Vector3 groundPos, float targetHeight)
        {
            GameObject inst;
            if (rocks != null && rocks.Count > 0)
            {
                inst = (GameObject)PrefabUtility.InstantiatePrefab(rocks[Random.Range(0, rocks.Count)], parent);
                inst.transform.position = groundPos;
                inst.transform.rotation = Quaternion.Euler(0f, Random.Range(0f, 360f), 0f);
                var b0 = RendBounds(inst);
                if (b0.size.y > 0.001f) inst.transform.localScale *= targetHeight / b0.size.y;
                if (mat != null)
                    foreach (var r in inst.GetComponentsInChildren<Renderer>(true)) r.sharedMaterial = mat;
            }
            else
            {
                inst = GameObject.CreatePrimitive(PrimitiveType.Cube);   // fallback socle
                inst.transform.SetParent(parent);
                inst.transform.position = groundPos;
                inst.transform.localScale = new Vector3(2.4f, targetHeight, 2.4f);
                if (mat != null) inst.GetComponent<Renderer>().sharedMaterial = mat;
            }

            // Cale la base au sol.
            var b = RendBounds(inst);
            inst.transform.position += Vector3.up * (groundPos.y - b.min.y);

            // MeshCollider précis (épouse la forme du rocher → pas de mur invisible autour ;
            // le joueur peut s'approcher et tenir sur le vrai sommet).
            foreach (var mf in inst.GetComponentsInChildren<MeshFilter>(true))
            {
                if (mf.sharedMesh == null || mf.GetComponent<MeshCollider>() != null) continue;
                mf.gameObject.AddComponent<MeshCollider>().sharedMesh = mf.sharedMesh;
            }
            return RendBounds(inst).max.y;
        }

        private static Bounds RendBounds(GameObject go)
        {
            var rs = go.GetComponentsInChildren<Renderer>(true);
            if (rs.Length == 0) return new Bounds(go.transform.position, Vector3.one);
            var b = rs[0].bounds;
            for (var i = 1; i < rs.Length; i++) b.Encapsulate(rs[i].bounds);
            return b;
        }

        private static void CreatePickup(Transform parent, PowerUpType type, Vector3 pos)
        {
            var go = new GameObject($"PowerUp_{type}");
            go.transform.SetParent(parent);
            go.transform.position = pos;
            var pu = go.AddComponent<PowerUpPickup>();
            var so = new SerializedObject(pu);
            so.FindProperty("_type").enumValueIndex = (int)type;

            // Cherche un vrai modèle 3D nommé d'après le type dans Assets/Models/PowerUps/.
            // Ex : Assets/Models/PowerUps/Shield.glb (ou .fbx / .prefab).
            var model = FindPowerUpModel(type);
            if (model != null)
                so.FindProperty("_model").objectReferenceValue = model;

            so.ApplyModifiedPropertiesWithoutUndo();
            EditorUtility.SetDirty(pu);
        }

        private static GameObject FindPowerUpModel(PowerUpType type)
        {
            const string dir = "Assets/Models/PowerUps";
            foreach (var ext in new[] { "prefab", "glb", "fbx" })
            {
                var path = $"{dir}/{type}.{ext}";
                if (File.Exists(path))
                {
                    var go = AssetDatabase.LoadAssetAtPath<GameObject>(path);
                    if (go != null) return go;
                }
            }
            return null;
        }

        // ── Diagnostic NavMesh ─────────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Diagnose NavMesh")]
        public static void DiagnoseNavMesh()
        {
            // 1. NavMeshSurface présent ?
            var surface = Object.FindAnyObjectByType<NavMeshSurface>();
            Debug.Log($"[Diag] NavMeshSurface présent : {surface != null}");
            if (surface != null)
            {
                Debug.Log($"[Diag]   collectObjects={surface.collectObjects}, useGeometry={surface.useGeometry}, " +
                          $"size={surface.size}, navMeshData={(surface.navMeshData != null ? "OUI" : "NULL (jamais baké!)")}");
            }

            // 2. Triangulation globale du NavMesh : combien de triangles ?
            var tri = NavMesh.CalculateTriangulation();
            Debug.Log($"[Diag] NavMesh triangulation : {tri.vertices.Length} vertices, {tri.indices.Length / 3} triangles");
            if (tri.indices.Length == 0)
                Debug.LogError("[Diag] ⚠️ NAVMESH VIDE — le bake n'a rien généré. Le sol n'est pas collecté " +
                               "(pas de collider si useGeometry=PhysicsColliders, ou hors du volume size).");

            // 3. Pour chaque NPC, SamplePosition trouve-t-il du sol navigable ?
            var npcsRoot = GameObject.Find("NPCs");
            if (npcsRoot != null)
            {
                foreach (Transform npc in npcsRoot.transform)
                {
                    var found = NavMesh.SamplePosition(npc.position, out var hit, 10f, NavMesh.AllAreas);
                    if (found)
                        Debug.Log($"[Diag] {npc.name} @ {npc.position} → navmesh trouvé à {hit.position} " +
                                  $"(distance {Vector3.Distance(npc.position, hit.position):F2}m)");
                    else
                        Debug.LogError($"[Diag] {npc.name} @ {npc.position} → AUCUN navmesh dans un rayon de 10m !");
                }
            }
            else Debug.LogWarning("[Diag] Pas de GameObject 'NPCs' dans la scène.");

            // 4. Le sol : liste les gros meshes/colliders au niveau y≈0
            Debug.Log("[Diag] Recherche du sol (objets nommés Ground/Sol/Plane/Floor) :");
            foreach (var go in EditorSceneManager.GetActiveScene().GetRootGameObjects())
            {
                ScanForGround(go.transform, 0);
            }
        }

        private static void ScanForGround(Transform t, int depth)
        {
            var n = t.name.ToLowerInvariant();
            if (n.Contains("ground") || n.Contains("sol") || n.Contains("plane") || n.Contains("floor") || n.Contains("terrain"))
            {
                var hasCol = t.GetComponent<Collider>() != null;
                var hasRenderer = t.GetComponent<Renderer>() != null;
                Debug.Log($"[Diag]   '{t.name}' pos={t.position} scale={t.lossyScale} collider={hasCol} renderer={hasRenderer}");
            }
            if (depth < 3)
                foreach (Transform c in t) ScanForGround(c, depth + 1);
        }

        // ── Play mode helpers ──────────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Enter Play Mode")]
        public static void EnterPlayMode()
        {
            if (!EditorApplication.isPlaying) EditorApplication.isPlaying = true;
        }

        [MenuItem("Tools/RocketPi/Exit Play Mode")]
        public static void ExitPlayMode()
        {
            if (EditorApplication.isPlaying) EditorApplication.isPlaying = false;
        }

        [MenuItem("Tools/RocketPi/Capture Game View to PNG")]
        public static void CaptureGameViewToPng()
        {
            // En play mode, on capture la GameView (vue du joueur via Main Camera)
            var cam = Camera.main;
            if (cam == null)
            {
                Debug.LogError("[RocketPi] Camera.main introuvable. Vérifie qu'une caméra a le tag MainCamera.");
                return;
            }

            const int w = 1280;
            const int h = 720;
            var rt = new RenderTexture(w, h, 24);
            var oldRt = cam.targetTexture;
            cam.targetTexture = rt;
            cam.Render();
            RenderTexture.active = rt;
            var tex = new Texture2D(w, h, TextureFormat.RGB24, false);
            tex.ReadPixels(new Rect(0, 0, w, h), 0, 0);
            tex.Apply();
            RenderTexture.active = null;
            cam.targetTexture = oldRt;
            Object.DestroyImmediate(rt);

            var bytes = tex.EncodeToPNG();
            Object.DestroyImmediate(tex);
            var dir = "Library/SceneCaptures";
            if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);
            var path = Path.Combine(dir, "game_capture.png");
            File.WriteAllBytes(path, bytes);
            Debug.Log($"[RocketPi] Game view captured: {Path.GetFullPath(path)}");
        }

        // ── Capture Scene to PNG ───────────────────────────────────────────
        [MenuItem("Tools/RocketPi/Capture Scene to PNG")]
        public static void CaptureSceneToPng()
        {
            var sceneView = SceneView.lastActiveSceneView;
            if (sceneView == null || sceneView.camera == null)
            {
                Debug.LogError("[RocketPi] Pas de SceneView actif. Ouvre l'onglet Scene avant de lancer.");
                return;
            }

            // Vue large pour englober la map agrandie (≈150) + la ceinture de montagnes.
            sceneView.LookAtDirect(new Vector3(0f, 0f, 0f), Quaternion.Euler(55f, 35f, 0f), 130f);
            sceneView.Repaint();
            sceneView.camera.transform.position = new Vector3(105f, 130f, -105f);
            sceneView.camera.transform.LookAt(Vector3.zero);

            const int w = 1280;
            const int h = 720;
            var rt = new RenderTexture(w, h, 24);
            var cam = sceneView.camera;
            var oldRt = cam.targetTexture;
            cam.targetTexture = rt;
            cam.Render();

            RenderTexture.active = rt;
            var tex = new Texture2D(w, h, TextureFormat.RGB24, false);
            tex.ReadPixels(new Rect(0, 0, w, h), 0, 0);
            tex.Apply();
            RenderTexture.active = null;
            cam.targetTexture = oldRt;
            Object.DestroyImmediate(rt);

            var bytes = tex.EncodeToPNG();
            Object.DestroyImmediate(tex);

            var dir = "Library/SceneCaptures";
            if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);
            var path = Path.Combine(dir, "scene_capture.png");
            File.WriteAllBytes(path, bytes);
            Debug.Log($"[RocketPi] Scene captured: {Path.GetFullPath(path)}");
        }

        // ── Convert Player to 3rd Person ───────────────────────────────────
        [MenuItem("Tools/RocketPi/Convert Player to 3rd Person")]
        public static void ConvertPlayerTo3rdPerson()
        {
            var scene = EditorSceneManager.GetActiveScene();
            var player = GameObject.Find("Player");
            if (player == null)
            {
                Debug.LogError("[RocketPi] Aucun GameObject 'Player' dans la scène active.");
                return;
            }

            // 1. Supprime doublons HealthSystem (garde le premier)
            var healths = player.GetComponents<HealthSystem>();
            for (var i = 1; i < healths.Length; i++) Object.DestroyImmediate(healths[i]);

            // 2. Trouve / crée la caméra 3rd person — détachée du Player
            //    Si la scène a une "Player/Camera" (ancien layout FPS), on la détache et on la
            //    reconvertit. Sinon, on en crée une nouvelle au niveau racine.
            Camera cam = null;
            var existingCamTransform = player.transform.Find("Camera");
            if (existingCamTransform != null)
            {
                existingCamTransform.SetParent(null, worldPositionStays: true);
                existingCamTransform.gameObject.name = "Main Camera";
                cam = existingCamTransform.GetComponent<Camera>();
            }
            else
            {
                cam = Object.FindAnyObjectByType<Camera>();
            }
            if (cam == null)
            {
                var camGo = new GameObject("Main Camera");
                cam = camGo.AddComponent<Camera>();
                camGo.AddComponent<AudioListener>();
            }
            cam.tag = "MainCamera";
            cam.fieldOfView = 65f;

            // 3. Position initiale derrière le player (la ThirdPersonCamera ajustera au runtime)
            cam.transform.position = player.transform.position + new Vector3(0f, 2.5f, -4f);
            cam.transform.LookAt(player.transform.position + Vector3.up * 1.5f);

            // 4. Ajoute ou récupère ThirdPersonCamera et le pointe vers le Player
            var tpCam = cam.GetComponent<ThirdPersonCamera>() ?? cam.gameObject.AddComponent<ThirdPersonCamera>();
            tpCam.SetTarget(player.transform, shoulderHeight: 1.6f);

            // 5. Configure le PlayerController : _camera, _match, _bodyAnchor, _weaponSocket
            var playerController = player.GetComponent<PlayerController>();
            if (playerController == null)
            {
                Debug.LogWarning("[RocketPi] PlayerController manquant — l'ajout est sauté.");
            }
            else
            {
                var match = Object.FindAnyObjectByType<TrainingMatchManager>();
                var so = new SerializedObject(playerController);
                so.FindProperty("_camera").objectReferenceValue = tpCam;
                if (match != null) so.FindProperty("_match").objectReferenceValue = match;
                so.FindProperty("_bodyAnchor").objectReferenceValue = player.transform;

                // WeaponSocket — peut avoir été reparenté au Player après le détachement de la
                // Camera. On le retrouve par nom dans les enfants directs du Player ou de la Camera.
                Transform weaponSocket = player.transform.Find("WeaponSocket");
                if (weaponSocket == null && cam != null) weaponSocket = cam.transform.Find("WeaponSocket");
                if (weaponSocket == null)
                {
                    var wsGo = new GameObject("WeaponSocket");
                    wsGo.transform.SetParent(player.transform, false);
                    wsGo.transform.localPosition = new Vector3(0.3f, 1.5f, 0.4f);
                    weaponSocket = wsGo.transform;
                }
                so.FindProperty("_weaponSocket").objectReferenceValue = weaponSocket;
                so.ApplyModifiedPropertiesWithoutUndo();
            }

            EditorUtility.SetDirty(player);
            EditorSceneManager.MarkSceneDirty(scene);
            Debug.Log("[RocketPi] Player converti en 3rd person setup. Camera détachée + ThirdPersonCamera ajouté + refs branchées.");
        }

        // ── Bootstrap ──────────────────────────────────────────────────────

        private static void CreateBootstrap()
        {
            if (File.Exists(BootstrapPath))
            {
                Debug.Log("[RocketPi] Bootstrap.unity exists, skipping.");
                return;
            }
            var scene = EditorSceneManager.NewScene(NewSceneSetup.EmptyScene, NewSceneMode.Single);
            scene.name = "Bootstrap";
            var bridgeGo = new GameObject("RocketpiBridge");
            bridgeGo.AddComponent<RocketpiBridge>();
            EditorSceneManager.SaveScene(scene, BootstrapPath);
        }

        // ── Training : build complet ───────────────────────────────────────

        private static void BuildTrainingScene(bool save)
        {
            var scene = EditorSceneManager.NewScene(NewSceneSetup.EmptyScene, NewSceneMode.Single);
            scene.name = "Training";

            // ── Lighting ───────────────────────────────────────────────────
            var lightGo = new GameObject("Directional Light");
            var light = lightGo.AddComponent<Light>();
            light.type = LightType.Directional;
            light.intensity = 1.1f;
            light.shadows = LightShadows.Soft;
            lightGo.transform.rotation = Quaternion.Euler(50f, 30f, 0f);

            // ── Environment root ───────────────────────────────────────────
            var envRoot = new GameObject("Environment");

            // Ground 80×80 (assez grand pour patrouilles)
            var ground = GameObject.CreatePrimitive(PrimitiveType.Plane);
            ground.name = "Ground";
            ground.transform.SetParent(envRoot.transform);
            ground.transform.localScale = new Vector3(8f, 1f, 8f); // Plane = 10×10 par défaut

            // Cover/obstacles pour rendre le pathfinding intéressant
            CreateCover(envRoot.transform, new Vector3( 10f, 0.5f,   0f), new Vector3(3f, 1f, 3f));
            CreateCover(envRoot.transform, new Vector3(-10f, 0.5f,   0f), new Vector3(3f, 1f, 3f));
            CreateCover(envRoot.transform, new Vector3(  0f, 0.5f,  10f), new Vector3(3f, 1f, 3f));
            CreateCover(envRoot.transform, new Vector3(  0f, 0.5f, -10f), new Vector3(3f, 1f, 3f));
            CreateCover(envRoot.transform, new Vector3(  6f, 1.0f,   6f), new Vector3(2f, 2f, 2f));
            CreateCover(envRoot.transform, new Vector3( -6f, 1.0f,  -6f), new Vector3(2f, 2f, 2f));

            // ── NavMesh surface ────────────────────────────────────────────
            var navMeshGo = new GameObject("NavMeshSurface");
            navMeshGo.transform.SetParent(envRoot.transform);
            var surface = navMeshGo.AddComponent<NavMeshSurface>();
            surface.collectObjects = CollectObjects.All;
            surface.layerMask = ~0;
            surface.useGeometry = NavMeshCollectGeometry.PhysicsColliders;
            surface.BuildNavMesh();

            // ── Player capsule + 3rd person ────────────────────────────────
            var playerGo = new GameObject("Player");
            var cc = playerGo.AddComponent<CharacterController>();
            cc.height = 1.85f;
            cc.radius = 0.4f;
            cc.center = new Vector3(0f, 0.925f, 0f);
            playerGo.AddComponent<PlayerController>();
            playerGo.AddComponent<HealthSystem>();
            playerGo.tag = "Player";
            var playerLayer = LayerMask.NameToLayer("Player");
            if (playerLayer >= 0) playerGo.layer = playerLayer;
            playerGo.transform.position = new Vector3(0f, 0.1f, 0f);

            // Camera 3rd person détachée (suit le joueur via ThirdPersonCamera)
            var camGo = new GameObject("Main Camera");
            camGo.tag = "MainCamera";
            camGo.transform.position = new Vector3(0f, 2.5f, -4f);
            var cam = camGo.AddComponent<Camera>();
            cam.fieldOfView = 65f;
            camGo.AddComponent<AudioListener>();
            var tpCam = camGo.AddComponent<ThirdPersonCamera>();
            tpCam.SetTarget(playerGo.transform, shoulderHeight: 1.6f);

            // ── Waypoints ──────────────────────────────────────────────────
            var waypointsRoot = new GameObject("Waypoints");
            var waypoints = CreateHexagonWaypoints(waypointsRoot.transform, radius: 15f);

            // ── NPCs ───────────────────────────────────────────────────────
            var npcRoot = new GameObject("NPCs");
            CreateNpcs(npcRoot.transform, waypoints);

            // ── Match Manager + UI ─────────────────────────────────────────
            var matchGo = new GameObject("TrainingMatchManager");
            matchGo.AddComponent<TrainingMatchManager>();

            var canvasGo = new GameObject("Canvas");
            var canvas = canvasGo.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvasGo.AddComponent<UnityEngine.UI.CanvasScaler>();
            canvasGo.AddComponent<UnityEngine.UI.GraphicRaycaster>();
            canvasGo.AddComponent<HudController>();
            canvasGo.AddComponent<MainMenuController>();
            canvasGo.AddComponent<MatchSummaryController>();

            var esGo = new GameObject("EventSystem");
            esGo.AddComponent<UnityEngine.EventSystems.EventSystem>();
            esGo.AddComponent<UnityEngine.InputSystem.UI.InputSystemUIInputModule>();

            if (save)
            {
                EditorSceneManager.SaveScene(scene, GameplayPath);
                Debug.Log("[RocketPi] Training.unity rebuilt with NavMesh + 6 waypoints + 4 NPCs + 3rd person.");
            }
        }

        // ── Helpers : geometry ─────────────────────────────────────────────

        private static GameObject CreateCover(Transform parent, Vector3 pos, Vector3 scale)
        {
            var cube = GameObject.CreatePrimitive(PrimitiveType.Cube);
            cube.name = "Cover";
            cube.transform.SetParent(parent);
            cube.transform.position = pos;
            cube.transform.localScale = scale;
            return cube;
        }

        // ── Helpers : waypoints ────────────────────────────────────────────

        private static List<Transform> CreateHexagonWaypoints(Transform parent, float radius)
        {
            var list = new List<Transform>();
            for (var i = 0; i < 6; i++)
            {
                var angle = i * Mathf.PI * 2f / 6f;
                var pos = new Vector3(Mathf.Cos(angle) * radius, 0.5f, Mathf.Sin(angle) * radius);
                var wp = new GameObject($"WP_{i:00}");
                wp.transform.SetParent(parent);
                wp.transform.position = pos;
                list.Add(wp.transform);
            }
            return list;
        }

        private static List<Transform> FindOrCreateWaypoints(Transform parent)
        {
            var existing = GameObject.Find("Waypoints");
            if (existing != null)
            {
                var list = new List<Transform>();
                foreach (Transform t in existing.transform) list.Add(t);
                if (list.Count > 0) return list;
            }
            var root = existing != null ? existing.transform : new GameObject("Waypoints").transform;
            return CreateHexagonWaypoints(root, 15f);
        }

        // ── Helpers : NPCs ─────────────────────────────────────────────────

        private static void CreateNpcs(Transform parent, List<Transform> waypoints)
        {
            // 4 opérateurs choisis pour variété : 2 ORBIT, 1 FERRO, 1 VEIL
            // (les codename viennent de OperatorSeeder Laravel — RosterScaffolder Unity les match)
            var picks = new[] { "Vex", "Halo", "Iron", "Wraith" };
            var operatorsByName = LoadOperatorsByName();

            for (var i = 0; i < picks.Length; i++)
            {
                var name = picks[i];
                operatorsByName.TryGetValue(name, out var opData);

                var spawnIdx = i % waypoints.Count;
                var spawnPos = waypoints[spawnIdx].position + Vector3.up * 0.5f;

                var npc = new GameObject($"NPC_{name}");
                npc.transform.SetParent(parent);
                npc.transform.position = spawnPos;

                // Visuel placeholder : essaie d'abord le mesh Meshy .glb (T-pose statique)
                // pour avoir la vraie silhouette de l'opérateur. Fallback sur capsule si .glb
                // introuvable. Quand Mixamo sera fini et BodyPrefab assigné, le NPC
                // utilisera le mesh riggé via OperatorNpcController (au runtime).
                CreateVisualBody(npc.transform, name, opData);

                var agent = npc.AddComponent<NavMeshAgent>();
                agent.height = 1.85f;
                agent.radius = 0.4f;
                agent.speed = opData != null ? opData.WalkSpeed : 4f;
                agent.angularSpeed = 240f;
                agent.acceleration = 12f;
                agent.stoppingDistance = 0.4f;

                // Collider pour rendre le NPC touchable par les raycasts d'arme
                // (HitscanWeapon remonte au HealthSystem via GetComponentInParent).
                var capsule = npc.AddComponent<CapsuleCollider>();
                capsule.height = 1.85f;
                capsule.radius = 0.4f;
                capsule.center = new Vector3(0f, 0.925f, 0f);

                var health = npc.AddComponent<HealthSystem>();
                npc.AddComponent<WorldHealthBar>();    // barre de vie flottante
                var patroller = npc.AddComponent<NavMeshPatroller>();
                patroller.SetWaypoints(waypoints);

                var npcController = npc.AddComponent<OperatorNpcController>();
                if (opData != null) npcController.SetOperator(opData);

                // Cibles d'entraînement résistantes : 300 PV pour voir la barre de vie
                // descendre par paliers (sinon ~5 tirs à 18 dmg = mort en <1s).
                // SetMaxHealth APRÈS SetOperator (qui aurait remis BaseHp).
                health.SetMaxHealth(300);
            }
        }

        // ── Mode Infiltration (Où est Charlie) ────────────────────────────
        [MenuItem("Tools/RocketPi/Setup Hide & Seek")]
        public static void SetupHideAndSeek()
        {
            const int crowdCount = 48;   // foule de faux opérateurs

            var ops = new List<OperatorData>();
            foreach (var kv in LoadOperatorsByName())
                if (kv.Value != null && kv.Value.BodyPrefab != null) ops.Add(kv.Value);
            if (ops.Count == 0)
            {
                EditorUtility.DisplayDialog("Setup Hide & Seek",
                    "Aucun OperatorData avec BodyPrefab. Lance d'abord 'Build Operator Body Prefabs'.", "OK");
                return;
            }

            // Nettoie une foule précédente + les anciens NPC_* (cibles d'entraînement).
            var oldCrowd = GameObject.Find("Crowd");
            if (oldCrowd != null) Object.DestroyImmediate(oldCrowd);
            foreach (var n in new[] { "NPC_Vex", "NPC_Halo", "NPC_Iron", "NPC_Wraith" })
            {
                var g = GameObject.Find(n);
                if (g != null) Object.DestroyImmediate(g);
            }

            var crowd = new GameObject("Crowd");
            int impostorIdx = Random.Range(0, crowdCount);
            int spawned = 0;

            for (var i = 0; i < crowdCount; i++)
            {
                if (!TryRandomNavPoint(64f, out var pos)) continue;
                var op = ops[Random.Range(0, ops.Count)];
                BuildCrowdNpc(crowd.transform, op, pos, isImpostor: i == impostorIdx);
                spawned++;
            }

            // Manager (singleton de scène).
            if (Object.FindAnyObjectByType<Rocketpi.Gameplay.Match.HideSeekManager>() == null)
                new GameObject("HideSeekManager").AddComponent<Rocketpi.Gameplay.Match.HideSeekManager>();

            EditorSceneManager.MarkSceneDirty(EditorSceneManager.GetActiveScene());
            EditorUtility.DisplayDialog("Setup Hide & Seek",
                $"✓ Foule de {spawned} opérateurs (dont 1 imposteur)\n" +
                "✓ Tir + sorts uniquement en courant (PlayerController)\n" +
                "✓ Tuer un faux op = pénalité de vie ; tuer l'imposteur = score\n\n" +
                "Lance le Play : marche pour te fondre, repère celui qui court.", "OK");
        }

        private static bool TryRandomNavPoint(float radius, out Vector3 pos)
        {
            for (var i = 0; i < 30; i++)
            {
                var rnd = new Vector3(Random.Range(-radius, radius), 0f, Random.Range(-radius, radius));
                if (NavMesh.SamplePosition(rnd, out var hit, 6f, NavMesh.AllAreas)) { pos = hit.position; return true; }
            }
            pos = Vector3.zero;
            return false;
        }

        /// <summary>Point NavMesh aléatoire dans un anneau [minR, maxR] autour du centre.</summary>
        private static bool TryRandomNavPointRing(float minR, float maxR, out Vector3 pos)
        {
            for (var i = 0; i < 40; i++)
            {
                var rnd = new Vector3(Random.Range(-maxR, maxR), 0f, Random.Range(-maxR, maxR));
                if (new Vector2(rnd.x, rnd.z).magnitude < minR) continue;
                if (NavMesh.SamplePosition(rnd, out var hit, 6f, NavMesh.AllAreas)
                    && new Vector2(hit.position.x, hit.position.z).magnitude >= minR)
                { pos = hit.position; return true; }
            }
            pos = Vector3.zero;
            return false;
        }

        private static void BuildCrowdNpc(Transform parent, OperatorData op, Vector3 pos, bool isImpostor)
        {
            var npc = new GameObject((isImpostor ? "Impostor_" : "Decoy_") + op.DisplayName);
            npc.transform.SetParent(parent);
            npc.transform.position = pos;

            CreateVisualBody(npc.transform, op.DisplayName, op);

            var agent = npc.AddComponent<NavMeshAgent>();
            agent.height = 1.85f; agent.radius = 0.4f; agent.speed = op.WalkSpeed;
            agent.angularSpeed = 240f; agent.acceleration = 12f; agent.stoppingDistance = 0.4f;

            var capsule = npc.AddComponent<CapsuleCollider>();
            capsule.height = 1.85f; capsule.radius = 0.4f; capsule.center = new Vector3(0f, 0.925f, 0f);

            var health = npc.AddComponent<HealthSystem>();
            npc.AddComponent<WorldHealthBar>();
            npc.AddComponent<NavMeshPatroller>();          // roam forcé par OperatorNpcController.Awake

            var ctrl = npc.AddComponent<OperatorNpcController>();
            ctrl.SetOperator(op);
            ctrl.SetImpostor(isImpostor);

            // Mort rapide (≈ quelques balles) : pas des tanks 300 PV ici.
            health.SetMaxHealth(isImpostor ? 110 : 70);
        }

        private static void CreateVisualBody(Transform parent, string opName, OperatorData opData)
        {
            // Priorité :
            //   1. BodyPrefab assigné sur OperatorData (riggé humanoid + Animator)
            //   2. .glb Meshy statique (silhouette T-pose, pas d'anim)
            //   3. Capsule colorée fallback

            // 1. BodyPrefab Mixamo riggé
            if (opData != null && opData.BodyPrefab != null)
            {
                var bodyInstance = (GameObject)PrefabUtility.InstantiatePrefab(opData.BodyPrefab);
                bodyInstance.name = "Body";
                bodyInstance.transform.SetParent(parent);
                bodyInstance.transform.localPosition = Vector3.zero;
                bodyInstance.transform.localRotation = Quaternion.identity;

                // Recolorage par faction UNIQUEMENT pour le Y Bot partagé (mannequin
                // gris). Les vrais persos Mixamo gardent leurs propres textures.
                var isSharedYBot = opData.BodyPrefab.name.Contains("SharedHumanoid")
                                 || opData.BodyPrefab.name.Contains("YBot");
                if (isSharedYBot)
                {
                    foreach (var r in bodyInstance.GetComponentsInChildren<Renderer>(true))
                    {
                        if (r.sharedMaterial == null) continue;
                        var coloredMat = new Material(r.sharedMaterial) { color = opData.AccentColor };
                        r.sharedMaterial = coloredMat;
                    }
                }
                return;
            }

            // 2. Mesh Meshy statique
            var glbPath = $"Assets/Models/Operators/Raw/{opName}.glb";
            var glbAsset = AssetDatabase.LoadAssetAtPath<GameObject>(glbPath);
            if (glbAsset != null)
            {
                var meshInstance = (GameObject)PrefabUtility.InstantiatePrefab(glbAsset);
                meshInstance.name = "MeshyBody";
                meshInstance.transform.SetParent(parent);
                meshInstance.transform.localPosition = Vector3.zero;
                meshInstance.transform.localRotation = Quaternion.identity;
                foreach (var col in meshInstance.GetComponentsInChildren<Collider>(true))
                    Object.DestroyImmediate(col);
                return;
            }

            // 3. Fallback : capsule colorée
            var placeholder = GameObject.CreatePrimitive(PrimitiveType.Capsule);
            placeholder.name = "PlaceholderBody";
            placeholder.transform.SetParent(parent);
            placeholder.transform.localPosition = new Vector3(0f, 1f, 0f);
            Object.DestroyImmediate(placeholder.GetComponent<Collider>());
            var renderer = placeholder.GetComponent<MeshRenderer>();
            var mat = new Material(renderer.sharedMaterial);
            mat.color = opData != null ? opData.AccentColor : Color.red;
            renderer.sharedMaterial = mat;
        }

        private static Dictionary<string, OperatorData> LoadOperatorsByName()
        {
            var dict = new Dictionary<string, OperatorData>();
            var guids = AssetDatabase.FindAssets("t:OperatorData");
            foreach (var g in guids)
            {
                var path = AssetDatabase.GUIDToAssetPath(g);
                var op = AssetDatabase.LoadAssetAtPath<OperatorData>(path);
                if (op != null) dict[op.DisplayName] = op;
            }
            return dict;
        }

        // ── Build Settings ─────────────────────────────────────────────────

        private static void UpdateBuildSettings()
        {
            var scenes = new List<EditorBuildSettingsScene>(EditorBuildSettings.scenes);
            EnsureSceneInBuild(scenes, BootstrapPath);
            EnsureSceneInBuild(scenes, GameplayPath);
            EditorBuildSettings.scenes = scenes.ToArray();
        }

        private static void EnsureSceneInBuild(List<EditorBuildSettingsScene> scenes, string path)
        {
            foreach (var s in scenes)
                if (s.path == path) { s.enabled = true; return; }
            scenes.Add(new EditorBuildSettingsScene(path, enabled: true));
        }

        private static void EnsureFolder(string path)
        {
            if (AssetDatabase.IsValidFolder(path)) return;
            var parts = path.Split('/');
            var current = parts[0];
            for (var i = 1; i < parts.Length; i++)
            {
                var next = $"{current}/{parts[i]}";
                if (!AssetDatabase.IsValidFolder(next))
                    AssetDatabase.CreateFolder(current, parts[i]);
                current = next;
            }
        }
    }
}
#endif
