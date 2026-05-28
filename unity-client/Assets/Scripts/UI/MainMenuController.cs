// MainMenuController.cs — Écran d'accueil affiché tant qu'aucune session
// n'est démarrée. Le bouton "Lancer training" déclenche le call serveur
// /api/unity/session/start, puis envoie OnSessionStart au RocketpiBridge.

using System.Collections;
using Rocketpi.Bridge;
using Rocketpi.RestClient;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.UI
{
    public class MainMenuController : MonoBehaviour
    {
        [Header("UI")]
        [SerializeField] private GameObject _root;
        [SerializeField] private Button     _startTrainingButton;
        [SerializeField] private TMP_Text   _statusLabel;

        private bool _isRequesting;
        private bool _autoStarted;

        private void Start()
        {
            // L'écran d'accueil est désormais juste un loader transparent : pas de fenêtre
            // intermédiaire à cliquer. Dès que la config arrive du bridge, on lance le
            // training automatiquement.
            Hide();

            if (_startTrainingButton != null)
                _startTrainingButton.onClick.AddListener(OnStartTrainingClicked);

            if (RocketpiBridge.Instance != null)
            {
                RocketpiBridge.Instance.OnConfigReceived += HandleConfigReceived;
                RocketpiBridge.Instance.OnSessionStarted += HandleSessionStarted;
            }

            // Si la config est déjà là (cas Editor mock ou hot reload), démarre tout de suite.
            if (Rocketpi.RestClient.RocketpiApiClient.Instance.IsConfigured) AutoStart();
        }

        private void OnDestroy()
        {
            if (_startTrainingButton != null)
                _startTrainingButton.onClick.RemoveListener(OnStartTrainingClicked);
            if (RocketpiBridge.Instance != null)
            {
                RocketpiBridge.Instance.OnConfigReceived -= HandleConfigReceived;
                RocketpiBridge.Instance.OnSessionStarted -= HandleSessionStarted;
            }
        }

        private void HandleConfigReceived(ConfigPayload _) => AutoStart();
        private void HandleSessionStarted(SessionPayload _) => Hide();

        // Démarre la session training automatiquement, sans clic utilisateur.
        private void AutoStart()
        {
            if (_autoStarted || _isRequesting) return;
            _autoStarted = true;
            StartCoroutine(RequestTrainingSession());
        }

        private void UpdateButtonState()
        {
            var ready = RocketpiApiClient.Instance.IsConfigured;
            if (_startTrainingButton != null) _startTrainingButton.interactable = ready && !_isRequesting;
            if (_statusLabel != null)
                _statusLabel.text = ready ? "Prêt à jouer" : "En attente du serveur...";
        }

        public void Hide()
        {
            if (_root != null) _root.SetActive(false);
        }

        public void Show()
        {
            if (_root != null) _root.SetActive(true);
            UpdateButtonState();
        }

        // ── Bouton "Lancer training" ───────────────────────────────────────

        private void OnStartTrainingClicked()
        {
            if (_isRequesting) return;
            StartCoroutine(RequestTrainingSession());
        }

        private IEnumerator RequestTrainingSession()
        {
            _isRequesting = true;
            UpdateButtonState();
            if (_statusLabel != null) _statusLabel.text = "Création de la session…";

            var req = new SessionStartRequest
            {
                Mode      = "training",
                RankType  = "casual",
                OperatorUsedId = null,
            };

            yield return RocketpiApiClient.Instance.StartSession(req,
                onSuccess: resp =>
                {
                    // Pousse directement vers le bridge comme si JS avait appelé OnSessionStart.
                    // En production réelle (avec hand-off Inertia), c'est React qui ferait
                    // l'appel /api/unity/session/start puis SendMessage. Ici on accélère le
                    // flow pour permettre au joueur de tester depuis l'éditeur Unity.
                    var payload = new SessionPayload
                    {
                        sessionToken    = resp.SessionToken,
                        mode            = "training",
                        rankType        = "casual",
                        operatorUsedId  = -1,
                    };
                    var json = JsonUtility.ToJson(payload);
                    RocketpiBridge.Instance?.OnSessionStart(json);
                },
                onError: err =>
                {
                    if (_statusLabel != null) _statusLabel.text = $"Erreur : {err}";
                    _isRequesting = false;
                    UpdateButtonState();
                }
            );

            _isRequesting = false;
        }
    }
}
