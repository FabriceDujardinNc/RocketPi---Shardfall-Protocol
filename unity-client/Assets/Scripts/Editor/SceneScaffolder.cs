// SceneScaffolder.cs — Crée les scènes Bootstrap, Gameplay, et configure
// les Build Settings pour inclure les bonnes scènes.
//
// Tools > RocketPi > Scaffold Scenes
//
// Idempotent : ne recrée pas une scène qui existe déjà.

#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using Rocketpi.Bridge;
using Rocketpi.Gameplay;
using Rocketpi.Gameplay.Match;
using Rocketpi.UI;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

namespace Rocketpi.Editor
{
    public static class SceneScaffolder
    {
        private const string ScenesDir = "Assets/Scenes";
        private const string BootstrapPath = "Assets/Scenes/Bootstrap.unity";
        private const string GameplayPath  = "Assets/Scenes/Training.unity";

        [MenuItem("Tools/RocketPi/Scaffold Scenes")]
        public static void ScaffoldScenes()
        {
            if (!AssetDatabase.IsValidFolder(ScenesDir))
                AssetDatabase.CreateFolder("Assets", "Scenes");

            CreateBootstrap();
            CreateTraining();
            UpdateBuildSettings();

            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();
            Debug.Log("[RocketPi] Scenes scaffolded. Open Bootstrap.unity in Hierarchy to verify.");
        }

        // ── Bootstrap : juste le RocketpiBridge singleton ──────────────────

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

        // ── Training : ground + player + spawner + UI ──────────────────────

        private static void CreateTraining()
        {
            if (File.Exists(GameplayPath))
            {
                Debug.Log("[RocketPi] Training.unity exists, skipping.");
                return;
            }

            var scene = EditorSceneManager.NewScene(NewSceneSetup.EmptyScene, NewSceneMode.Single);
            scene.name = "Training";

            // Ground 50x50 sol plat
            var ground = GameObject.CreatePrimitive(PrimitiveType.Plane);
            ground.name = "Ground";
            ground.transform.localScale = new Vector3(5f, 1f, 5f);

            // Light
            var lightGo = new GameObject("Directional Light");
            var light = lightGo.AddComponent<Light>();
            light.type = LightType.Directional;
            light.intensity = 1f;
            lightGo.transform.rotation = Quaternion.Euler(45f, 30f, 0f);

            // Player capsule (CharacterController + PlayerController + HealthSystem + Camera)
            var playerGo = new GameObject("Player");
            var cc = playerGo.AddComponent<CharacterController>();
            cc.height = 1.8f;
            cc.radius = 0.4f;
            cc.center = new Vector3(0f, 0.9f, 0f);
            var player = playerGo.AddComponent<PlayerController>();
            playerGo.AddComponent<HealthSystem>();
            playerGo.tag = "Player";
            var playerLayer = LayerMask.NameToLayer("Player");
            if (playerLayer >= 0) playerGo.layer = playerLayer;
            else Debug.LogWarning("[RocketPi] Layer 'Player' absent — Player reste sur Default. Crée-le via Edit > Project Settings > Tags & Layers.");
            playerGo.transform.position = new Vector3(0f, 0.1f, 0f);

            var camGo = new GameObject("Camera");
            camGo.transform.SetParent(playerGo.transform, false);
            camGo.transform.localPosition = new Vector3(0f, 1.65f, 0f);
            var cam = camGo.AddComponent<Camera>();
            cam.fieldOfView = 75f;
            camGo.AddComponent<AudioListener>();

            var weaponSocket = new GameObject("WeaponSocket");
            weaponSocket.transform.SetParent(camGo.transform, false);
            weaponSocket.transform.localPosition = new Vector3(0.25f, -0.2f, 0.5f);

            // Spawner zone (cf. TargetSpawner — il faudra plus tard assigner un prefab cible)
            var spawnerGo = new GameObject("TargetSpawner");
            spawnerGo.transform.position = new Vector3(0f, 0.5f, 10f);
            spawnerGo.AddComponent<TargetSpawner>();

            // Match manager
            var matchGo = new GameObject("TrainingMatchManager");
            var match = matchGo.AddComponent<TrainingMatchManager>();

            // Canvas avec HUD + MainMenu + Summary stubs (les TMP labels sont à
            // brancher manuellement — c'est le seul morceau qu'on ne peut pas
            // entièrement scripter sans risquer de corrompre les références).
            var canvasGo = new GameObject("Canvas");
            var canvas = canvasGo.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvasGo.AddComponent<UnityEngine.UI.CanvasScaler>();
            canvasGo.AddComponent<UnityEngine.UI.GraphicRaycaster>();

            canvasGo.AddComponent<HudController>();
            canvasGo.AddComponent<MainMenuController>();
            canvasGo.AddComponent<MatchSummaryController>();

            // EventSystem requis pour les boutons UI
            var esGo = new GameObject("EventSystem");
            esGo.AddComponent<UnityEngine.EventSystems.EventSystem>();
            esGo.AddComponent<UnityEngine.InputSystem.UI.InputSystemUIInputModule>();

            EditorSceneManager.SaveScene(scene, GameplayPath);
        }

        // ── Build Settings : ajouter les 2 scènes dans l'ordre ─────────────

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
    }
}
#endif
