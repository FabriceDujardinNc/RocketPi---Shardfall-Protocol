// EditorMockSession.cs — Mock backend pour démarrer un match en éditeur Unity
// sans serveur Laravel. Désactivé hors mode éditeur (test : Application.isEditor).
//
// Attache ce script à un GameObject dans la scène Training (ex. "MockBackend").
// Au Start, si activé, il pousse un SessionPayload mock vers RocketpiBridge
// pour déclencher OnSessionStarted (= début du match Training).

using System.Collections;
using UnityEngine;

namespace Rocketpi.Bridge
{
    public class EditorMockSession : MonoBehaviour
    {
        [Header("Auto-démarrage en éditeur")]
        [Tooltip("Déclenche automatiquement une session mock après le délai si on est en éditeur.")]
        [SerializeField] private bool _autoStartInEditor = true;
        [SerializeField] private float _delaySeconds = 1.5f;

        [Header("Payload")]
        [SerializeField] private string _sessionToken = "editor-mock-token";
        [SerializeField] private string _mode = "training";
        [SerializeField] private string _rankType = "casual";

        private void Awake()
        {
            if (!Application.isEditor) return;
            EnsureBridge();
        }

        private void Start()
        {
            if (!Application.isEditor) return;
            PushMockConfig();
            if (!_autoStartInEditor) return;
            StartCoroutine(MockSessionAfterDelay());
        }

        /// <summary>Pousse un ConfigPayload mock pour que RocketpiApiClient.IsConfigured = true
        /// et que MainMenuController active le bouton "Lancer training".</summary>
        private static void PushMockConfig()
        {
            if (RocketpiBridge.Instance == null) return;
            var cfg = new ConfigPayload
            {
                apiBaseUrl  = "http://localhost",
                apiToken    = "editor-mock-bearer",
                userId      = 0,
                locale      = "fr",
                photonAppId = string.Empty,
            };
            RocketpiBridge.Instance.OnConfig(JsonUtility.ToJson(cfg));
        }

        /// <summary>Si on lance Training tout seul sans Bootstrap, instancie un RocketpiBridge mock
        /// pour que MainMenu et TrainingMatchManager puissent recevoir leurs events.</summary>
        private static void EnsureBridge()
        {
            if (RocketpiBridge.Instance != null) return;
            var go = new GameObject("RocketpiBridge");
            go.AddComponent<RocketpiBridge>();
        }

        private IEnumerator MockSessionAfterDelay()
        {
            yield return new WaitForSeconds(_delaySeconds);
            FireMockSession();
        }

        /// <summary>Appelable depuis un UnityEvent (bouton "Lancer training") pour démarrer manuellement.</summary>
        public void StartMockSession()
        {
            FireMockSession();
        }

        private void FireMockSession()
        {
            if (RocketpiBridge.Instance == null)
            {
                Debug.LogWarning("[EditorMockSession] RocketpiBridge.Instance is null, can't fire mock session.");
                return;
            }
            var payload = new SessionPayload
            {
                sessionToken    = _sessionToken,
                mode            = _mode,
                rankType        = _rankType,
                operatorUsedId  = -1,
            };
            var json = JsonUtility.ToJson(payload);
            RocketpiBridge.Instance.OnSessionStart(json);
        }
    }
}
