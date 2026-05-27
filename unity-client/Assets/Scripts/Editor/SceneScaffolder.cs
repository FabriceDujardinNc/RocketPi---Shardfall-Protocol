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
using Rocketpi.Gameplay.Body;
using Rocketpi.Gameplay.CameraControl;
using Rocketpi.Gameplay.Match;
using Rocketpi.Gameplay.NPC;
using Rocketpi.Gameplay.Operators;
using Rocketpi.Gameplay.PowerUps;
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

            // 2. (Re)crée le conteneur de pickups.
            var existing = GameObject.Find("PowerUps");
            if (existing != null) Object.DestroyImmediate(existing);
            var root = new GameObject("PowerUps");

            var types = (PowerUpType[])System.Enum.GetValues(typeof(PowerUpType));

            // Au sol : un de chaque type, réparti en cercle (rayon 9).
            for (var i = 0; i < types.Length; i++)
            {
                var angle = i * Mathf.PI * 2f / types.Length;
                var pos = new Vector3(Mathf.Cos(angle) * 9f, 0f, Mathf.Sin(angle) * 9f);
                CreatePickup(root.transform, types[i], pos);
            }

            // En hauteur (au-dessus du fort) : les bonus "forts" à récupérer en sautant /
            // depuis les remparts. y=6 au centre + 2 coins.
            CreatePickup(root.transform, PowerUpType.MegaBomb,   new Vector3( 0f, 6f,  0f));
            CreatePickup(root.transform, PowerUpType.QuadDamage, new Vector3( 6f, 6f,  6f));
            CreatePickup(root.transform, PowerUpType.Shield,     new Vector3(-6f, 6f, -6f));

            EditorSceneManager.MarkSceneDirty(scene);
            Debug.Log($"[RocketPi] {types.Length + 3} power-ups placés (sol + fort).");
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

            // Vue top-down assez haute pour englober les 6 waypoints (rayon 15m) + le château
            sceneView.LookAtDirect(new Vector3(0f, 0f, 0f), Quaternion.Euler(55f, 35f, 0f), 50f);
            sceneView.Repaint();
            sceneView.camera.transform.position = new Vector3(35f, 50f, -35f);
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
                // Override couleur sur tous les renderers pour distinguer les opérateurs
                // (Y Bot partagé entre les 4 NPCs → besoin de varier la couleur).
                foreach (var r in bodyInstance.GetComponentsInChildren<Renderer>(true))
                {
                    if (r.sharedMaterial == null) continue;
                    var coloredMat = new Material(r.sharedMaterial);
                    coloredMat.color = opData.AccentColor;
                    r.sharedMaterial = coloredMat;
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
