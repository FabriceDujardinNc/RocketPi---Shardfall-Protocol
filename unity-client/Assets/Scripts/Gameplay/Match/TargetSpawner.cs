// TargetSpawner.cs — Génère des PracticeTarget à intervalles aléatoires
// dans une zone définie. Auto-register sur TrainingMatchManager si présent.

using Rocketpi.Gameplay.Targets;
using UnityEngine;

namespace Rocketpi.Gameplay.Match
{
    public class TargetSpawner : MonoBehaviour
    {
        [SerializeField] private GameObject _targetPrefab;
        [SerializeField] private Vector3    _spawnAreaSize = new(20f, 0f, 20f);
        [SerializeField] private float      _spawnInterval = 3f;
        [SerializeField] private int        _maxConcurrent = 6;

        private float _nextSpawnAt;
        private int   _alive;
        private TrainingMatchManager _matchManager;

        private void Start()
        {
            _matchManager = FindAnyObjectByType<TrainingMatchManager>();
        }

        private void Update()
        {
            if (_targetPrefab == null) return;
            if (_alive >= _maxConcurrent) return;
            if (Time.time < _nextSpawnAt) return;

            SpawnOne();
            _nextSpawnAt = Time.time + _spawnInterval;
        }

        private void SpawnOne()
        {
            var pos = transform.position + new Vector3(
                Random.Range(-_spawnAreaSize.x * 0.5f, _spawnAreaSize.x * 0.5f),
                Random.Range(0f, _spawnAreaSize.y),
                Random.Range(-_spawnAreaSize.z * 0.5f, _spawnAreaSize.z * 0.5f)
            );

            var go = Instantiate(_targetPrefab, pos, Quaternion.identity);
            if (go.TryGetComponent<PracticeTarget>(out var target))
            {
                _alive++;
                target.OnDestroyed += _ => _alive--;
                _matchManager?.RegisterTarget(target);
            }
        }

        private void OnDrawGizmosSelected()
        {
            Gizmos.color = new Color(0f, 0.8f, 1f, 0.25f);
            Gizmos.DrawWireCube(transform.position, _spawnAreaSize);
        }
    }
}
