// AssetPipelineTools.cs — Outils éditeur pour la pipeline d'opérateurs.
//
// Menus :
//   - Tools > RocketPi > Configure Operator Imports
//   - Tools > RocketPi > Build Locomotion Controller
//   - Tools > RocketPi > Build Operator Body Prefabs
//
// Étapes attendues côté user AVANT de lancer ces commandes :
//   1. Avoir uploadé chaque .glb sur Mixamo, fait l'auto-rig, et téléchargé le .fbx
//      sous Assets/Models/Operators/Rigged/{OpName}.fbx (cf. MIXAMO_PIPELINE.md)
//   2. Avoir téléchargé les anims Humanoid sous Assets/Animations/Locomotion/{Anim}.fbx
//      (Idle, Walk, Run, Death, Jump, Fall, AimIdle, Fire — voir doc)

#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using System.Linq;
using Rocketpi.Gameplay.Body;
using Rocketpi.Gameplay.Operators;
using UnityEditor;
using UnityEditor.Animations;
using UnityEngine;

namespace Rocketpi.Editor
{
    public static class AssetPipelineTools
    {
        private const string RiggedDir       = "Assets/Models/Operators/Rigged";
        private const string LocomotionDir   = "Assets/Animations/Locomotion";
        private const string OperatorsResDir = "Assets/Resources/Operators";
        private const string BodyPrefabsDir  = "Assets/Prefabs/Operators";
        private const string ControllerPath  = "Assets/Animations/OperatorLocomotion.controller";
        private const string SharedHumanoidPath = "Assets/Prefabs/Operators/SharedHumanoidBody.prefab";

        // Loop-true par défaut
        private static readonly HashSet<string> NonLoopingAnims = new()
        {
            "Death", "Jump", "Fire", "Reload"
        };

        // ─── 1. Configure Operator Imports ─────────────────────────────────

        [MenuItem("Tools/RocketPi/Configure Operator Imports")]
        public static void ConfigureOperatorImports()
        {
            EnsureFolder(RiggedDir);
            EnsureFolder(LocomotionDir);

            var riggedFiles = Directory.Exists(RiggedDir)
                ? Directory.GetFiles(RiggedDir, "*.fbx").Concat(Directory.GetFiles(RiggedDir, "*.glb")).ToArray()
                : System.Array.Empty<string>();

            if (riggedFiles.Length == 0)
            {
                EditorUtility.DisplayDialog("Configure Operator Imports",
                    $"Aucun .fbx ou .glb trouvé dans {RiggedDir}.\n\n" +
                    "Avant de lancer cette commande, place les .fbx riggés Mixamo dans ce dossier " +
                    "(cf. docs/MIXAMO_PIPELINE.md).", "OK");
                return;
            }

            string firstAvatarPath = null;

            // Configure les meshes riggés
            foreach (var raw in riggedFiles)
            {
                var path = raw.Replace('\\', '/');
                var importer = AssetImporter.GetAtPath(path) as ModelImporter;
                if (importer == null) continue;

                importer.animationType = ModelImporterAnimationType.Human;
                importer.avatarSetup   = ModelImporterAvatarSetup.CreateFromThisModel;
                importer.optimizeGameObjects = false;
                importer.useFileScale  = true;
                importer.isReadable    = true;
                importer.importBlendShapes = false;
                importer.importAnimation = false; // mesh seul, pas d'anim embarquée
                importer.SaveAndReimport();
                Debug.Log($"[RocketPi] Configured rig: {path}");
                if (firstAvatarPath == null) firstAvatarPath = path;
            }

            // Configure les anims
            var locFiles = Directory.Exists(LocomotionDir)
                ? Directory.GetFiles(LocomotionDir, "*.fbx").Concat(Directory.GetFiles(LocomotionDir, "*.glb")).ToArray()
                : System.Array.Empty<string>();

            Avatar sourceAvatar = null;
            if (firstAvatarPath != null)
            {
                sourceAvatar = AssetDatabase.LoadAllAssetsAtPath(firstAvatarPath)
                    .OfType<Avatar>()
                    .FirstOrDefault();
            }

            foreach (var raw in locFiles)
            {
                var path = raw.Replace('\\', '/');
                var importer = AssetImporter.GetAtPath(path) as ModelImporter;
                if (importer == null) continue;

                importer.animationType = ModelImporterAnimationType.Human;
                if (sourceAvatar != null)
                {
                    importer.avatarSetup = ModelImporterAvatarSetup.CopyFromOther;
                    importer.sourceAvatar = sourceAvatar;
                }
                importer.optimizeGameObjects = false;
                importer.useFileScale = true;
                importer.importAnimation = true;

                // Configure le clip
                var clips = importer.defaultClipAnimations;
                var fileName = Path.GetFileNameWithoutExtension(path);
                if (clips != null && clips.Length > 0)
                {
                    var isLooping = !NonLoopingAnims.Contains(fileName);
                    for (var i = 0; i < clips.Length; i++)
                    {
                        clips[i].loopTime   = isLooping;
                        clips[i].lockRootRotation = true;
                        clips[i].lockRootHeightY  = true;
                        clips[i].keepOriginalOrientation = true;
                        clips[i].keepOriginalPositionY   = true;
                        clips[i].keepOriginalPositionXZ  = false; // strip XZ pour avoir un cycle in-place
                    }
                    importer.clipAnimations = clips;
                }

                importer.SaveAndReimport();
                Debug.Log($"[RocketPi] Configured anim: {path} (loop={!NonLoopingAnims.Contains(fileName)})");
            }

            EditorUtility.DisplayDialog("Configure Operator Imports",
                $"Configurés :\n• {riggedFiles.Length} rigs\n• {locFiles.Length} animations",
                "OK");
        }

