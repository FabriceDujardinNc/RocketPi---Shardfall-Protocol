// PlayerController.cs — Contrôleur FPS du joueur local.
//
// Mouvement à la souris + clavier (WASD), saut, look caméra, intégration
// Input System nouveau (Unity.InputSystem). Pas de logique réseau ici —
// pour le mode training c'est purement local. La version réseau (Photon
// Fusion) viendra dans Phase 5 et fera tourner ce contrôleur dans un
// NetworkBehaviour avec input replication.

using System;
using Rocketpi.Gameplay.Weapons;
using Rocketpi.Gameplay.Operators;
using UnityEngine;
using UnityEngine.InputSystem;

namespace Rocketpi.Gameplay
{
    [RequireComponent(typeof(CharacterController))]
    [RequireComponent(typeof(HealthSystem))]
    public class PlayerController : MonoBehaviour
    {
        [Header("Look")]
        [SerializeField] private Camera _cameraOverride;
        [SerializeField] private float _lookSensitivity = 0.12f;
        [SerializeField] private float _pitchMin = -85f;
        [SerializeField] private float _pitchMax = 85f;

        [Header("Move")]
        [SerializeField] private float _walkSpeed = 5.5f;
        [SerializeField] private float _sprintSpeed = 8.5f;
        [SerializeField] private float _jumpVelocity = 5.5f;
        [SerializeField] private float _gravity = -18f;
        [SerializeField] private float _airControl = 0.55f;

        [Header("Operator")]
        [SerializeField] private OperatorData _operator;
        [SerializeField] private Transform _weaponSocket;

        public OperatorData Operator => _operator;
        public HealthSystem Health { get; private set; }
        public WeaponBase   Weapon { get; private set; }

        public event Action<WeaponBase> OnWeaponChanged;

        private CharacterController _cc;
        private InputAction _moveAction;
        private InputAction _lookAction;
        private InputAction _jumpAction;
        private InputAction _sprintAction;
        private InputAction _fireAction;
        private InputAction _altFireAction;
        private InputAction _ability1Action;
        private InputAction _ability2Action;
        private InputAction _ultimateAction;

        private Vector3 _velocity;
        private float _pitch;

        private void Awake()
        {
            _cc = GetComponent<CharacterController>();
            Health = GetComponent<HealthSystem>();
            if (_cameraOverride == null) _cameraOverride = GetComponentInChildren<Camera>();

            // L'Input System est mappé par défaut sur les bindings standard FPS.
            // Pour un projet réel, créer un InputActionAsset et l'assigner ;
            // ici on crée les actions à la volée pour réduire le setup éditeur.
            _moveAction     = new InputAction("Move",     binding: "<Gamepad>/leftStick");
            _moveAction.AddCompositeBinding("Dpad")
                .With("Up",    "<Keyboard>/w")
                .With("Down",  "<Keyboard>/s")
                .With("Left",  "<Keyboard>/a")
                .With("Right", "<Keyboard>/d");
            _lookAction     = new InputAction("Look",     binding: "<Mouse>/delta");
            _lookAction.AddBinding("<Gamepad>/rightStick");
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
            _lookAction.Enable();
            _jumpAction.Enable();
            _sprintAction.Enable();
            _fireAction.Enable();
            _altFireAction.Enable();
            _ability1Action.Enable();
            _ability2Action.Enable();
            _ultimateAction.Enable();

            // En WebGL, le pointer lock se gère sur clic dans le canvas (cf. UI).
            Cursor.lockState = CursorLockMode.Locked;
            Cursor.visible   = false;
        }

        private void OnDisable()
        {
            _moveAction.Disable();
            _lookAction.Disable();
            _jumpAction.Disable();
            _sprintAction.Disable();
            _fireAction.Disable();
            _altFireAction.Disable();
            _ability1Action.Disable();
            _ability2Action.Disable();
            _ultimateAction.Disable();

            Cursor.lockState = CursorLockMode.None;
            Cursor.visible   = true;
        }

        private void Start()
        {
            ApplyOperatorStats();
            EquipOperatorWeapon();
        }

        private void Update()
        {
            if (Health.IsDead) return;

            HandleLook();
            HandleMove();
            HandleFire();
        }

        // ── Operator / Weapon ──────────────────────────────────────────────

        public void SetOperator(OperatorData op)
        {
            _operator = op;
            ApplyOperatorStats();
            EquipOperatorWeapon();
        }

        private void ApplyOperatorStats()
        {
            if (_operator == null) return;
            Health.SetMaxHealth(_operator.BaseHp);
            _walkSpeed   = _operator.WalkSpeed;
            _sprintSpeed = _operator.SprintSpeed;
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

        // ── Input handlers ─────────────────────────────────────────────────

        private void HandleLook()
        {
            var delta = _lookAction.ReadValue<Vector2>() * _lookSensitivity;
            transform.Rotate(0f, delta.x, 0f, Space.World);

            _pitch = Mathf.Clamp(_pitch - delta.y, _pitchMin, _pitchMax);
            if (_cameraOverride != null)
                _cameraOverride.transform.localRotation = Quaternion.Euler(_pitch, 0f, 0f);
        }

        private void HandleMove()
        {
            var input = _moveAction.ReadValue<Vector2>();
            var wishDir = transform.right * input.x + transform.forward * input.y;
            var speed = _sprintAction.IsPressed() ? _sprintSpeed : _walkSpeed;
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
