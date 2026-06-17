// FirstRunSetup.cs — Configure le projet au premier lancement de Unity Editor.
//
// Détecte un sentinel asset (Assets/Settings/FirstRunDone.asset) ; s'il
// n'existe pas, applique la config par défaut (PlayerSettings WebGL, layers,
// tags, WebGL template) puis pose le sentinel.

#if UNITY_EDITOR
using System.IO;
using UnityEditor;
using UnityEngine;

namespace Rocketpi.Editor
{
    [InitializeOnLoad]
    public static class FirstRunSetup
    {
        private const string SentinelAssetPath = "Assets/Settings/FirstRunDone.asset";
        private const string SettingsDir = "Assets/Settings";

        static FirstRunSetup()
        {
            EditorApplication.delayCall += MaybeRun;
        }

        private static void MaybeRun()
        {
            if (File.Exists(SentinelAssetPath)) return;
            Debug.Log("[RocketPi] First-run setup starting...");

            EnsureFolders();
            ConfigurePlayerSettings();
            ConfigureWebGLBuild();
            CreateSentinel();

            Debug.Log("[RocketPi] First-run setup done. Next steps:\n" +
                      "  1. Tools > RocketPi > Scaffold Roster (creates 8 Operator SO assets)\n" +
                      "  2. Tools > RocketPi > Scaffold Scenes (creates Bootstrap + Gameplay scenes)\n" +
                      "  3. File > Build Settings → add scenes → check WebGL platform\n" +
                      "  4. Tools > RocketPi > Build WebGL (dev)");
        }

        private static void EnsureFolders()
        {
            if (!AssetDatabase.IsValidFolder(SettingsDir))
                AssetDatabase.CreateFolder("Assets", "Settings");
        }

        private static void ConfigurePlayerSettings()
        {
            PlayerSettings.companyName = "RocketPi";
            PlayerSettings.productName = "Shardfall Protocol";
            PlayerSettings.applicationIdentifier = "pro.rocketpi.shardfall";
            PlayerSettings.bundleVersion = "0.1.0";

            // Default colorspace : Linear (URP veut Linear).
            PlayerSettings.colorSpace = ColorSpace.Linear;
        }

        private static void ConfigureWebGLBuild()
        {
            PlayerSettings.WebGL.compressionFormat = WebGLCompressionFormat.Brotli;
            PlayerSettings.WebGL.template = "PROJECT:RocketPi";
            PlayerSettings.WebGL.memorySize = 512; // MB initial heap
            PlayerSettings.WebGL.linkerTarget = WebGLLinkerTarget.Wasm;
            PlayerSettings.WebGL.exceptionSupport = WebGLExceptionSupport.ExplicitlyThrownExceptionsOnly;
        }

        private static void CreateSentinel()
        {
            File.WriteAllText(SentinelAssetPath, "Created by FirstRunSetup");
            AssetDatabase.Refresh();
        }
    }
}
#endif
