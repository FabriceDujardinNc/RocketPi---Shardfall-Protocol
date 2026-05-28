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
            "Death", "Jump", "Fire", "Reload", "Flip", "Rifle-Jump", "RifleJump"
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
            var rifleJump = LoadClipByName("Rifle-Jump") ?? LoadClipByName("RifleJump");
            var flip      = LoadClipByName("Flip");
            var fall      = LoadClipByName("Fall");
            var aimIdle   = LoadClipByName("AimIdle");
            var fire      = LoadClipByName("Fire");
            var reload    = LoadClipByName("Reload");

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
            controller.AddParameter("Reload",      AnimatorControllerParameterType.Trigger);
            controller.AddParameter("Flip",        AnimatorControllerParameterType.Trigger);

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
                // Saut debout (Jump) ou saut en course avec arme (Rifle-Jump) selon Speed.
                if (rifleJump != null)
                {
                    var jumpTree = new BlendTree
                    {
                        name = "JumpBlend", blendType = BlendTreeType.Simple1D,
                        blendParameter = "Speed", hideFlags = HideFlags.HideInHierarchy
                    };
                    jumpTree.AddChild(jump,      0f);     // immobile → saut debout
                    jumpTree.AddChild(rifleJump, 0.5f);   // en mouvement → saut arme
                    AssetDatabase.AddObjectToAsset(jumpTree, controller);
                    jumpState.motion = jumpTree;
                }
                else jumpState.motion = jump;

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

            // ── Flip (salto du double saut, déclenché par le trigger "Flip") ──
            if (flip != null)
            {
                var flipState = sm.AddState("Flip", new Vector3(450, 120, 0));
                flipState.motion = flip;
                var toFlip = sm.AddAnyStateTransition(flipState);
                toFlip.AddCondition(AnimatorConditionMode.If, 0, "Flip");
                toFlip.duration = 0.05f;
                toFlip.hasExitTime = false;
                toFlip.canTransitionToSelf = true;

                // Retour au sol → locomotion ; sécurité : sortie en fin de clip.
                var flipToLoco = flipState.AddTransition(locomotionState);
                flipToLoco.AddCondition(AnimatorConditionMode.If, 0, "IsGrounded");
                flipToLoco.hasExitTime = false;
                flipToLoco.duration = 0.15f;
                var flipTimeout = flipState.AddTransition(locomotionState);
                flipTimeout.hasExitTime = true; flipTimeout.exitTime = 0.95f; flipTimeout.duration = 0.1f;
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

            // ── Reload ───────────────────────────────────────────────────
            // Joue l'anim de recharge en entier puis revient à la locomotion.
            // (Interrompt le bas du corps — acceptable en proto ; un avatar mask
            // upper-body viendrait plus tard pour recharger en marchant.)
            if (reload != null)
            {
                var reloadState = sm.AddState("Reload", new Vector3(450, 240, 0));
                reloadState.motion = reload;
                var toReload = sm.AddAnyStateTransition(reloadState);
                toReload.AddCondition(AnimatorConditionMode.If, 0, "Reload");
                toReload.duration = 0.1f;
                toReload.hasExitTime = false;
                toReload.canTransitionToSelf = false;

                var reloadToLocomotion = reloadState.AddTransition(locomotionState);
                reloadToLocomotion.hasExitTime = true;
                reloadToLocomotion.exitTime = 0.9f;     // 90% du clip
                reloadToLocomotion.duration = 0.15f;
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

        // ─── Câblage saut/salto (Jump, Rifle-Jump, Flip) ───────────────────
        // Configure TOUTES les anims Locomotion en Humanoid (sans toucher aux rigs des
        // opérateurs → ne casse pas Crag), reconstruit le controller (états Jump blend +
        // Flip), puis relie le controller à tous les body prefabs.
        [MenuItem("Tools/RocketPi/Wire Jump Animations")]
        public static void WireJumpAnimations()
        {
            // 1. Avatar source Humanoid valide (n'importe quel opérateur déjà riggé).
            Avatar src = null;
            foreach (var raw in Directory.GetFiles(RiggedDir, "*.fbx"))
            {
                var a = AssetDatabase.LoadAllAssetsAtPath(raw.Replace('\\', '/')).OfType<Avatar>().FirstOrDefault();
                if (a != null && a.isValid && a.isHuman) { src = a; break; }
            }

            // 2. Configure toutes les anims locomotion (incl. Jump/Rifle-Jump/Flip).
            foreach (var raw in Directory.GetFiles(LocomotionDir, "*.fbx"))
            {
                var path = raw.Replace('\\', '/');
                if (AssetImporter.GetAtPath(path) is not ModelImporter imp) continue;
                imp.animationType = ModelImporterAnimationType.Human;
                if (src != null) { imp.avatarSetup = ModelImporterAvatarSetup.CopyFromOther; imp.sourceAvatar = src; }
                imp.optimizeGameObjects = false;
                imp.useFileScale = true;
                imp.importAnimation = true;

                var name = Path.GetFileNameWithoutExtension(path);
                var clips = imp.defaultClipAnimations;
                if (clips != null && clips.Length > 0)
                {
                    var loop = !NonLoopingAnims.Contains(name);
                    for (var i = 0; i < clips.Length; i++)
                    {
                        clips[i].name = name;
                        clips[i].loopTime = loop;
                        clips[i].lockRootRotation = true;
                        clips[i].lockRootHeightY  = true;
                        clips[i].keepOriginalOrientation = true;
                        clips[i].keepOriginalPositionY   = true;
                        clips[i].keepOriginalPositionXZ  = false;
                    }
                    imp.clipAnimations = clips;
                }
                imp.SaveAndReimport();
            }

            // 3. Reconstruit le controller (GUID change) puis relie aux bodies.
            BuildLocomotionController();
            DiagnoseAndRepairAvatars();
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
                        // Renomme le clip au nom du fichier (Mixamo les nomme tous
                        // "mixamo.com" → impossible de retrouver Reload par nom au runtime).
                        clips[i].name = clipName;
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

        // ─── 5. Extract Operator Textures (URP) ────────────────────────────
        //
        // Les FBX Mixamo embarquent les textures (diffuse + normal) dans le binaire.
        // En URP, le matériau généré n'attache PAS la texture → rendu blanc.
        // Cette commande :
        //   1. extrait les textures embarquées vers Rigged/Textures/
        //   2. extrait les matériaux vers Rigged/Materials/
        //   3. force chaque matériau en URP/Lit + bind BaseMap (diffuse) / BumpMap (normal)
        //      par convention de nom Mixamo (*_Diffuse, *_Normal), et remappe le FBX dessus.

        [MenuItem("Tools/RocketPi/Extract Operator Textures (URP)")]
        public static void ExtractOperatorTextures()
        {
            var texDir = $"{RiggedDir}/Textures";
            var matDir = $"{RiggedDir}/Materials";
            // Repart propre : un run précédent a pu mélanger les textures (dossier partagé).
            if (AssetDatabase.IsValidFolder(texDir)) AssetDatabase.DeleteAsset(texDir);
            EnsureFolder(texDir);
            EnsureFolder(matDir);

            var fbxFiles = Directory.Exists(RiggedDir)
                ? Directory.GetFiles(RiggedDir, "*.fbx")
                : System.Array.Empty<string>();

            if (fbxFiles.Length == 0)
            {
                EditorUtility.DisplayDialog("Extract Operator Textures",
                    $"Aucun .fbx dans {RiggedDir}.", "OK");
                return;
            }

            // Choisit le shader selon le pipeline ACTIF. Si aucun SRP n'est assigné dans
            // Graphics/Quality Settings, on est en Built-in RP → les shaders URP rendent
            // MAGENTA. On prend alors Standard (built-in), comme les murs de la scène.
            var srpActive = UnityEngine.Rendering.GraphicsSettings.defaultRenderPipeline != null
                         || UnityEngine.Rendering.GraphicsSettings.currentRenderPipeline != null;
            var bodyShader = srpActive ? Shader.Find("Universal Render Pipeline/Lit") : null;
            if (bodyShader == null) bodyShader = Shader.Find("Standard");
            if (bodyShader == null) bodyShader = Shader.Find("Universal Render Pipeline/Lit");
            if (bodyShader == null)
            {
                EditorUtility.DisplayDialog("Extract Operator Textures",
                    "Aucun shader Standard ni URP/Lit trouvé.", "OK");
                return;
            }
            var urpLit = bodyShader;   // alias conservé pour le reste de la méthode
            Debug.Log($"[RocketPi] Pipeline actif: {(srpActive ? "SRP/URP" : "Built-in")} → shader matériaux: {bodyShader.name}");

            var report = new List<string>();

            foreach (var raw in fbxFiles)
            {
                var path   = raw.Replace('\\', '/');
                var opName = Path.GetFileNameWithoutExtension(path);
                var importer = AssetImporter.GetAtPath(path) as ModelImporter;
                if (importer == null) continue;

                // 1. Extrait les textures embarquées DANS UN SOUS-DOSSIER PAR PERSO
                //    (évite la contamination croisée : chaque perso ne voit QUE ses textures).
                var charTexDir = $"{texDir}/{opName}";
                EnsureFolder(charTexDir);
                importer.materialImportMode = ModelImporterMaterialImportMode.ImportStandard;
                importer.ExtractTextures(charTexDir);
                AssetDatabase.Refresh();
                importer.SaveAndReimport();

                // 2. Textures de CE personnage uniquement.
                var allTex = Directory.GetFiles(charTexDir, "*.png")
                    .Concat(Directory.GetFiles(charTexDir, "*.jpg"))
                    .Concat(Directory.GetFiles(charTexDir, "*.tga"))
                    .Select(p => p.Replace('\\', '/')).ToList();

                // 2b. IDEMPOTENCE : si un run précédent a déjà remappé les matériaux vers
                //     l'externe, le FBX n'a plus de matériaux embarqués → on retire ces
                //     remaps pour les régénérer et pouvoir les re-binder proprement.
                foreach (var kvp in importer.GetExternalObjectMap())
                    if (kvp.Key.type == typeof(Material))
                        importer.RemoveRemap(kvp.Key);
                importer.SaveAndReimport();

                // 3. Construit un matériau URP/Lit par matériau embarqué du FBX et le remappe.
                var embeddedMats = AssetDatabase.LoadAllAssetsAtPath(path).OfType<Material>().ToList();
                int boundForThis = 0;
                var urpByName = new Dictionary<string, Material>();   // nom embarqué → URP mat

                foreach (var src in embeddedMats)
                {
                    var matPath = $"{matDir}/{opName}_{SanitizeName(src.name)}.mat";
                    var mat = AssetDatabase.LoadAssetAtPath<Material>(matPath);
                    if (mat == null)
                    {
                        mat = new Material(urpLit);
                        AssetDatabase.CreateAsset(mat, matPath);
                    }
                    else mat.shader = urpLit;

                    // Diffuse : cherche une texture dont le nom matche le matériau, sinon
                    // la première *_Diffuse du sous-dossier de CE perso (pas de contamination).
                    var diffuse = FindTexture(allTex, src.name, "Diffuse", "Albedo", "BaseColor", "_D")
                                  ?? FindFirstBySuffix(allTex, "Diffuse", "Albedo", "BaseColor");
                    var normal  = FindTexture(allTex, src.name, "Normal", "_N")
                                  ?? FindFirstBySuffix(allTex, "Normal");

                    if (diffuse != null)
                    {
                        // Bind les deux conventions : _MainTex/_Color (Standard built-in)
                        // ET _BaseMap/_BaseColor (URP), pour marcher quel que soit le pipeline.
                        if (mat.HasProperty("_MainTex"))   mat.SetTexture("_MainTex", diffuse);
                        if (mat.HasProperty("_BaseMap"))   mat.SetTexture("_BaseMap", diffuse);
                        if (mat.HasProperty("_Color"))     mat.SetColor("_Color", Color.white);
                        if (mat.HasProperty("_BaseColor")) mat.SetColor("_BaseColor", Color.white);
                        boundForThis++;
                    }
                    if (normal != null)
                    {
                        ForceNormalMap(normal);
                        if (mat.HasProperty("_BumpMap")) mat.SetTexture("_BumpMap", normal);
                        mat.EnableKeyword("_NORMALMAP");
                    }
                    EditorUtility.SetDirty(mat);

                    // Remappe le matériau embarqué du FBX vers notre matériau URP.
                    importer.AddRemap(new AssetImporter.SourceAssetIdentifier(src), mat);
                    urpByName[src.name] = mat;
                }

                importer.SaveAndReimport();

                // 4. CRUCIAL : assigne directement les matériaux URP sur les renderers du
                //    body prefab. Le remap FBX seul ne suffit pas — les renderers du prefab
                //    peuvent garder les matériaux Standard embarqués (magenta en URP).
                int slotsFixed = AssignUrpMaterialsToBodyPrefab(opName, urpByName, matDir);

                report.Add($"{opName}: {embeddedMats.Count} mat(s), {boundForThis} diffuse, {slotsFixed} slot(s) prefab.");
            }

            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();

            Debug.Log("[RocketPi] Extract Operator Textures :\n" + string.Join("\n", report) +
                      "\nLes prefabs body référencent les matériaux du FBX → couleurs appliquées sans rebuild.");
        }

        /// <summary>
        /// Assigne les matériaux URP directement sur les renderers du body prefab
        /// (par correspondance de nom de slot). Retourne le nb de slots corrigés.
        /// </summary>
        private static int AssignUrpMaterialsToBodyPrefab(string opName, Dictionary<string, Material> urpByName, string matDir)
        {
            var prefabPath = $"{BodyPrefabsDir}/{opName}Body.prefab";
            if (!File.Exists(prefabPath)) return 0;

            var root = PrefabUtility.LoadPrefabContents(prefabPath);
            int fixedSlots = 0;
            foreach (var r in root.GetComponentsInChildren<Renderer>(true))
            {
                var slots = r.sharedMaterials;
                for (var i = 0; i < slots.Length; i++)
                {
                    // Nom du slot courant (sans suffixe " (Instance)").
                    var slotName = slots[i] != null ? slots[i].name.Replace(" (Instance)", "") : "";

                    Material target = null;
                    // a) match direct sur le nom embarqué d'origine
                    if (!string.IsNullOrEmpty(slotName) && urpByName.TryGetValue(slotName, out var byName))
                        target = byName;
                    // b) sinon par chemin {opName}_{slotName}.mat
                    if (target == null && !string.IsNullOrEmpty(slotName))
                        target = AssetDatabase.LoadAssetAtPath<Material>($"{matDir}/{opName}_{SanitizeName(slotName)}.mat");
                    // c) dernier recours : 1er matériau URP du perso (corps principal)
                    if (target == null && urpByName.Count > 0)
                        target = urpByName.Values.First();

                    if (target != null && slots[i] != target)
                    {
                        slots[i] = target;
                        fixedSlots++;
                    }
                }
                r.sharedMaterials = slots;
            }
            PrefabUtility.SaveAsPrefabAsset(root, prefabPath);
            PrefabUtility.UnloadPrefabContents(root);
            return fixedSlots;
        }

        private static Texture2D FindTexture(List<string> texPaths, string matName, params string[] suffixes)
        {
            // Matche d'abord les textures qui partagent un token avec le nom du matériau.
            foreach (var p in texPaths)
            {
                var n = Path.GetFileNameWithoutExtension(p);
                foreach (var suf in suffixes)
                {
                    if (n.IndexOf(suf, System.StringComparison.OrdinalIgnoreCase) >= 0 &&
                        n.IndexOf(matName, System.StringComparison.OrdinalIgnoreCase) >= 0)
                        return AssetDatabase.LoadAssetAtPath<Texture2D>(p);
                }
            }
            return null;
        }

        private static Texture2D FindFirstBySuffix(List<string> texPaths, params string[] suffixes)
        {
            foreach (var p in texPaths)
            {
                var n = Path.GetFileNameWithoutExtension(p);
                foreach (var suf in suffixes)
                    if (n.IndexOf(suf, System.StringComparison.OrdinalIgnoreCase) >= 0)
                        return AssetDatabase.LoadAssetAtPath<Texture2D>(p);
            }
            return null;
        }

        private static void ForceNormalMap(Texture2D tex)
        {
            var p = AssetDatabase.GetAssetPath(tex);
            if (AssetImporter.GetAtPath(p) is TextureImporter ti && ti.textureType != TextureImporterType.NormalMap)
            {
                ti.textureType = TextureImporterType.NormalMap;
                ti.SaveAndReimport();
            }
        }

        private static string SanitizeName(string s)
        {
            foreach (var c in Path.GetInvalidFileNameChars()) s = s.Replace(c, '_');
            return s.Replace(' ', '_');
        }

        // ─── 6. Diagnose & Repair Operator Avatars ─────────────────────────
        //
        // Vérifie que chaque FBX personnage a un avatar Humanoid VALIDE, et
        // ré-assigne explicitement l'avatar + controller sur chaque body prefab
        // (un avatar invalide = T-pose au repos car les clips ne se retargettent pas).
        // Si un avatar est invalide, force un ré-import Humanoid pour le régénérer.

        [MenuItem("Tools/RocketPi/Diagnose & Repair Operator Avatars")]
        public static void DiagnoseAndRepairAvatars()
        {
            var controller = AssetDatabase.LoadAssetAtPath<AnimatorController>(ControllerPath);
            var fbxFiles = Directory.Exists(RiggedDir)
                ? Directory.GetFiles(RiggedDir, "*.fbx")
                : System.Array.Empty<string>();

            var report = new List<string>();
            int repaired = 0;

            // Cherche un avatar Humanoid VALIDE à utiliser comme donneur (les persos
            // Mixamo partagent le skelette "mixamorig:" → le mapping par nom marche).
            Avatar donor = null;
            string donorName = null;
            foreach (var raw in fbxFiles)
            {
                var p = raw.Replace('\\', '/');
                var a = AssetDatabase.LoadAllAssetsAtPath(p).OfType<Avatar>().FirstOrDefault();
                if (a != null && a.isValid && a.isHuman) { donor = a; donorName = Path.GetFileNameWithoutExtension(p); break; }
            }

            foreach (var raw in fbxFiles)
            {
                var path   = raw.Replace('\\', '/');
                var opName = Path.GetFileNameWithoutExtension(path);
                var avatar = AssetDatabase.LoadAllAssetsAtPath(path).OfType<Avatar>().FirstOrDefault();

                bool valid = avatar != null && avatar.isValid && avatar.isHuman;
                report.Add($"{opName}: avatar={(avatar == null ? "NULL" : avatar.name)} isValid={avatar?.isValid} isHuman={avatar?.isHuman}");

                // Avatar non-Humanoid → l'auto-mapper a échoué (rig asymétrique/incomplet).
                // On copie le mapping d'un perso valide (par nom de bone mixamorig:*).
                // CopyFromOther ne génère PAS d'avatar embarqué : le FBX réutilise celui
                // du donneur. On assigne donc l'avatar DONNEUR au body prefab.
                if (!valid && donor != null && opName != donorName)
                {
                    if (AssetImporter.GetAtPath(path) is ModelImporter imp)
                    {
                        imp.animationType = ModelImporterAnimationType.Human;
                        imp.avatarSetup   = ModelImporterAvatarSetup.CopyFromOther;
                        imp.sourceAvatar  = donor;
                        imp.optimizeGameObjects = false;
                        imp.SaveAndReimport();
                        avatar = donor;   // le retarget se fera via l'avatar du donneur
                        valid  = true;
                        report[^1] += $"  → CopyFromOther({donorName}) : avatar donneur assigné";
                    }
                }

                // Ré-assigne explicitement avatar + controller sur le body prefab.
                var prefabPath = $"{BodyPrefabsDir}/{opName}Body.prefab";
                var prefab = AssetDatabase.LoadAssetAtPath<GameObject>(prefabPath);
                if (prefab != null && avatar != null)
                {
                    var root = PrefabUtility.LoadPrefabContents(prefabPath);
                    // NB : ne PAS utiliser `?? AddComponent` — l'opérateur `??` C# ignore
                    // la surcharge `== null` d'Unity (fake-null) → MissingComponentException.
                    var anim = root.GetComponent<Animator>();
                    if (anim == null) anim = root.AddComponent<Animator>();
                    anim.avatar = avatar;
                    if (controller != null) anim.runtimeAnimatorController = controller;
                    anim.applyRootMotion = false;
                    // Lie l'_animator sérialisé de l'OperatorBody pour éviter tout doute.
                    var ob = root.GetComponent<OperatorBody>();
                    if (ob == null) ob = root.AddComponent<OperatorBody>();
                    var so = new SerializedObject(ob);
                    so.FindProperty("_animator").objectReferenceValue = anim;
                    so.ApplyModifiedPropertiesWithoutUndo();
                    PrefabUtility.SaveAsPrefabAsset(root, prefabPath);
                    PrefabUtility.UnloadPrefabContents(root);
                    repaired++;
                }
            }

            AssetDatabase.SaveAssets();
            // Pas de DisplayDialog (modal) ici : il bloque l'éditeur pour les commandes MCP.
            Debug.Log($"[RocketPi] Avatar diagnostic (body prefabs ré-assignés : {repaired}) :\n"
                      + string.Join("\n", report));
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
