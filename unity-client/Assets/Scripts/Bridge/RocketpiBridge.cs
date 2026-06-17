// RocketpiBridge.cs — Singleton MonoBehaviour qui sert de pont JS ↔ Unity.
//
// Posé manuellement sur un GameObject nommé EXACTEMENT "RocketpiBridge" dans la
// scène Bootstrap. Le nom est référencé par unityInstance.SendMessage côté JS,
// donc tout changement de nom casse l'intégration. Voir CLAUDE.md.

using System;
using System.Runtime.InteropServices;
using UnityEngine;

namespace Rocketpi.Bridge
{
    public sealed class RocketpiBridge : MonoBehaviour
    {
        // ── Bridge JS importé depuis Assets/Plugins/WebGL/RocketpiBridge.jslib ──
        // Ces extern n'ont d'effet qu'en build WebGL. En Editor / Standalone, on stub.
#if UNITY_WEBGL && !UNITY_EDITOR
        [DllImport("__Internal")] private static extern void RocketpiNotifyReady();
        [DllImport("__Internal")] private static extern void RocketpiSubmitMatchResult(string payloadJson);
        [DllImport("__Internal")] private static extern void RocketpiRequestReload();
        [DllImport("__Internal")] private static extern void RocketpiLog(string level, string message);
#else
        private static void RocketpiNotifyReady() => Debug.Log("[RocketpiBridge] ready (editor stub)");
        private static void RocketpiSubmitMatchResult(string payloadJson) => Debug.Log($"[RocketpiBridge] match result (editor): {payloadJson}");
        private static void RocketpiRequestReload() => Debug.Log("[RocketpiBridge] request reload (editor stub)");
        private static void RocketpiLog(string level, string message) => Debug.Log($"[Rocketpi:{level}] {message}");
#endif

        public static RocketpiBridge Instance { get; private set; }

        public ConfigPayload Config { get; private set; }
        public SessionPayload CurrentSession { get; private set; }

        public event Action<ConfigPayload> OnConfigReceived;
        public event Action<SessionPayload> OnSessionStarted;
        public event Action OnSessionAborted;

        private void Awake()
        {
            if (Instance != null && Instance != this)
            {
                Destroy(gameObject);
                return;
            }
            Instance = this;
            DontDestroyOnLoad(gameObject);
        }

        private void Start()
        {
            // Annonce à React que Unity est prêt à recevoir OnConfig.
            // /!\ Ne JAMAIS notifier ready avant Start (la GameObject Bridge doit être instanciée).
            RocketpiNotifyReady();
        }

        // ─────────────────────────────────────────────────────────────────────
        // Handlers SendMessage (JS → Unity)
        // Signatures imposées par SendMessage : un seul argument string max.
        // ─────────────────────────────────────────────────────────────────────

        /// <summary>
        /// Appelé par React au boot avec la config initiale (API token, locale, etc).
        /// </summary>
        public void OnConfig(string json)
        {
            try
            {
                Config = JsonUtility.FromJson<ConfigPayload>(json);
                Log("info", $"Config reçue (userId={Config.userId}, locale={Config.locale})");
                OnConfigReceived?.Invoke(Config);
            }
            catch (Exception e)
            {
                Log("error", $"OnConfig parse failed: {e.Message}");
            }
        }

        /// <summary>
        /// Appelé par React quand le joueur lance un match. session_token déjà émis serveur.
        /// </summary>
        public void OnSessionStart(string json)
        {
            try
            {
                CurrentSession = JsonUtility.FromJson<SessionPayload>(json);
                Log("info", $"Session démarrée (mode={CurrentSession.mode}, rankType={CurrentSession.rankType})");
                OnSessionStarted?.Invoke(CurrentSession);
            }
            catch (Exception e)
            {
                Log("error", $"OnSessionStart parse failed: {e.Message}");
            }
        }

        /// <summary>
        /// Appelé par React si le joueur ferme la page ou navigue ailleurs.
        /// L'argument est vide ('') car SendMessage exige un string.
        /// </summary>
        public void OnSessionAbort(string _)
        {
            if (CurrentSession == null) return;
            Log("warn", $"Session abandonnée (token={Truncate(CurrentSession.sessionToken, 8)}...)");
            OnSessionAborted?.Invoke();
            CurrentSession = null;
        }

        /// <summary>
        /// Notifications push depuis Laravel (ex. matchmaking found). Format libre.
        /// </summary>
        public void OnReceiveMessage(string json)
        {
            Log("info", $"Message reçu: {Truncate(json, 200)}");
            // À étendre selon les types de messages
        }

        // ─────────────────────────────────────────────────────────────────────
        // API publique pour le gameplay (Unity → JS)
        // ─────────────────────────────────────────────────────────────────────

        /// <summary>
        /// Notifie React que le match est terminé. Le payload est aussi envoyé en parallèle
        /// au serveur via /api/unity/match/result (REST direct depuis le gameplay).
        /// </summary>
        public void SubmitMatchResult(MatchResultPayload payload)
        {
            var json = JsonUtility.ToJson(payload);
            Log("info", $"Submit match result: score={payload.score}, won={payload.won}");
            RocketpiSubmitMatchResult(json);
        }

        /// <summary>
        /// Demande à React de recharger la page (erreur fatale, session expirée…).
        /// </summary>
        public void RequestReload()
        {
            Log("warn", "Request reload");
            RocketpiRequestReload();
        }

        /// <summary>
        /// Logger cross-domain. Level : "debug" | "info" | "warn" | "error".
        /// En prod, les "debug" sont filtrés côté React.
        /// </summary>
        public void Log(string level, string message)
        {
            RocketpiLog(level, message);
        }

        // ─────────────────────────────────────────────────────────────────────

        private static string Truncate(string s, int max)
        {
            if (string.IsNullOrEmpty(s)) return string.Empty;
            return s.Length <= max ? s : s.Substring(0, max);
        }
    }
}
