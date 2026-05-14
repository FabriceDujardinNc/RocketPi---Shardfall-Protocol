// BuildWebGL.cs — Script Editor pour builder le client en WebGL vers ../public/unity/.
//
// Tools > RocketPi > Build WebGL (dev)
// Tools > RocketPi > Build WebGL (production)
// Ou en CLI : Unity -batchmode -executeMethod Rocketpi.Editor.BuildWebGL.Build -quit

#if UNITY_EDITOR
using System;
using System.IO;
using UnityEditor;
using UnityEditor.Build.Reporting;
using UnityEngine;

namespace Rocketpi.Editor
{
    public static class BuildWebGL
    {
        // Cible du build : dossier public Laravel servi statiquement
        private const string LaravelPublicUnityPath = "../public/unity";

        [MenuItem("Tools/RocketPi/Build WebGL (dev)")]
        public static void BuildDev() => Run(developmentBuild: true);

        [MenuItem("Tools/RocketPi/Build WebGL (production)")]
        public static void BuildProd() => Run(developmentBuild: false);

        /// <summary>
        /// Entrée CLI utilisée par -executeMethod. Détecte le flag -developmentBuild
        /// dans les args pour basculer entre dev et prod.
        /// </summary>
        public static void Build()
        {
            var devBuild = Array.IndexOf(Environment.GetCommandLineArgs(), "-developmentBuild") >= 0;
            Run(devBuild);
        }

        private static void Run(bool developmentBuild)
        {
            var outputPath = Path.GetFullPath(LaravelPublicUnityPath);
            if (!Directory.Exists(outputPath))
                Directory.CreateDirectory(outputPath);

            // Scènes incluses : EditorBuildSettings (file > build settings) doit lister
            // au moins Bootstrap. On laisse Unity remonter une erreur claire si vide.
            var scenes = Array.ConvertAll(
                Array.FindAll(EditorBuildSettings.scenes, s => s.enabled),
                s => s.path
            );

            var options = new BuildPlayerOptions
            {
                scenes = scenes,
                locationPathName = outputPath,
                target = BuildTarget.WebGL,
                options = developmentBuild ? BuildOptions.Development : BuildOptions.None,
            };

            Debug.Log($"[RocketPi] Building WebGL ({(developmentBuild ? "dev" : "prod")}) → {outputPath}");

            var report = BuildPipeline.BuildPlayer(options);
            var summary = report.summary;

            if (summary.result == BuildResult.Succeeded)
            {
                WriteManifest(outputPath, summary);
                Debug.Log($"[RocketPi] Build OK : {summary.totalSize / 1024 / 1024} MB, {summary.totalTime}");
            }
            else
            {
                Debug.LogError($"[RocketPi] Build FAILED : {summary.result}");
                if (Application.isBatchMode)
                    EditorApplication.Exit(1);
            }
        }

        /// <summary>
        /// Génère public/unity/manifest.json consommé par UnityCanvas.tsx pour
        /// le cache-busting et la résolution des URLs des artefacts WebGL.
        /// Scan le dossier Build/ pour récupérer les vrais noms de fichiers
        /// (Unity peut nommer en "{ProductName}.loader.js" ou "unity.loader.js"
        /// selon les versions / Player Settings).
        /// </summary>
        private static void WriteManifest(string outputPath, BuildSummary summary)
        {
            var buildDir = Path.Combine(outputPath, "Build");
            string loader = "Build/Build.loader.js";
            string data = "Build/Build.data";
            string framework = "Build/Build.framework.js";
            string code = "Build/Build.wasm";

            if (Directory.Exists(buildDir))
            {
                foreach (var file in Directory.GetFiles(buildDir))
                {
                    var name = Path.GetFileName(file);
                    if (name.EndsWith(".loader.js"))      loader    = $"Build/{name}";
                    else if (name.EndsWith(".framework.js") || name.EndsWith(".framework.js.unityweb"))
                                                          framework = $"Build/{name}";
                    else if (name.EndsWith(".wasm") || name.EndsWith(".wasm.unityweb"))
                                                          code      = $"Build/{name}";
                    else if (name.EndsWith(".data") || name.EndsWith(".data.unityweb"))
                                                          data      = $"Build/{name}";
                }
            }

            // JsonUtility ne sait pas sérialiser des anonymous types → on construit la string manuellement.
            // Ordre indenté pour rester lisible côté ops.
            var guid = summary.guid.ToString();
            var version = Application.version;
            var builtAt = DateTime.UtcNow.ToString("o");
            var sizeBytes = (long)summary.totalSize;
            var isDev = (summary.options & BuildOptions.Development) != 0 ? "true" : "false";

            var json =
                "{\n" +
                $"  \"version\": \"{version}\",\n" +
                $"  \"buildGuid\": \"{guid}\",\n" +
                $"  \"builtAt\": \"{builtAt}\",\n" +
                $"  \"sizeBytes\": {sizeBytes},\n" +
                $"  \"isDevelopmentBuild\": {isDev},\n" +
                "  \"urls\": {\n" +
                $"    \"loader\": \"{loader}\",\n" +
                $"    \"data\": \"{data}\",\n" +
                $"    \"framework\": \"{framework}\",\n" +
                $"    \"code\": \"{code}\"\n" +
                "  }\n" +
                "}\n";

            File.WriteAllText(Path.Combine(outputPath, "manifest.json"), json);
        }
    }
}
#endif
