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
using Rocketpi.Gameplay.Abilities;
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
        [SerializeField] private float _jumpVelocity = 9.5f;   // ~2.5 m de hauteur (atteint les plateformes à bonus)
        private float _jumpMultiplier = 1f;   // certains opérateurs sautent plus haut
        private int   _maxJumps = 1;           // certains opérateurs ont le double saut
        private int   _jumpsUsed;
        private Renderer[] _weaponRenderers;   // cachés en marchant (mode infiltration)
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

        /// <summary>Multiplicateur de vitesse (power-up SpeedBoost). 1 = normal.</summary>
        public float SpeedMultiplier { get; set; } = 1f;

        /// <summary>False pendant l'écran de sélection d'opérateur : gèle le joueur.</summary>
        public bool CanPlay { get; private set; } = true;
        public void SetMatchReady(bool ready) => CanPlay = ready;

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
        private InputAction _toggleViewAction;

        private GameObject _bodyInstance;
        private Renderer[] _bodyRenderers;
        private bool _firstPerson;
        private Vector3 _velocity;
        private PlayerAbilities _abilities;

        private void Awake()
        {
            _cc = GetComponent<CharacterController>();
            Health = GetComponent<HealthSystem>();
            _abilities = GetComponent<PlayerAbilities>();
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
            // Capacité de classe sur F (Q sert au déplacement gauche en AZERTY).
            _ability1Action = new InputAction("Ability1", binding: "<Keyboard>/f");
            _ability2Action = new InputAction("Ability2", binding: "<Keyboard>/e");
            _ultimateAction = new InputAction("Ultimate", binding: "<Keyboard>/r");
            _toggleViewAction = new InputAction("ToggleView", binding: "<Keyboard>/v");
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
            _toggleViewAction.Enable();

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
            _toggleViewAction.Disable();

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
            if (!CanPlay) return;   // écran de sélection ouvert → joueur gelé

            // En standalone (Play mode sans bridge JS), aucun OnSessionStarted ne
            // vient locker le curseur. On permet donc un lock manuel : clic gauche
            // capture la souris, Échap la relâche. C'est le pattern FPS/TPS classique.
            if (Cursor.lockState != CursorLockMode.Locked)
            {
                if (Mouse.current != null && Mouse.current.leftButton.wasPressedThisFrame)
                {
                    LockCursor();
                    return; // ce clic sert à capturer la souris, pas à tirer
                }
            }
            else if (Keyboard.current != null && Keyboard.current.escapeKey.wasPressedThisFrame)
            {
                ReleaseCursor();
            }

            // Toggle vue 1ère / 3ème personne (touche V).
            if (_toggleViewAction.WasPressedThisFrame()) ToggleView();

            var matchActive = Cursor.lockState == CursorLockMode.Locked;
            if (matchActive)
            {
                HandleFire();
                // Mode infiltration : sorts/ultime UNIQUEMENT en courant (marcher = se fondre
                // dans la foule de faux opérateurs, sans rien pouvoir déclencher).
                if (IsRunning() && _ability1Action.WasPressedThisFrame()) _abilities?.UseClassAbility();
                if (IsRunning() && _ultimateAction.WasPressedThisFrame()) _abilities?.UseUltimate();
            }
            HandleMove();
            UpdateWeaponVisibility();
        }

        /// <summary>Mode infiltration : l'arme n'est VISIBLE qu'en courant. En marchant,
        /// le joueur ressemble aux faux opérateurs (civils sans arme apparente).</summary>
        private void UpdateWeaponVisibility()
        {
            if (_weaponRenderers == null) return;
            var show = IsRunning();
            foreach (var r in _weaponRenderers)
                if (r != null) r.enabled = show;
        }

        // ── Vue 1ère / 3ème personne ───────────────────────────────────────

        public void ToggleView()
        {
            _firstPerson = !_firstPerson;
            ApplyViewMode();
        }

        private void ApplyViewMode()
        {
            _camera?.SetFirstPerson(_firstPerson);
            // Corps visible dans les DEUX vues → en 1ère personne on voit ses bras/mains
            // tenir l'arme. La TÊTE est masquée par OperatorBody (sinon on voit l'intérieur
            // du crâne depuis la caméra placée aux yeux).
            if (_bodyRenderers != null)
                foreach (var r in _bodyRenderers)
                    if (r != null) r.enabled = true;
            Body?.SetFirstPersonView(_firstPerson);
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

            // Capacités de saut par classe : Scout saute très haut, Infiltrator a le
            // double saut (saut aérien). Les autres : saut normal.
            switch (_operator.Role)
            {
                case Rocketpi.Gameplay.Operators.OperatorRole.Scout:
                    _jumpMultiplier = 2.4f; _maxJumps = 1; break;          // ~3× plus haut
                case Rocketpi.Gameplay.Operators.OperatorRole.Infiltrator:
                    _jumpMultiplier = 1.15f; _maxJumps = 2; break;         // double saut
                default:
                    _jumpMultiplier = 1f; _maxJumps = 1; break;
            }
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

            // Mémorise les renderers pour les masquer en 1ère personne.
            _bodyRenderers = _bodyInstance.GetComponentsInChildren<Renderer>(true);
            ApplyViewMode();
        }

        private void HandleWeaponFired()    => Body?.TriggerFire();
        private void HandleWeaponReload()   => Body?.TriggerReload();

        /// <summary>Affiche/masque le mesh du joueur (camouflage / invisibilité).</summary>
        public void SetBodyVisible(bool visible)
        {
            if (_bodyRenderers == null) return;
            foreach (var r in _bodyRenderers)
                if (r != null) r.enabled = visible;
        }

        /// <summary>
        /// Garantit que le WeaponSocket est attaché au PLAYER (pas à la caméra) et placé
        /// devant, à hauteur de main. Sinon (socket sous la caméra 3ème personne) l'arme
        /// et le muzzle se retrouvent DERRIÈRE le model → balles qui partent de derrière.
        /// Sur le Player, le socket tourne avec le corps (qui fait face à la visée en tir)
        /// et survit aux respawns du body.
        /// </summary>
        private void EnsureWeaponSocketOnPlayer()
        {
            if (_weaponSocket == null) return;
            if (_weaponSocket.parent != transform)
                _weaponSocket.SetParent(transform, worldPositionStays: false);
            _weaponSocket.localPosition = new Vector3(0.25f, 1.4f, 0.45f); // droite, épaule, devant
            _weaponSocket.localRotation = Quaternion.identity;
        }

        private void EquipOperatorWeapon()
        {
            EnsureWeaponSocketOnPlayer();
            if (_operator == null || _operator.WeaponPrefab == null || _weaponSocket == null) return;

            if (Weapon != null)
            {
                Weapon.OnFired -= HandleWeaponFired;
                Weapon.OnReloadStarted -= HandleWeaponReload;
                Destroy(Weapon.gameObject);
            }

            var instance = Instantiate(_operator.WeaponPrefab, _weaponSocket);
            instance.transform.localPosition = Vector3.zero;
            instance.transform.localRotation = Quaternion.identity;
            Weapon = instance.GetComponent<WeaponBase>();
            Weapon?.Initialize(this);

            // Mode infiltration : on cache les renderers de l'arme tant que le joueur ne
            // court pas → en marchant, il ressemble aux civils (pas d'arme visible).
            _weaponRenderers = instance.GetComponentsInChildren<Renderer>(true);

            // Relaye tir + recharge vers l'anim du body.
            if (Weapon != null)
            {
                Weapon.OnFired += HandleWeaponFired;
                Weapon.OnReloadStarted += HandleWeaponReload;

                // Synchronise la durée de rechargement sur la longueur de l'anim Reload
                // pour que les balles ne reviennent pas avant la fin de l'animation.
                if (Body != null)
                {
                    var reloadLen = Body.GetClipLength("Reload");
                    if (reloadLen > 0f) Weapon.SetReloadTime(reloadLen);
                }
            }

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
            var speed = (sprint ? _sprintSpeed : _walkSpeed) * SpeedMultiplier;
            var groundControl = _cc.isGrounded ? 1f : _airControl;

            var horizontal = wishDir * (speed * groundControl);
            _velocity.x = horizontal.x;
            _velocity.z = horizontal.z;

            if (_cc.isGrounded)
            {
                if (_velocity.y < 0f) _velocity.y = -2f;
                _jumpsUsed = 0;                       // reset au sol
            }
            else
            {
                _velocity.y += _gravity * Time.deltaTime;
            }

            // Saut : au sol OU en l'air tant qu'il reste des sauts (double saut pour
            // certaines classes). Hauteur modulée par le multiplicateur d'opérateur.
            if (_jumpAction.WasPressedThisFrame() && _jumpsUsed < _maxJumps)
            {
                var airJump = !_cc.isGrounded;   // 2ᵉ saut en l'air → salto
                _velocity.y = _jumpVelocity * _jumpMultiplier;
                _jumpsUsed++;
                if (airJump) Body?.TriggerFlip();
            }

            _cc.Move(_velocity * Time.deltaTime);

            // Vise-t-on (tir principal/secondaire enfoncé) ? Si oui, le corps doit faire
            // FACE au réticule (yaw caméra) pour que l'arme — et donc le départ des balles —
            // pointe vers la cible. Sinon les balles semblent partir de derrière le model.
            var aiming = (_fireAction != null && _fireAction.IsPressed())
                      || (_altFireAction != null && _altFireAction.IsPressed());

            if (_firstPerson || aiming)
            {
                // Face au yaw caméra. En visée 3ème personne on tourne vite vers la cible.
                var aimRot = Quaternion.Euler(0f, camYaw, 0f);
                transform.rotation = _firstPerson
                    ? aimRot
                    : Quaternion.Slerp(transform.rotation, aimRot, Time.deltaTime * _bodyTurnSpeed * 3f);
            }
            else if (wishDir.sqrMagnitude > 0.001f)
            {
                // 3ème personne au repos de tir : tourne vers la direction du mouvement.
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
            // Bloque le tir tant que l'anim de recharge joue (peu importe sa durée).
            if (Body != null && Body.IsPlayingReload()) return;
            // Mode infiltration : on ne peut TIRER qu'en courant. En marchant, on se fond
            // dans la foule mais on est désarmé.
            if (!IsRunning()) { Weapon.OnFireReleased(); return; }
            if (_fireAction.IsPressed())     Weapon.OnFireHeld();
            if (_fireAction.WasReleasedThisFrame()) Weapon.OnFireReleased();
            if (_altFireAction.WasPressedThisFrame()) Weapon.OnAltFire();
        }

        /// <summary>True si le joueur COURT (sprint + déplacement). Conditionne tir + sorts
        /// dans le mode infiltration : marcher = se cacher, courir = pouvoir agir (mais s'exposer).</summary>
        public bool IsRunning()
        {
            if (_sprintAction == null || _moveAction == null) return false;
            return _sprintAction.IsPressed() && _moveAction.ReadValue<Vector2>().sqrMagnitude > 0.04f;
        }
    }
}
