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
        /// le cache-busting. Format minimal mais extensible.
        /// </summary>
        private static void WriteManifest(string outputPath, BuildSummary summary)
        {
            var manifest = new
            {
                version = Application.version,
                buildGuid = summary.guid.ToString(),
                builtAt = DateTime.UtcNow.ToString("o"),
                sizeBytes = (long)summary.totalSize,
                isDevelopmentBuild = (summary.options & BuildOptions.Development) != 0,
            };
            var json = JsonUtility.ToJson(manifest, prettyPrint: true);
            File.WriteAllText(Path.Combine(outputPath, "manifest.json"), json);
        }
    }
}
#endif
