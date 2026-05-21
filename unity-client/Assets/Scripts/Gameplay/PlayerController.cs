// PlayerController.cs — Contrôleur 3rd person du joueur local.
//
// Le joueur :
//   - instancie le BodyPrefab de l'opérateur sur lui-même
//   - bouge avec WASD relatif à la direction caméra (3rd person classique)
//   - tourne le body vers la direction de la caméra quand il bouge
//   - lit la vélocité du CharacterController et l'envoie à OperatorBody (anim driver)
//
// Pas de logique réseau ici. La version Photon Fusion (Phase 5) wrappera ce
// MonoBehaviour dans un NetworkBehaviour avec input replication.

using System;
using Rocketpi.Bridge;
using Rocketpi.Gameplay.Body;
using Rocketpi.Gameplay.CameraControl;
using Rocketpi.Gameplay.Match;
using Rocketpi.Gameplay.Operators;
using Rocketpi.Gameplay.Weapons;
using Rocketpi.RestClient;
using UnityEngine;
using UnityEngine.InputSystem;

namespace Rocketpi.Gameplay
{
    [RequireComponent(typeof(CharacterController))]
    [RequireComponent(typeof(HealthSystem))]
    public class PlayerController : MonoBehaviour
    {
        [Header("Move")]
        [SerializeField] private float _walkSpeed = 5.5f;
        [SerializeField] private float _sprintSpeed = 8.5f;
        [SerializeField] private float _jumpVelocity = 5.5f;
        [SerializeField] private float _gravity = -18f;
        [SerializeField] private float _airControl = 0.55f;
        [SerializeField, Range(1f, 30f)] private float _bodyTurnSpeed = 12f;

        [Header("Operator")]
        [SerializeField] private OperatorData _operator;
        [SerializeField] private Transform _weaponSocket;

        [Header("Refs")]
        [SerializeField] private ThirdPersonCamera _camera;
        [SerializeField] private Transform _bodyAnchor;       // où instancier le BodyPrefab (par défaut = transform)
        [SerializeField] private TrainingMatchManager _match;

        public OperatorData Operator => _operator;
        public HealthSystem Health { get; private set; }
        public WeaponBase   Weapon { get; private set; }
        public OperatorBody Body   { get; private set; }

        public event Action<WeaponBase> OnWeaponChanged;

        private CharacterController _cc;
        private InputAction _moveAction;
        private InputAction _jumpAction;
        private InputAction _sprintAction;
        private InputAction _fireAction;
        private InputAction _altFireAction;
        private InputAction _ability1Action;
        private InputAction _ability2Action;
        private InputAction _ultimateAction;

        private GameObject _bodyInstance;
        private Vector3 _velocity;

        private void Awake()
        {
            _cc = GetComponent<CharacterController>();
            Health = GetComponent<HealthSystem>();
            if (_camera == null) _camera = FindAnyObjectByType<ThirdPersonCamera>();
            if (_match == null)  _match  = FindAnyObjectByType<TrainingMatchManager>();
            if (_bodyAnchor == null) _bodyAnchor = transform;

            _moveAction = new InputAction("Move", binding: "<Gamepad>/leftStick");
            _moveAction.AddCompositeBinding("Dpad")
                .With("Up",    "<Keyboard>/w")
                .With("Down",  "<Keyboard>/s")
                .With("Left",  "<Keyboard>/a")
                .With("Right", "<Keyboard>/d");
            _jumpAction     = new InputAction("Jump",     binding: "<Keyboard>/space");
            _sprintAction   = new InputAction("Sprint",   binding: "<Keyboard>/leftShift");
            _fireAction     = new InputAction("Fire",     binding: "<Mouse>/leftButton");
            _altFireAction  = new InputAction("AltFire",  binding: "<Mouse>/rightButton");
            _ability1Action = new InputAction("Ability1", binding: "<Keyboard>/q");
            _ability2Action = new InputAction("Ability2", binding: "<Keyboard>/e");
            _ultimateAction = new InputAction("Ultimate", binding: "<Keyboard>/r");
        }

        private void OnEnable()
        {
            _moveAction.Enable();
            _jumpAction.Enable();
            _sprintAction.Enable();
            _fireAction.Enable();
            _altFireAction.Enable();
            _ability1Action.Enable();
            _ability2Action.Enable();
            _ultimateAction.Enable();

            ReleaseCursor();

            if (RocketpiBridge.Instance != null)
            {
                RocketpiBridge.Instance.OnSessionStarted += HandleSessionStarted;
                RocketpiBridge.Instance.OnSessionAborted += HandleSessionAborted;
            }
            if (_match != null)
            {
                _match.OnMatchSubmitted += HandleMatchSubmitted;
                _match.OnMatchError     += HandleMatchError;
            }
        }

        private void OnDisable()
        {
            _moveAction.Disable();
            _jumpAction.Disable();
            _sprintAction.Disable();
            _fireAction.Disable();
            _altFireAction.Disable();
            _ability1Action.Disable();
            _ability2Action.Disable();
            _ultimateAction.Disable();

            if (RocketpiBridge.Instance != null)
            {
                RocketpiBridge.Instance.OnSessionStarted -= HandleSessionStarted;
                RocketpiBridge.Instance.OnSessionAborted -= HandleSessionAborted;
            }
            if (_match != null)
            {
                _match.OnMatchSubmitted -= HandleMatchSubmitted;
                _match.OnMatchError     -= HandleMatchError;
            }

            ReleaseCursor();
        }

