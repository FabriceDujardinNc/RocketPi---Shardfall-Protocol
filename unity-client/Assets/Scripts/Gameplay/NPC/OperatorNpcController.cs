// OperatorNpcController.cs — Contrôleur IA d'un opérateur NPC.
//
// Comportement minimal pour la scène Training :
//   1. Patrol : suit les waypoints assignés via NavMeshPatroller.
//   2. Engage : si un joueur entre dans le rayon de vision (cone), met en pause
//      la patrouille, se tourne vers le joueur, "tire" toutes les X secondes
//      (placeholder — pas de dégât réel pour l'instant, juste anim trigger).
//   3. Return : si le joueur sort du rayon ou la distance dépasse, reprend la
//      patrouille.
//
// HealthSystem branché : si le NPC est touché jusqu'à 0 HP, déclenche l'anim
// de mort sur OperatorBody et désactive le NavMeshAgent.
//
// Pas de logique réseau ici. La version PvP réseau (Phase 5) remplacera ce
// comportement par un Photon NetworkBehaviour.

using Rocketpi.Gameplay.Body;
using Rocketpi.Gameplay.Operators;
using UnityEngine;
using UnityEngine.AI;

namespace Rocketpi.Gameplay.NPC
{
    [RequireComponent(typeof(NavMeshAgent))]
    [RequireComponent(typeof(HealthSystem))]
    public class OperatorNpcController : MonoBehaviour
    {
        public enum State { Patrol, Engage, Dead }

        [Header("Identité")]
        [SerializeField] private OperatorData _operator;

        [Header("Body 3D")]
        [Tooltip("Instance du BodyPrefab, ajouté en runtime ou pré-attaché en éditeur.")]
        [SerializeField] private OperatorBody _body;

        [Header("Vision")]
        [Tooltip("Si false, le NPC patrouille en boucle sans jamais engager le joueur " +
                 "(mode cible d'entraînement). Activer pour l'IA de combat.")]
        [SerializeField] private bool _detectionEnabled = false;
        [SerializeField] private float _detectionRadius = 18f;
        [SerializeField] private float _losingRadius    = 24f;
        [Tooltip("Demi-angle du cone de vision (degrés). 90 = vue large, 45 = vue serrée.")]
        [SerializeField, Range(10f, 180f)] private float _detectionHalfAngle = 60f;
        [SerializeField] private LayerMask _playerMask = ~0;
        [SerializeField] private LayerMask _losMask    = ~0;

        [Header("Combat")]
        [SerializeField] private float _attackCooldown = 2.0f;
        [SerializeField] private float _engageStandoffDistance = 8f;
        [SerializeField] private float _faceTurnSpeed = 8f;
        [Tooltip("Points accordés au joueur quand ce NPC est abattu.")]
        [SerializeField] private int _scoreOnKill = 100;

        [Header("Infiltration (mode Où est Charlie)")]
        [Tooltip("Si true, ce NPC est l'IMPOSTEUR (cible) : il marche pour se fondre mais " +
                 "se trahit en COURANT par moments. Le tuer rapporte gros ; tuer un faux " +
                 "opérateur (decoy) fait perdre de la vie au joueur.")]
        [SerializeField] private bool _isImpostor = false;
        private bool  _running;
        private float _nextRunToggle;

        [Header("Respawn")]
        [Tooltip("Si true, le NPC réapparaît à un waypoint aléatoire après sa mort.")]
        [SerializeField] private bool _respawnEnabled = true;
        [SerializeField] private float _respawnDelay = 3f;

        [Header("Impact mur (onde de choc)")]
        [Tooltip("Dégâts de base quand le NPC est projeté contre un mur.")]
        [SerializeField] private int _wallImpactDamageBase = 25;
        [Tooltip("Dégâts additionnels par mètre de vitesse résiduelle à l'impact.")]
        [SerializeField] private float _wallImpactDamagePerMeter = 4f;

        public State Current { get; private set; } = State.Patrol;
        public Transform CurrentTarget { get; private set; }