        // ─── 2. Build Locomotion Controller ────────────────────────────────

        [MenuItem("Tools/RocketPi/Build Locomotion Controller")]
        public static void BuildLocomotionController()
        {
            EnsureFolder("Assets/Animations");

            // Charge tous les clips disponibles
            var idle      = LoadClipByName("Idle");
            var walk      = LoadClipByName("Walk");
            var run       = LoadClipByName("Run");
            var sprint    = LoadClipByName("Sprint") ?? run; // fallback
            var death     = LoadClipByName("Death");
            var jump      = LoadClipByName("Jump");
            var fall      = LoadClipByName("Fall");
            var aimIdle   = LoadClipByName("AimIdle");
            var fire      = LoadClipByName("Fire");

            if (idle == null || walk == null || run == null)
            {
                EditorUtility.DisplayDialog("Build Locomotion Controller",
                    "Clips Idle/Walk/Run requis introuvables dans Assets/Animations/Locomotion/. " +
                    "Lance d'abord 'Configure Operator Imports' après avoir téléchargé les .fbx Mixamo.",
                    "OK");
                return;
            }

            if (File.Exists(ControllerPath)) AssetDatabase.DeleteAsset(ControllerPath);
            var controller = AnimatorController.CreateAnimatorControllerAtPath(ControllerPath);

            // ── Parameters ────────────────────────────────────────────────
            controller.AddParameter("Speed",       AnimatorControllerParameterType.Float);
            controller.AddParameter("IsGrounded",  AnimatorControllerParameterType.Bool);
            controller.AddParameter("IsSprinting", AnimatorControllerParameterType.Bool);
            controller.AddParameter("Vertical",    AnimatorControllerParameterType.Float);
            controller.AddParameter("Die",         AnimatorControllerParameterType.Trigger);
            controller.AddParameter("Fire",        AnimatorControllerParameterType.Trigger);

            // Force IsGrounded = true par défaut
            var ig = controller.parameters[1]; ig.defaultBool = true; controller.parameters = controller.parameters;

            // ── Layer 0 : Base / Locomotion ──────────────────────────────
            var baseLayer = controller.layers[0];
            var sm = baseLayer.stateMachine;
            sm.entryPosition = new Vector3(50, 0, 0);
            sm.anyStatePosition = new Vector3(50, 80, 0);
            sm.exitPosition = new Vector3(500, 0, 0);

            // Blend Tree 1D Speed → Idle/Walk/Run
            var blendTree = new BlendTree
            {
                name = "Locomotion",
                blendType = BlendTreeType.Simple1D,
                blendParameter = "Speed",
                hideFlags = HideFlags.HideInHierarchy
            };
            blendTree.AddChild(idle,   0f);
            blendTree.AddChild(walk,   0.5f);
            blendTree.AddChild(run,    1.0f);
            AssetDatabase.AddObjectToAsset(blendTree, controller);

            var locomotionState = sm.AddState("Locomotion", new Vector3(250, 0, 0));
            locomotionState.motion = blendTree;
            sm.defaultState = locomotionState;

            // ── Air states ────────────────────────────────────────────────
            if (jump != null)
            {
                var jumpState = sm.AddState("Jump", new Vector3(250, 120, 0));
                jumpState.motion = jump;

                var toJump = sm.AddAnyStateTransition(jumpState);
                toJump.AddCondition(AnimatorConditionMode.IfNot, 0, "IsGrounded");
                toJump.AddCondition(AnimatorConditionMode.Greater, 0.2f, "Vertical");
                toJump.duration = 0.1f;
                toJump.hasExitTime = false;
                toJump.canTransitionToSelf = false;

                if (fall != null)
                {
                    var fallState = sm.AddState("Fall", new Vector3(400, 120, 0));
                    fallState.motion = fall;
                    var jumpToFall = jumpState.AddTransition(fallState);
                    jumpToFall.AddCondition(AnimatorConditionMode.Less, 0.0f, "Vertical");
                    jumpToFall.hasExitTime = false;
                    jumpToFall.duration = 0.15f;

                    var fallToLocomotion = fallState.AddTransition(locomotionState);
                    fallToLocomotion.AddCondition(AnimatorConditionMode.If, 0, "IsGrounded");
                    fallToLocomotion.hasExitTime = false;
                    fallToLocomotion.duration = 0.2f;
                }

                var jumpToLocomotion = jumpState.AddTransition(locomotionState);
                jumpToLocomotion.AddCondition(AnimatorConditionMode.If, 0, "IsGrounded");
                jumpToLocomotion.hasExitTime = false;
                jumpToLocomotion.duration = 0.2f;
            }

            // ── Death ────────────────────────────────────────────────────
            if (death != null)
            {
                var deathState = sm.AddState("Death", new Vector3(250, 240, 0));
                deathState.motion = death;
                var toDeath = sm.AddAnyStateTransition(deathState);
                toDeath.AddCondition(AnimatorConditionMode.If, 0, "Die");
                toDeath.duration = 0.1f;
                toDeath.hasExitTime = false;
                toDeath.canTransitionToSelf = false;
            }

            // ── Layer 1 : Combat (additive) ──────────────────────────────
            if (aimIdle != null || fire != null)
            {
                var combatLayer = new AnimatorControllerLayer
                {
                    name = "Combat",
                    defaultWeight = 0f,
                    blendingMode = AnimatorLayerBlendingMode.Additive,
                    stateMachine = new AnimatorStateMachine { name = "Combat", hideFlags = HideFlags.HideInHierarchy }
                };
                AssetDatabase.AddObjectToAsset(combatLayer.stateMachine, controller);
                controller.AddLayer(combatLayer);

                var combatSm = combatLayer.stateMachine;
                var idleState = combatSm.AddState(aimIdle != null ? "AimIdle" : "Empty", new Vector3(250, 0, 0));
                if (aimIdle != null) idleState.motion = aimIdle;
                combatSm.defaultState = idleState;

                if (fire != null)
                {
                    var fireState = combatSm.AddState("Fire", new Vector3(450, 0, 0));
                    fireState.motion = fire;
                    var toFire = combatSm.AddAnyStateTransition(fireState);
                    toFire.AddCondition(AnimatorConditionMode.If, 0, "Fire");
                    toFire.duration = 0.05f;
                    toFire.hasExitTime = false;
                    toFire.canTransitionToSelf = true;

                    var fireToIdle = fireState.AddTransition(idleState);
                    fireToIdle.hasExitTime = true;
                    fireToIdle.exitTime = 0.9f;
                    fireToIdle.duration = 0.1f;
                }
            }

            EditorUtility.SetDirty(controller);
            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();
            EditorUtility.DisplayDialog("Build Locomotion Controller",
                $"Controller créé : {ControllerPath}", "OK");
        }

