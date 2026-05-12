// PracticeTarget.cs — Cible d'entraînement avec score à la destruction.
// Émet OnDestroyed quand HP atteint 0, consommé par TrainingMatchManager.

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Targets
{
    [RequireComponent(typeof(HealthSystem))]
    public class PracticeTarget : MonoBehaviour
    {
        [SerializeField] private int   _scoreOnKill = 100;
        [SerializeField] private float _respawnDelay = 1.5f;
        [SerializeField] private bool  _respawnAfterDeath = true;

        public int ScoreOnKill => _scoreOnKill;
        public event Action<PracticeTarget> OnDestroyed;

        private HealthSystem _health;
        private Vector3      _initialPosition;
        private Quaternion   _initialRotation;

        private void Awake()
        {
            _health = GetComponent<HealthSystem>();
            _initialPosition = transform.position;
            _initialRotation = transform.rotation;
            _health.OnDied += HandleDied;
        }

        private void OnDestroy()
        {
            if (_health != null) _health.OnDied -= HandleDied;
        }

        private void HandleDied()
        {
            OnDestroyed?.Invoke(this);
            if (_respawnAfterDeath)
            {
                gameObject.SetActive(false);
                Invoke(nameof(Respawn), _respawnDelay);
            }
            else
            {
                Destroy(gameObject, 0.5f);
            }
        }

        private void Respawn()
        {
            transform.SetPositionAndRotation(_initialPosition, _initialRotation);
            _health.ResetHealth();
            gameObject.SetActive(true);
        }
    }
}