        private NavMeshAgent     _agent;
        private NavMeshPatroller _patroller;
        private HealthSystem     _health;
        private Rocketpi.Gameplay.Match.TrainingMatchManager _matchManager;
        private float _nextAttackAt;

        private void Awake()
        {
            _agent     = GetComponent<NavMeshAgent>();
            _patroller = GetComponent<NavMeshPatroller>();
            // Errance libre garantie : chaque NPC choisit des destinations aléatoires sur
            // toute l'arène (centre = origine, rayon 22 m) plutôt que de suivre les mêmes
            // waypoints dans le même sens. Forcé ici car les NPCs déjà en scène peuvent
            // avoir _roam=false sérialisé (champ ajouté après leur création).
            _patroller?.SetRoam(true, Vector3.zero, 70f);
            _health    = GetComponent<HealthSystem>();
            if (_body == null) _body = GetComponentInChildren<OperatorBody>();
            // Réf au match manager pour remonter le score à la mort (une fois, au boot).
            _matchManager = FindAnyObjectByType<Rocketpi.Gameplay.Match.TrainingMatchManager>();

            if (_operator != null)
            {
                _agent.speed = _operator.WalkSpeed;
                _body?.Configure(_operator.WalkSpeed, _operator.SprintSpeed);
                _health.SetMaxHealth(_operator.BaseHp);
            }
        }

        private void OnEnable()
        {
            _health.OnDied += HandleDeath;
        }

        private void OnDisable()
        {
            _health.OnDied -= HandleDeath;
        }

        /// <summary>Fige le NPC sur place pendant <paramref name="duration"/> s (EMP / hack).</summary>
        public void Stun(float duration)
        {
            if (Current == State.Dead) return;
            if (_patroller != null) _patroller.enabled = false;
            if (_agent != null && _agent.isOnNavMesh) _agent.isStopped = true;
            CancelInvoke(nameof(EndStun));
            Invoke(nameof(EndStun), duration);
        }

        private void EndStun()
        {
            if (Current == State.Dead) return;
            if (_agent != null && _agent.isOnNavMesh) _agent.isStopped = false;
            if (_patroller != null) { _patroller.enabled = true; _patroller.StartPatrol(); }
        }

        /// <summary>Repousse le NPC loin du point <paramref name="from"/> (onde de choc).
        /// S'il percute un mur sur sa trajectoire, il s'écrase dessus et subit des dégâts.</summary>
        public void ApplyKnockback(Vector3 from, float force)
        {
            if (_agent == null || !_agent.enabled || !_agent.isOnNavMesh) return;
            var dir = transform.position - from;
            dir.y = 0f;
            if (dir.sqrMagnitude < 0.01f)
                dir = new Vector3(Random.value - 0.5f, 0f, Random.value - 0.5f);
            dir.Normalize();

            var origin = transform.position + Vector3.up * 1f;

            // Cherche un mur sur la trajectoire (ignore soi-même + autres entités).
            var hits = Physics.RaycastAll(origin, dir, force, ~0, QueryTriggerInteraction.Ignore);
            System.Array.Sort(hits, (a, b) => a.distance.CompareTo(b.distance));
            foreach (var h in hits)
            {
                if (h.collider.transform == transform || h.collider.transform.IsChildOf(transform)) continue;
                if (h.collider.GetComponentInParent<HealthSystem>() != null) continue; // un autre NPC/joueur, pas un mur

                // Mur percuté : on s'arrête juste devant + dégâts d'impact
                // proportionnels à la vitesse (force restante après le trajet).
                var stop = h.point - dir * 0.5f;
                if (NavMesh.SamplePosition(stop, out var navStop, 3f, NavMesh.AllAreas))
                    _agent.Warp(navStop.position);

                var remaining = Mathf.Max(0f, force - h.distance);
                var impact = Mathf.RoundToInt(_wallImpactDamageBase + remaining * _wallImpactDamagePerMeter);
                _health.TakeDamage(impact);
                return;
            }

            // Pas de mur : projection libre jusqu'à la portée.
            var target = transform.position + dir * force;
            if (NavMesh.SamplePosition(target, out var hit, force + 2f, NavMesh.AllAreas))
                _agent.Warp(hit.position);
        }