        // ─── 3. Build Operator Body Prefabs ────────────────────────────────

        [MenuItem("Tools/RocketPi/Build Operator Body Prefabs")]
        public static void BuildOperatorBodyPrefabs()
        {
            EnsureFolder(BodyPrefabsDir);

            var controller = AssetDatabase.LoadAssetAtPath<AnimatorController>(ControllerPath);
            if (controller == null)
            {
                EditorUtility.DisplayDialog("Build Operator Body Prefabs",
                    "Controller introuvable. Lance d'abord 'Build Locomotion Controller'.", "OK");
                return;
            }

            var riggedFiles = Directory.Exists(RiggedDir)
                ? Directory.GetFiles(RiggedDir, "*.fbx").Concat(Directory.GetFiles(RiggedDir, "*.glb")).ToArray()
                : System.Array.Empty<string>();

            if (riggedFiles.Length == 0)
            {
                EditorUtility.DisplayDialog("Build Operator Body Prefabs",
                    $"Aucun mesh riggé dans {RiggedDir}.", "OK");
                return;
            }

            var operatorsByName = LoadOperatorsByName();
            int built = 0;

            foreach (var raw in riggedFiles)
            {
                var path = raw.Replace('\\', '/');
                var fbxModel = AssetDatabase.LoadAssetAtPath<GameObject>(path);
                if (fbxModel == null) continue;

                var opName = Path.GetFileNameWithoutExtension(path);
                var prefabPath = $"{BodyPrefabsDir}/{opName}Body.prefab";

                // Instancie le modèle, ajoute Animator + OperatorBody, sauve en prefab
                var instance = (GameObject)PrefabUtility.InstantiatePrefab(fbxModel);
                instance.name = $"{opName}Body";

                var animator = instance.GetComponent<Animator>() ?? instance.AddComponent<Animator>();
                animator.runtimeAnimatorController = controller;
                animator.applyRootMotion = false;

                // Récupère l'avatar généré par l'import
                var avatar = AssetDatabase.LoadAllAssetsAtPath(path).OfType<Avatar>().FirstOrDefault();
                if (avatar != null) animator.avatar = avatar;

                var body = instance.GetComponent<OperatorBody>() ?? instance.AddComponent<OperatorBody>();

                // Save prefab
                var prefab = PrefabUtility.SaveAsPrefabAsset(instance, prefabPath);
                Object.DestroyImmediate(instance);

                built++;

                // Tente d'auto-link sur l'OperatorData correspondant
                if (operatorsByName.TryGetValue(opName, out var opData))
                {
                    var so = new SerializedObject(opData);
                    so.FindProperty("BodyPrefab").objectReferenceValue = prefab;
                    so.ApplyModifiedPropertiesWithoutUndo();
                    EditorUtility.SetDirty(opData);
                }
            }

            AssetDatabase.SaveAssets();
            EditorUtility.DisplayDialog("Build Operator Body Prefabs",
                $"Construit {built} prefab(s) dans {BodyPrefabsDir}/", "OK");
        }