        private void Start()
        {
            ApplyOperatorStats();
            SpawnOperatorBody();
            EquipOperatorWeapon();
            BindCameraToBody();
        }

        // ── Curseur ────────────────────────────────────────────────────────

        private static void LockCursor()
        {
            Cursor.lockState = CursorLockMode.Locked;
            Cursor.visible   = false;
        }

        private static void ReleaseCursor()
        {
            Cursor.lockState = CursorLockMode.None;
            Cursor.visible   = true;
        }

        private void HandleSessionStarted(SessionPayload _) => LockCursor();
        private void HandleSessionAborted()                  => ReleaseCursor();
        private void HandleMatchSubmitted(MatchResultResponse _) => ReleaseCursor();
        private void HandleMatchError(string _)                  => ReleaseCursor();

        private void Update()
        {
            if (Health.IsDead) return;

            var matchActive = Cursor.lockState == CursorLockMode.Locked;
            if (matchActive) HandleFire();
            HandleMove();
        }

        // ── Operator / Body / Weapon ───────────────────────────────────────

        public void SetOperator(OperatorData op)
        {
            _operator = op;
            ApplyOperatorStats();
            SpawnOperatorBody();
            EquipOperatorWeapon();
            BindCameraToBody();
        }

        private void ApplyOperatorStats()
        {
            if (_operator == null) return;
            Health.SetMaxHealth(_operator.BaseHp);
            _walkSpeed   = _operator.WalkSpeed;
            _sprintSpeed = _operator.SprintSpeed;

            // Resize du CC pour matcher la silhouette du body
            _cc.height = _operator.BodyHeight;
            _cc.radius = _operator.BodyRadius;
            _cc.center = new Vector3(0f, _operator.BodyHeight * 0.5f, 0f);
        }

        private void SpawnOperatorBody()
        {
            if (_operator == null) return;

            if (_bodyInstance != null)
            {
                Destroy(_bodyInstance);
                _bodyInstance = null;
                Body = null;
            }

            if (_operator.BodyPrefab == null)
            {
                Debug.LogWarning($"[PlayerController] BodyPrefab manquant sur {_operator.name} — joueur restera invisible (capsule logique).");
                return;
            }

            _bodyInstance = Instantiate(_operator.BodyPrefab, _bodyAnchor);
            _bodyInstance.transform.localPosition = Vector3.zero;
            _bodyInstance.transform.localRotation = Quaternion.identity;
            Body = _bodyInstance.GetComponent<OperatorBody>() ?? _bodyInstance.GetComponentInChildren<OperatorBody>();
            Body?.Configure(_walkSpeed, _sprintSpeed);
        }

        private void EquipOperatorWeapon()
        {
            if (_operator == null || _operator.WeaponPrefab == null || _weaponSocket == null) return;

            if (Weapon != null) Destroy(Weapon.gameObject);

            var instance = Instantiate(_operator.WeaponPrefab, _weaponSocket);
            instance.transform.localPosition = Vector3.zero;
            instance.transform.localRotation = Quaternion.identity;
            Weapon = instance.GetComponent<WeaponBase>();
            Weapon?.Initialize(this);
            OnWeaponChanged?.Invoke(Weapon);
        }

        private void BindCameraToBody()
        {
            if (_camera == null || _operator == null) return;
            _camera.SetTarget(transform, _operator.CameraShoulderHeight);
        }

        // ── Input → mouvement 3rd person ───────────────────────────────────

        private void HandleMove()
        {
            var input = _moveAction.ReadValue<Vector2>();

            // Direction caméra projetée sur le plan horizontal
            var camYaw = _camera != null ? _camera.Yaw : transform.eulerAngles.y;
            var camRot = Quaternion.Euler(0f, camYaw, 0f);
            var wishDir = camRot * new Vector3(input.x, 0f, input.y);

            var sprint = _sprintAction.IsPressed() && input.y > 0.1f;
            var speed = sprint ? _sprintSpeed : _walkSpeed;
            var groundControl = _cc.isGrounded ? 1f : _airControl;

            var horizontal = wishDir * (speed * groundControl);
            _velocity.x = horizontal.x;
            _velocity.z = horizontal.z;

            if (_cc.isGrounded)
            {
                if (_velocity.y < 0f) _velocity.y = -2f;
                if (_jumpAction.WasPressedThisFrame())
                    _velocity.y = _jumpVelocity;
            }
            else
            {
                _velocity.y += _gravity * Time.deltaTime;
            }

            _cc.Move(_velocity * Time.deltaTime);

            // Tourne le perso vers la direction de la caméra UNIQUEMENT s'il bouge
            if (wishDir.sqrMagnitude > 0.001f)
            {
                var look = Quaternion.LookRotation(new Vector3(wishDir.x, 0f, wishDir.z));
                transform.rotation = Quaternion.Slerp(transform.rotation, look, Time.deltaTime * _bodyTurnSpeed);
            }

            // Anim driver
            if (Body != null)
            {
                var worldVel = new Vector3(_velocity.x, _velocity.y, _velocity.z);
                Body.DriveLocomotion(worldVel, _cc.isGrounded, sprint);
            }
        }

        private void HandleFire()
        {
            if (Weapon == null) return;
            if (_fireAction.IsPressed())     Weapon.OnFireHeld();
            if (_fireAction.WasReleasedThisFrame()) Weapon.OnFireReleased();
            if (_altFireAction.WasPressedThisFrame()) Weapon.OnAltFire();
        }
    }
}