        /// <summary>Désigne ce NPC comme imposteur (cible) ou faux opérateur (decoy).</summary>
        public void SetImpostor(bool v) => _isImpostor = v;
        public bool IsImpostor => _isImpostor;

        public void SetOperator(OperatorData op)
        {
            _operator = op;
            if (op == null) return;
            // Lazy init au cas où SetOperator soit appelé depuis l'éditeur (Awake non joué).
            if (_agent  == null) _agent  = GetComponent<NavMeshAgent>();
            if (_health == null) _health = GetComponent<HealthSystem>();
            if (_body   == null) _body   = GetComponentInChildren<OperatorBody>();
            if (_agent  != null) _agent.speed = op.WalkSpeed;
            _body?.Configure(op.WalkSpeed, op.SprintSpeed);
            _health?.SetMaxHealth(op.BaseHp);
        }

        private void Update()
        {
            if (Current == State.Dead) return;

            switch (Current)
            {
                case State.Patrol: TickPatrol(); break;
                case State.Engage: TickEngage(); break;
            }

            // Imposteur : alterne marche (se fondre) et course (se trahir) → c'est le tell
            // que le joueur doit repérer. Les decoys gardent leur vitesse de marche.
            if (_isImpostor && Current == State.Patrol && _operator != null && _agent != null)
            {
                if (Time.time >= _nextRunToggle)
                {
                    _running = !_running;
                    _nextRunToggle = Time.time + (_running ? Random.Range(1.5f, 3.5f) : Random.Range(4f, 9f));
                    _agent.speed = _running ? _operator.SprintSpeed : _operator.WalkSpeed;
                }
            }

            // Pousse la vélocité à l'animator (l'anim Run/Walk suit la vitesse réelle).
            if (_body != null)
            {
                var velocity = _agent.velocity;
                _body.DriveLocomotion(velocity, isGrounded: true, isSprinting: _isImpostor && _running);
            }
        }

        private void TickPatrol()
        {
            if (!_detectionEnabled) return;   // mode cible : patrouille pure, jamais d'engage
            var player = FindPlayerInCone();
            if (player != null)
            {
                EnterEngage(player);
                return;
            }
        }

        private void TickEngage()
        {
            if (CurrentTarget == null) { EnterPatrol(); return; }

            var dist = Vector3.Distance(transform.position, CurrentTarget.position);
            if (dist > _losingRadius)
            {
                EnterPatrol();
                return;
            }

            // Maintient le standoff
            var dir = CurrentTarget.position - transform.position;
            dir.y = 0f;
            var desired = CurrentTarget.position - dir.normalized * _engageStandoffDistance;
            _agent.SetDestination(desired);

            // Tourne vers la cible
            if (dir.sqrMagnitude > 0.001f)
            {
                var look = Quaternion.LookRotation(dir);
                transform.rotation = Quaternion.Slerp(transform.rotation, look, Time.deltaTime * _faceTurnSpeed);
            }

            // Tir cooldownisé (placeholder — pas de dégât réel)
            if (Time.time >= _nextAttackAt && dist <= _engageStandoffDistance + 2f)
            {
                Attack();
                _nextAttackAt = Time.time + _attackCooldown;
            }
        }

        private void EnterEngage(Transform player)
        {
            CurrentTarget = player;
            Current = State.Engage;
            _patroller?.Pause();
        }

        private void EnterPatrol()
        {
            CurrentTarget = null;
            Current = State.Patrol;
            _patroller?.Resume();
        }