        // ─── 4. Setup Shared Humanoid Placeholder (Y Bot Mixamo) ───────────
        //
        // Pipeline complet en 1 commande :
        //   - configure les imports Humanoid sur YBot.fbx + 3 anims
        //   - build OperatorLocomotion.controller
        //   - crée SharedHumanoidBody.prefab basé sur YBot
        //   - auto-assigne ce prefab à tous les 8 OperatorData.BodyPrefab
        // Tous les NPCs partagent le même rig humanoid Mixamo, avec couleur
        // override par opérateur (via OperatorData.AccentColor appliqué côté CreateNpcs).

        [MenuItem("Tools/RocketPi/Setup Shared Humanoid Placeholder")]
        public static void SetupSharedHumanoidPlaceholder()
        {
            EnsureFolder(RiggedDir);
            EnsureFolder(LocomotionDir);
            EnsureFolder(BodyPrefabsDir);

            // Validation : Y Bot doit exister
            var yBotPath = $"{RiggedDir}/YBot.fbx";
            if (!File.Exists(yBotPath))
            {
                EditorUtility.DisplayDialog("Setup Shared Humanoid",
                    $"Fichier {yBotPath} introuvable.\n\n" +
                    "Télécharge Y Bot depuis https://www.mixamo.com (Characters > Y Bot > Download FBX With Skin) " +
                    "et place-le dans Assets/Models/Operators/Rigged/YBot.fbx.", "OK");
                return;
            }

            // Configure le rig Y Bot
            var yBotImporter = AssetImporter.GetAtPath(yBotPath) as ModelImporter;
            if (yBotImporter != null)
            {
                yBotImporter.animationType = ModelImporterAnimationType.Human;
                yBotImporter.avatarSetup   = ModelImporterAvatarSetup.CreateFromThisModel;
                yBotImporter.optimizeGameObjects = false;
                yBotImporter.useFileScale = true;
                yBotImporter.importBlendShapes = false;
                yBotImporter.importAnimation = false;
                yBotImporter.SaveAndReimport();
                Debug.Log($"[RocketPi] YBot rig configured.");
            }

            var yBotAvatar = AssetDatabase.LoadAllAssetsAtPath(yBotPath).OfType<Avatar>().FirstOrDefault();

            // Validation : Idle/Walk/Run requis. Death/Jump/Fire/etc. optionnels.
            string[] requiredAnims = { "Idle", "Walk", "Run" };
            var missingAnims = new List<string>();
            foreach (var n in requiredAnims)
            {
                var p = $"{LocomotionDir}/{n}.fbx";
                if (!File.Exists(p)) missingAnims.Add(n);
            }
            if (missingAnims.Count > 0)
            {
                EditorUtility.DisplayDialog("Setup Shared Humanoid",
                    $"Anims manquantes : {string.Join(", ", missingAnims.Select(n => $"{n}.fbx"))}\n\n" +
                    "Télécharge depuis Mixamo (Animations > Breathing Idle / Walking / Running, In Place + Without Skin) " +
                    "et place-les dans Assets/Animations/Locomotion/.", "OK");
                return;
            }

            // Configure TOUS les .fbx présents dans Locomotion/ (incl. Death/Jump/Fire
            // s'ils ont été ajoutés). loopTime selon NonLoopingAnims (Death/Jump/Fire
            // sont one-shot, ne doivent pas boucler).
            var locFbx = Directory.GetFiles(LocomotionDir, "*.fbx");
            foreach (var p in locFbx)
            {
                var imp = AssetImporter.GetAtPath(p.Replace('\\', '/')) as ModelImporter;
                if (imp == null) continue;
                var clipName = Path.GetFileNameWithoutExtension(p);
                var isLooping = !NonLoopingAnims.Contains(clipName);

                imp.animationType = ModelImporterAnimationType.Human;
                if (yBotAvatar != null)
                {
                    imp.avatarSetup = ModelImporterAvatarSetup.CopyFromOther;
                    imp.sourceAvatar = yBotAvatar;
                }
                imp.optimizeGameObjects = false;
                imp.useFileScale = true;
                imp.importAnimation = true;

                var clips = imp.defaultClipAnimations;
                if (clips != null && clips.Length > 0)
                {
                    for (var i = 0; i < clips.Length; i++)
                    {
                        clips[i].loopTime = isLooping;
                        clips[i].lockRootRotation = true;
                        clips[i].lockRootHeightY  = true;
                        clips[i].keepOriginalOrientation = true;
                        clips[i].keepOriginalPositionY   = true;
                        clips[i].keepOriginalPositionXZ  = false;
                    }
                    imp.clipAnimations = clips;
                }
                imp.SaveAndReimport();
                Debug.Log($"[RocketPi] Anim configured: {p}");
            }

            // Build OperatorLocomotion controller (réutilise BuildLocomotionController)
            BuildLocomotionController();

            var controller = AssetDatabase.LoadAssetAtPath<AnimatorController>(ControllerPath);
            if (controller == null)
            {
                Debug.LogError("[RocketPi] Controller non créé. Échec.");
                return;
            }

            // Crée SharedHumanoidBody.prefab
            if (File.Exists(SharedHumanoidPath)) AssetDatabase.DeleteAsset(SharedHumanoidPath);
            var yBotModel = AssetDatabase.LoadAssetAtPath<GameObject>(yBotPath);
            var instance = (GameObject)PrefabUtility.InstantiatePrefab(yBotModel);
            instance.name = "SharedHumanoidBody";

            var animator = instance.GetComponent<Animator>() ?? instance.AddComponent<Animator>();
            animator.runtimeAnimatorController = controller;
            animator.applyRootMotion = false;
            if (yBotAvatar != null) animator.avatar = yBotAvatar;

            if (instance.GetComponent<OperatorBody>() == null) instance.AddComponent<OperatorBody>();

            var prefab = PrefabUtility.SaveAsPrefabAsset(instance, SharedHumanoidPath);
            Object.DestroyImmediate(instance);

            // Assigne ce prefab à TOUS les OperatorData
            var ops = LoadOperatorsByName();
            int assigned = 0;
            foreach (var op in ops.Values)
            {
                var so = new SerializedObject(op);
                so.FindProperty("BodyPrefab").objectReferenceValue = prefab;
                so.ApplyModifiedPropertiesWithoutUndo();
                EditorUtility.SetDirty(op);
                assigned++;
            }
            AssetDatabase.SaveAssets();

            EditorUtility.DisplayDialog("Setup Shared Humanoid",
                $"✓ YBot riggé + 3 anims configurées Humanoid\n" +
                $"✓ OperatorLocomotion.controller (Idle/Walk/Run Blend Tree)\n" +
                $"✓ SharedHumanoidBody.prefab créé\n" +
                $"✓ Assigné à {assigned} OperatorData\n\n" +
                "Lance maintenant 'Add NPCs to Current Scene' pour remplacer les capsules par le mesh humanoid.",
                "OK");
        }

        // ─── Helpers ───────────────────────────────────────────────────────

        private static AnimationClip LoadClipByName(string name)
        {
            var path = $"{LocomotionDir}/{name}.fbx";
            if (!File.Exists(path)) path = $"{LocomotionDir}/{name}.glb";
            if (!File.Exists(path)) return null;
            return AssetDatabase.LoadAllAssetsAtPath(path).OfType<AnimationClip>()
                .FirstOrDefault(c => !c.name.StartsWith("__preview__"));
        }

        private static Dictionary<string, OperatorData> LoadOperatorsByName()
        {
            var dict = new Dictionary<string, OperatorData>();
            var guids = AssetDatabase.FindAssets("t:OperatorData");
            foreach (var g in guids)
            {
                var p = AssetDatabase.GUIDToAssetPath(g);
                var op = AssetDatabase.LoadAssetAtPath<OperatorData>(p);
                if (op != null && !string.IsNullOrEmpty(op.DisplayName))
                    dict[op.DisplayName] = op;
            }
            return dict;
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
