// SceneBootstrapper.cs — Charge automatiquement la scène Training depuis Bootstrap
// au démarrage. À placer sur le GameObject RocketpiBridge dans Bootstrap.unity.

using UnityEngine;
using UnityEngine.SceneManagement;

namespace Rocketpi.Bridge
{
    public class SceneBootstrapper : MonoBehaviour
    {
        [SerializeField] private string _trainingSceneName = "Training";

        private void Start()
        {
            if (SceneManager.GetActiveScene().name != "Bootstrap") return;
            SceneManager.LoadScene(_trainingSceneName, LoadSceneMode.Additive);
        }
    }
}
