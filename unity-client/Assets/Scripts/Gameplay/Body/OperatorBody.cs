// OperatorBody.cs — Pont entre la locomotion (CharacterController / NavMeshAgent)
// et l'Animator du mesh skinné de l'opérateur.
//
// Posé sur la racine du BodyPrefab (cf. OperatorData.BodyPrefab). Le composant :
//  - lit la vélocité 2D (XZ) du parent ou d'un fournisseur externe
//  - pousse les paramètres animator standards : Speed (0..1), IsGrounded, IsSprinting, Vertical
//  - peut être driven par le PlayerController (joueur local) ou OperatorNpcController (NPC)
//
// Paramètres Animator attendus (cf. Tools > RocketPi > Build Locomotion Controller) :
//   Speed       : float, normalisé 0=idle / 0.5=walk / 1=sprint
//   IsGrounded  : bool
//   IsSprinting : bool
//   Vertical    : float, vitesse Y pour blend Jump/Fall (optionnel)
//   Die         : trigger pour anim de mort

using UnityEngine;

namespace Rocketpi.Gameplay.Body
{
    [DisallowMultipleComponent]
    public class OperatorBody : MonoBehaviour
    {
        [Header("Refs")]
        [Tooltip("Animator du mesh (souvent sur le même GO que le SkinnedMeshRenderer).")]
        [SerializeField] private Animator _animator;

        [Header("Lissage")]
        [Tooltip("Plus grand = plus de lag mais smoother. Recommandé 0.08-0.15.")]
        [SerializeField] private float _speedDamp = 0.10f;

        [Header("Vitesses référence")]
        [Tooltip("Vitesse correspondant à Speed=0.5 dans l'Animator (walk normal).")]
        [SerializeField] private float _walkSpeedRef   = 5.5f;
        [Tooltip("Vitesse correspondant à Speed=1.0 (sprint).")]
        [SerializeField] private float _sprintSpeedRef = 8.5f;

        private static readonly int HashSpeed       = Animator.StringToHash("Speed");
        private static readonly int HashIsGrounded  = Animator.StringToHash("IsGrounded");
        private static readonly int HashIsSprinting = Animator.StringToHash("IsSprinting");
        private static readonly int HashVertical    = Animator.StringToHash("Vertical");
        private static readonly int HashDie         = Animator.StringToHash("Die");

        private Vector3 _externalVelocity;
        private bool    _externalGrounded = true;
        private bool    _externalSprint;
        private bool    _useExternal;

        private void Awake()
        {
            if (_animator == null) _animator = GetComponentInChildren<Animator>();
        }

        /// <summary>Configure les vitesses référence depuis l'OperatorData.</summary>
        public void Configure(float walkSpeed, float sprintSpeed)
        {
            _walkSpeedRef   = walkSpeed;
            _sprintSpeedRef = sprintSpeed;
        }

        /// <summary>
        /// Pousse l'état de locomotion depuis un controller externe (PlayerController ou NPC).
        /// Appel typique chaque Update du parent. <paramref name="velocity"/> est en monde, pas local.
        /// </summary>
        public void DriveLocomotion(Vector3 velocity, bool isGrounded, bool isSprinting)
        {
            _externalVelocity = velocity;
            _externalGrounded = isGrounded;
            _externalSprint   = isSprinting;
            _useExternal      = true;
        }

        public void TriggerDeath()
        {
            if (_animator != null) _animator.SetTrigger(HashDie);
        }

        /// <summary>Réinitialise l'Animator (sort de l'état mort) pour un respawn.</summary>
        public void Revive()
        {
            if (_animator == null) return;
            _animator.Rebind();    // remet l'Animator à son état par défaut (Locomotion)
            _animator.Update(0f);
        }

        private static readonly int HashFire = Animator.StringToHash("Fire");

        public void TriggerFire()
        {
            if (_animator != null) _animator.SetTrigger(HashFire);
        }

        private void Update()
        {
            if (_animator == null) return;

            var v = _useExternal ? _externalVelocity : Vector3.zero;
            var horizontal = new Vector2(v.x, v.z).magnitude;

            // Normalisation : 0 → idle, 0.5 → walk, 1 → sprint.
            // Au-delà du sprint on clamp à 1 pour ne pas casser le Blend Tree.
            float normalized;
            if (horizontal < 0.05f) normalized = 0f;
            else if (horizontal <= _walkSpeedRef)
                normalized = Mathf.InverseLerp(0f, _walkSpeedRef, horizontal) * 0.5f;
            else
                normalized = 0.5f + Mathf.InverseLerp(_walkSpeedRef, _sprintSpeedRef, horizontal) * 0.5f;

            _animator.SetFloat(HashSpeed, normalized, _speedDamp, Time.deltaTime);
            _animator.SetBool(HashIsGrounded, _externalGrounded);
            _animator.SetBool(HashIsSprinting, _externalSprint);
            _animator.SetFloat(HashVertical, v.y);
        }
    }
}
