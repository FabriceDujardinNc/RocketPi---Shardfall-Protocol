// MatchSummaryController.cs — Écran de fin de match. Affiche le score, kills,
// le delta de rang (renvoyé par le serveur) et propose de rejouer ou quitter.

using Rocketpi.Bridge;
using Rocketpi.Gameplay.Match;
using Rocketpi.RestClient;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.UI
{
    public class MatchSummaryController : MonoBehaviour
    {
        [Header("Refs scène")]
        [SerializeField] private TrainingMatchManager _match;

        [Header("UI")]
        [SerializeField] private GameObject _root;
        [SerializeField] private TMP_Text   _scoreLabel;
        [SerializeField] private TMP_Text   _killsLabel;
        [SerializeField] private TMP_Text   _deltaLabel;
        [SerializeField] private TMP_Text   _tierLabel;
        [SerializeField] private TMP_Text   _errorLabel;
        [SerializeField] private Button     _replayButton;
        [SerializeField] private Button     _quitButton;
        [SerializeField] private MainMenuController _mainMenu;

        private void Start()
        {
            if (_root != null) _root.SetActive(false);
            if (_match != null)
            {
                _match.OnMatchSubmitted += HandleSubmitted;
                _match.OnMatchError     += HandleError;
            }
            if (_replayButton != null) _replayButton.onClick.AddListener(OnReplayClicked);
            if (_quitButton   != null) _quitButton.onClick.AddListener(OnQuitClicked);
        }

        private void OnDestroy()
        {
            if (_match != null)
            {
                _match.OnMatchSubmitted -= HandleSubmitted;
                _match.OnMatchError     -= HandleError;
            }
            if (_replayButton != null) _replayButton.onClick.RemoveListener(OnReplayClicked);
            if (_quitButton   != null) _quitButton.onClick.RemoveListener(OnQuitClicked);
        }

        private void HandleSubmitted(MatchResultResponse resp)
        {
            if (_root != null) _root.SetActive(true);
            if (_scoreLabel != null && _match != null) _scoreLabel.text = _match.CurrentScore.ToString("N0");
            if (_killsLabel != null && _match != null) _killsLabel.text = _match.CurrentKills.ToString();
            if (_deltaLabel != null)
            {
                var d = resp.RankPointsDelta;
                _deltaLabel.text = d > 0 ? $"+{d}" : d.ToString();
            }
            if (_tierLabel != null) _tierLabel.text = resp.TierAfter ?? "—";
            if (_errorLabel != null) _errorLabel.text = string.Empty;
        }

        private void HandleError(string err)
        {
            if (_root != null) _root.SetActive(true);
            if (_errorLabel != null) _errorLabel.text = err;
        }

        private void OnReplayClicked()
        {
            if (_root != null) _root.SetActive(false);
            _mainMenu?.Show();
        }

        private void OnQuitClicked()
        {
            RocketpiBridge.Instance?.RequestReload();
        }
    }
}
