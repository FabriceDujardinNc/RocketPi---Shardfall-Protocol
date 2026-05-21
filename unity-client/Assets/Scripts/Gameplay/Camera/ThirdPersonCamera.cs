// ThirdPersonCamera.cs — Caméra orbitale 3rd person pour le joueur RocketPi.
//
// Suit un Transform cible (le body de l'opérateur), oriente avec la souris,
// gère la collision avec les murs via SphereCast pour éviter de traverser
// la géométrie. Compatible Input System nouveau.
//
// Posé sur le GameObject Camera principal de la scène Training (cf.
// SceneScaffolder). La cible est assignée par PlayerController.SetCameraTarget()
// au moment où le body de l'opérateur est instancié.

using UnityEngine;
using UnityEngine.InputSystem;

namespace Rocketpi.Gameplay.CameraControl
{
    [RequireComponent(typeof(Camera))]
    public class ThirdPersonCamera : MonoBehaviour
    {
        [Header("Cible")]
        [SerializeField] private Transform _target;
        [SerializeField] private Vector3   _shoulderOffset = new(0.5f, 1.6f, 0f);

        [Header("Distance + zoom")]
        [SerializeField] private float _distance       = 3.5f;
        [SerializeField] private float _minDistance    = 1.5f;
        [SerializeField] private float _maxDistance    = 6.0f;
        [SerializeField] private float _zoomSpeed      = 4.0f;

        [Header("Look")]
        [SerializeField] private float _yawSensitivity   = 0.20f;
        [SerializeField] private float _pitchSensitivity = 0.15f;
        [SerializeField] private float _pitchMin = -40f;
        [SerializeField] private float _pitchMax = 70f;

        [Header("Lissage + collision")]
        [SerializeField] private float _followSmoothTime = 0.05f;
        [SerializeField] private float _collisionRadius  = 0.25f;
        [SerializeField] private LayerMask _collisionMask = ~0;

        [Header("Sortie")]
        [Tooltip("Yaw exporté chaque frame : utilisé par le PlayerController pour orienter le body vers l'avant de la caméra.")]
        public float Yaw   { get; private set; }
        public float Pitch { get; private set; }

        private InputAction _lookAction;
        private InputAction _zoomAction;
        private Vector3 _smoothedPos;
        private Vector3 _velRef;
        private bool    _interactive = true;

        private void Awake()
        {
            _lookAction = new InputAction("CameraLook", binding: "<Mouse>/delta");
            _lookAction.AddBinding("<Gamepad>/rightStick");
            _zoomAction = new InputAction("CameraZoom", binding: "<Mouse>/scroll/y");
        }

        private void OnEnable()
        {
            _lookAction.Enable();
            _zoomAction.Enable();
        }

        private void OnDisable()
        {
            _lookAction.Disable();
            _zoomAction.Disable();
        }

        public void SetTarget(Transform target, float shoulderHeight)
        {
            _target = target;
            _shoulderOffset = new Vector3(_shoulderOffset.x, shoulderHeight, _shoulderOffset.z);
            if (_target != null)
                _smoothedPos = _target.position + _shoulderOffset;
        }

        /// <summary>Désactive les inputs caméra (ex. UI ouverte).</summary>
        public void SetInteractive(bool value) => _interactive = value;

        private void LateUpdate()
        {
            if (_target == null) return;

            if (_interactive)
            {
                var delta = _lookAction.ReadValue<Vector2>();
                Yaw   += delta.x * _yawSensitivity;
                Pitch  = Mathf.Clamp(Pitch - delta.y * _pitchSensitivity, _pitchMin, _pitchMax);

                var zoom = _zoomAction.ReadValue<float>();
                if (Mathf.Abs(zoom) > 0.01f)
                    _distance = Mathf.Clamp(_distance - zoom * _zoomSpeed * Time.deltaTime, _minDistance, _maxDistance);
            }

            // Ancrage : épaule droite du body
            var anchor = _target.position + _shoulderOffset;
            _smoothedPos = Vector3.SmoothDamp(_smoothedPos, anchor, ref _velRef, _followSmoothTime);

            // Rotation cible
            var rot = Quaternion.Euler(Pitch, Yaw, 0f);
            var desiredOffset = rot * new Vector3(0f, 0f, -_distance);
            var desiredPos = _smoothedPos + desiredOffset;

            // Collision : rapproche la cam si un mur est entre elle et le perso
            if (Physics.SphereCast(_smoothedPos, _collisionRadius, desiredOffset.normalized,
                    out var hit, _distance, _collisionMask, QueryTriggerInteraction.Ignore))
            {
                desiredPos = _smoothedPos + desiredOffset.normalized * (hit.distance - 0.05f);
            }

            transform.position = desiredPos;
            transform.LookAt(_smoothedPos);
        }
    }
}