        /// <summary>Cherche le joueur dans le cone de vision avec ligne de vue dégagée.</summary>
        private Transform FindPlayerInCone()
        {
            // OverlapSphere puis test angle/raycast
            var hits = Physics.OverlapSphere(transform.position, _detectionRadius, _playerMask, QueryTriggerInteraction.Ignore);
            foreach (var h in hits)
            {
                var to = h.transform.position - transform.position;
                to.y = 0f;
                var angle = Vector3.Angle(transform.forward, to);
                if (angle > _detectionHalfAngle) continue;

                // Ligne de vue
                var origin = transform.position + Vector3.up * 1.5f;
                var target = h.transform.position + Vector3.up * 1.5f;
                if (Physics.Linecast(origin, target, out var blocked, _losMask, QueryTriggerInteraction.Ignore))
                {
                    // Si la ligne de vue est bloquée par autre chose que le player lui-même
                    if (blocked.transform != h.transform) continue;
                }
                return h.transform;
            }
            return null;
        }

        private void Attack()
        {
            // Stub — anim trigger / SFX. La vraie logique (raycast damage) viendra avec
            // une WeaponBase rattachée au NPC, similaire au joueur.
            // Pour l'instant on log juste pour valider le flow.
            #if UNITY_EDITOR
            Debug.DrawLine(transform.position + Vector3.up * 1.5f,
                CurrentTarget.position + Vector3.up * 1.5f,
                Color.red, 0.15f);
            #endif
        }

        private void HandleDeath()
        {
            Current = State.Dead;
            CurrentTarget = null;
            _patroller?.StopPatrol();
            if (_agent.isOnNavMesh) _agent.isStopped = true;
            _body?.TriggerDeath();

            // Mode infiltration prioritaire : l'éliminé est-il l'imposteur (score) ou un
            // faux opérateur (pénalité de vie) ? Sinon, comportement training classique (score).
            if (Rocketpi.Gameplay.Match.HideSeekManager.Instance != null)
                Rocketpi.Gameplay.Match.HideSeekManager.Instance.OnOperatorKilled(_isImpostor);
            else
                _matchManager?.RegisterKill(_scoreOnKill);

            if (_respawnEnabled)
            {
                // On garde l'agent activé (pour pouvoir Warp au respawn), juste stoppé.
                Invoke(nameof(Respawn), _respawnDelay);
            }
            else
            {
                _agent.enabled = false;
            }
        }

        private void Respawn()
        {
            // Nouvelle position : un waypoint aléatoire (réapparaît "plus loin").
            var newPos = _patroller != null
                ? _patroller.RandomWaypointPosition(transform.position)
                : transform.position;

            // Snap sur le NavMesh à la nouvelle position.
            if (_agent != null && _agent.enabled)
            {
                if (NavMesh.SamplePosition(newPos, out var navHit, 5f, NavMesh.AllAreas))
                    _agent.Warp(navHit.position);
                _agent.isStopped = false;
            }

            _health.ResetHealth();        // HP plein → WorldHealthBar se réaffiche
            _body?.Revive();              // sort de l'anim de mort
            // Réinitialise l'état de course (l'imposteur re-marche pour se re-cacher).
            _running = false;
            _nextRunToggle = Time.time + Random.Range(3f, 6f);
            if (_agent != null && _operator != null) _agent.speed = _operator.WalkSpeed;
            Current = State.Patrol;
            _patroller?.StartPatrol();
        }

        private void OnDrawGizmosSelected()
        {
            // Cone de vision
            Gizmos.color = new Color(1f, 0.7f, 0.2f, 0.4f);
            Gizmos.DrawWireSphere(transform.position, _detectionRadius);

            var leftDir  = Quaternion.AngleAxis(-_detectionHalfAngle, Vector3.up) * transform.forward;
            var rightDir = Quaternion.AngleAxis( _detectionHalfAngle, Vector3.up) * transform.forward;
            Gizmos.DrawRay(transform.position, leftDir  * _detectionRadius);
            Gizmos.DrawRay(transform.position, rightDir * _detectionRadius);

            Gizmos.color = new Color(1f, 0.3f, 0.3f, 0.3f);
            Gizmos.DrawWireSphere(transform.position, _losingRadius);
        }
    }
}
