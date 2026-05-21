// NavMeshPatroller.cs — Logique de patrouille en boucle entre waypoints.
//
// Composant léger qu'on attache au même GO que le NavMeshAgent. Le manager
// (OperatorNpcController) décide quand activer / désactiver la patrouille
// (ex. couper pendant un combat, reprendre après).
//
// Les waypoints peuvent être assignés via l'inspecteur OU via SetWaypoints(...)
// au runtime (utile pour le SceneScaffolder qui les crée en code).

using System.Collections.Generic;
using UnityEngine;
using UnityEngine.AI;

namespace Rocketpi.Gameplay.NPC
{
    [RequireComponent(typeof(NavMeshAgent))]
    public class NavMeshPatroller : MonoBehaviour
    {
        [Header("Waypoints")]
        [SerializeField] private List<Transform> _waypoints = new();
        [Tooltip("Distance sous laquelle on considère le waypoint atteint.")]
        [SerializeField] private float _arriveTolerance = 0.6f;
        [Tooltip("Temps d'attente sur chaque waypoint avant de partir au suivant.")]
        [SerializeField] private float _waitAtWaypoint = 1.5f;

        [Header("Comportement")]
        [SerializeField] private bool _loop = true;
        [SerializeField] private bool _randomOrder = false;

        public bool IsPatrolling { get; private set; }
        public Vector3 CurrentDestination => _agent != null ? _agent.destination : transform.position;

        private NavMeshAgent _agent;
        private int   _currentIndex = -1;
        private float _waitUntil;
        private bool  _paused;

        private void Awake()
        {
            _agent = GetComponent<NavMeshAgent>();
        }

        private void OnEnable()
        {
            if (_waypoints.Count > 0) StartPatrol();
        }

        public void SetWaypoints(IEnumerable<Transform> wps)
        {
            _waypoints.Clear();
            foreach (var w in wps) if (w != null) _waypoints.Add(w);
            // Ne démarre la patrouille qu'au runtime — en édition, _agent n'est pas
            // initialisé (Awake n'a pas tourné) donc GoNext provoquerait un NRE.
            if (Application.isPlaying && gameObject.activeInHierarchy && _waypoints.Count > 0)
                StartPatrol();
        }

        public void StartPatrol()
        {
            IsPatrolling = true;
            _paused = false;
            _currentIndex = -1;
            GoNext();
        }

        public void StopPatrol()
        {
            IsPatrolling = false;
            if (_agent.isOnNavMesh) _agent.ResetPath();
        }

        /// <summary>Met en pause sans reset (ex. combat). Reprendre via Resume().</summary>
        public void Pause()
        {
            _paused = true;
            if (_agent.isOnNavMesh) _agent.isStopped = true;
        }

        public void Resume()
        {
            _paused = false;
            if (_agent.isOnNavMesh) _agent.isStopped = false;
        }

        private void Update()
        {
            if (!IsPatrolling || _paused || _waypoints.Count == 0) return;
            if (!_agent.isOnNavMesh) return;

            if (_agent.pathPending) return;

            if (_agent.remainingDistance <= _arriveTolerance)
            {
                if (Time.time < _waitUntil) return;
                _waitUntil = Time.time + _waitAtWaypoint;
                GoNext();
            }
        }

        private void GoNext()
        {
            if (_waypoints.Count == 0) return;

            if (_randomOrder)
            {
                _currentIndex = Random.Range(0, _waypoints.Count);
            }
            else
            {
                _currentIndex++;
                if (_currentIndex >= _waypoints.Count)
                {
                    if (_loop) _currentIndex = 0;
                    else { StopPatrol(); return; }
                }
            }

            var wp = _waypoints[_currentIndex];
            if (wp == null) return;
            _agent.SetDestination(wp.position);
        }

        private void OnDrawGizmosSelected()
        {
            if (_waypoints == null || _waypoints.Count == 0) return;
            Gizmos.color = new Color(0.3f, 0.85f, 1f, 0.8f);
            for (var i = 0; i < _waypoints.Count; i++)
            {
                var a = _waypoints[i];
                if (a == null) continue;
                Gizmos.DrawSphere(a.position, 0.25f);
                var b = _waypoints[(i + 1) % _waypoints.Count];
                if (b != null) Gizmos.DrawLine(a.position, b.position);
            }
        }
    }
}
