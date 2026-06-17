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

        // Les offsets de placement dans la main sont maintenant PAR ARME (sur WeaponBase).
        // Voir le prefab de chaque arme (Sniper, Pistol, Warhammer…) section "Placement
        // dans la main droite" pour les ajuster individuellement.

        [Header("Refs")]
        [SerializeField] private ThirdPersonCamera _camera;
        [SerializeField] private Transform _bodyAnchor;       // où instancier le BodyPrefab (par défaut = transform)
        [SerializeField] private TrainingMatchManager _match;

        public OperatorData Operator => _operator;
        public HealthSystem Health { get; private set; }
        public WeaponBase   Weapon { get; private set; }
        public OperatorBody Body   { get; private set; }

        /// <summary>Multiplicateur de vitesse (power-up SpeedBoost ou Battlecry buff). 1 = normal.</summary>
        public float SpeedMultiplier { get; set; } = 1f;

        /// <summary>True = locomotion entièrement freezée (anim Battlecry en cours, etc.).
        /// Les inputs sont quand même lus mais aucun déplacement n'est appliqué.</summary>
        public bool LockMovement { get; set; }

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

        private Vector3 _spawnPosition;

        private void Awake()
        {
            _cc = GetComponent<CharacterController>();
            Health = GetComponent<HealthSystem>();
            Health.OnDied += HandlePlayerDeath;
            _spawnPosition = transform.position;
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
            if (Health != null) Health.OnDied -= HandlePlayerDeath;
            CancelInvoke(nameof(Respawn));
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
            EnsureMeleeBuffController();
        }

        /// <summary>Pose un MeleeBuffController sur le joueur SI l'opérateur courant est
        /// IsMelee, sinon le retire. Idempotent — appelé à chaque SetOperator.</summary>
        private void EnsureMeleeBuffController()
        {
            var existing = GetComponent<MeleeBuffController>();
            bool wantBuff = _operator != null && _operator.IsMelee;
            if (wantBuff && existing == null) gameObject.AddComponent<MeleeBuffController>();
            else if (!wantBuff && existing != null) Destroy(existing);
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

        // ── Mort & respawn ────────────────────────────────────────────────
        private const float RespawnDelay = 3f;

        private void HandlePlayerDeath()
        {
            // Joue l'anim Death du body (même que les NPCs) + gèle les entrées.
            Body?.TriggerDeath();
            SetMatchReady(false);
            _velocity = Vector3.zero;
            // Respawn après 3 s à la position initiale (centre de la plaza).
            CancelInvoke(nameof(Respawn));
            Invoke(nameof(Respawn), RespawnDelay);
        }

        private void Respawn()
        {
            // Téléporte au spawn + reset vie + sort de l'anim Death.
            if (_cc != null)
            {
                _cc.enabled = false;
                transform.position = _spawnPosition;
                _cc.enabled = true;
            }
            Health?.ResetHealth();
            Body?.Revive();
            SetMatchReady(true);
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

        // Suivi manuel de l'arme : la main Mixamo a un scale dynamique baked par l'Animator
        // qui shrink TOUT enfant (cube de probe vu à 1 cm au lieu de 70 cm). Workaround : on
        // garde l'arme en SCENE ROOT et on la repositionne chaque LateUpdate (après que
        // l'Animator ait mis les bones à jour) à la position/rotation du bone main.
        [System.NonSerialized] public Transform _weaponHandBone;
        [System.NonSerialized] public Transform _weaponAttached;
        [System.NonSerialized] public Vector3 _weaponLocalOffset;
        [System.NonSerialized] public Quaternion _weaponLocalRotation = Quaternion.identity;

        private void LateUpdate()
        {
            if (_weaponAttached == null || _weaponHandBone == null || Weapon == null) return;
            // Recalcule chaque frame depuis l'arme — modifier les valeurs sur le prefab
            // d'arme en Play mode met à jour le placement instantanément.
            var off = Weapon.HandOffset;
            var rot = Quaternion.Euler(Weapon.HandEuler);
            _weaponAttached.position = _weaponHandBone.position
                                     + _weaponHandBone.rotation * off;
            _weaponAttached.rotation = _weaponHandBone.rotation * rot;
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
            EnsureMeleeBuffController();
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

            // Pour les opérateurs IsMelee (créatures sans arme à feu), on remplace l'anim Run
            // par "Unarmed Run Forward" (mains levées / posture sans arme épaulée). Le clip
            // override est dans Resources/Animations/MeleeLocomotionOverride.overrideController
            // (asset construit via Tools > RocketPi > Build Melee Locomotion Override).
            if (_operator.IsMelee && _bodyInstance != null)
            {
                var anim = _bodyInstance.GetComponentInChildren<Animator>();
                if (anim != null)
                {
                    var ov = Resources.Load<AnimatorOverrideController>("Animations/MeleeLocomotionOverride");
                    if (ov != null) anim.runtimeAnimatorController = ov;
                    else Debug.LogWarning("[PlayerController] MeleeLocomotionOverride introuvable dans Resources/Animations/. " +
                                         "Lance Tools > RocketPi > Build Melee Locomotion Override.");
                }
            }

            // Mémorise les renderers pour les masquer en 1ère personne.
            _bodyRenderers = _bodyInstance.GetComponentsInChildren<Renderer>(true);
            ApplyViewMode();
        }

        private void HandleWeaponFired()
        {
            // Armes de mêlée → anim de swing (Melee-Combo-Attack), pas le trigger Fire
            // qui jouerait un muzzle flash / recul d'arme à feu.
            if (Weapon is MeleeWeapon) Body?.TriggerMeleeAttack();
            else                       Body?.TriggerFire();
        }
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

        /// <summary>Cherche un bone par suffixe de nom dans le body instancié (ex. "RightHand"
        /// matche "mixamorig:RightHand"). Retourne null si le body n'est pas spawn ou si
        /// l'opérateur n'a pas ce bone.</summary>
        private Transform FindBoneOnBody(string boneSuffix)
        {
            if (_bodyInstance == null) return null;
            foreach (var t in _bodyInstance.GetComponentsInChildren<Transform>(true))
            {
                var n = t.name;
                // "mixamorig:RightHand" mais PAS "mixamorig:RightHandThumb1".
                if (n.EndsWith(boneSuffix) || n.EndsWith(":" + boneSuffix)) return t;
            }
            return null;
        }

        private void EquipOperatorWeapon()
        {
            EnsureWeaponSocketOnPlayer();
            // Crée un socket à la volée si l'inspecteur n'en a pas → évite que l'arme
            // soit silencieusement non-spawned quand un user clique "play" sur une scène
            // mal câblée (cas observé sur Vex après changement de body prefab).
            if (_weaponSocket == null)
            {
                var go = new GameObject("WeaponSocket");
                _weaponSocket = go.transform;
                _weaponSocket.SetParent(transform, false);
                _weaponSocket.localPosition = new Vector3(0.25f, 1.4f, 0.45f);
                Debug.LogWarning("[PlayerController] WeaponSocket auto-créé (référence Inspector manquante).");
            }
            if (_operator == null) { Debug.LogWarning("[PlayerController] EquipOperatorWeapon: _operator null."); return; }
            if (_operator.WeaponPrefab == null)
            {
                Debug.LogWarning($"[PlayerController] EquipOperatorWeapon: WeaponPrefab null sur {_operator.DisplayName}.");
                return;
            }

            if (Weapon != null)
            {
                Weapon.OnFired -= HandleWeaponFired;
                Weapon.OnReloadStarted -= HandleWeaponReload;
                Destroy(Weapon.gameObject);
            }

            // Le bone main Mixamo a un scale dynamique qui shrink ses enfants à 1 % au
            // runtime. Workaround : on garde l'arme en SCENE ROOT (pas de parent) et on
            // la track manuellement en LateUpdate à la position/rotation du bone.
            var hand = FindBoneOnBody("RightHand");
            bool meleeWeapon = _operator.IsMelee || _operator.WeaponPrefab.GetComponent<MeleeWeapon>() != null;
            var instance = Instantiate(_operator.WeaponPrefab);
            instance.transform.localPosition = Vector3.zero;
            instance.transform.localRotation = Quaternion.identity;
            instance.transform.localScale    = Vector3.one;
            Transform parent = hand != null ? hand : _weaponSocket;

            // Rescale via bounds (taille cible 1.2 m mêlée / 0.7 m distance).
            var renderers = instance.GetComponentsInChildren<Renderer>(true);
            if (renderers.Length > 0)
            {
                var b = renderers[0].bounds;
                for (int i = 1; i < renderers.Length; i++) b.Encapsulate(renderers[i].bounds);
                float currentLongest = Mathf.Max(b.size.x, b.size.y, b.size.z);
                float target = meleeWeapon ? 1.2f : 0.7f;
                if (currentLongest > 0.0001f && currentLongest < target * 0.5f)
                {
                    instance.transform.localScale *= target / currentLongest;
                }
            }

            // Tracking manuel : LateUpdate de PlayerController repositionne l'arme à
            // la position/rotation du bone main (offset + rotation locale lus depuis l'arme).
            // Pas de parent → pas de transmission du scale dynamique de l'Animator.
            if (hand != null)
            {
                _weaponHandBone = hand;
                _weaponAttached = instance.transform;
                // Lecture initiale depuis l'arme (utilisée pour placement immédiat avant
                // le 1er LateUpdate). Le LateUpdate relit chaque frame, donc tweakable live.
                var w = instance.GetComponent<WeaponBase>();
                _weaponLocalOffset   = w != null ? w.HandOffset : Vector3.zero;
                _weaponLocalRotation = w != null ? Quaternion.Euler(w.HandEuler) : Quaternion.identity;
                instance.transform.position = hand.position + hand.rotation * _weaponLocalOffset;
                instance.transform.rotation = hand.rotation * _weaponLocalRotation;
            }
            else
            {
                // Fallback : pas de bone main → parente au socket classique
                instance.transform.SetParent(_weaponSocket, false);
                _weaponHandBone = null;
                _weaponAttached = null;
            }
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

            // Locked = on bloque le déplacement (anim Battlecry en cours sur Crag/Iron/Wraith).
            if (LockMovement)
            {
                wishDir = Vector3.zero;
                _velocity.x = 0f;
                _velocity.z = 0f;
            }
            else
            {
                var horizontal = wishDir * (speed * groundControl);
                _velocity.x = horizontal.x;
                _velocity.z = horizontal.z;
            }

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
            // Pas de saut quand locké en Battlecry — il faut finir l'anim.
            if (!LockMovement && _jumpAction.WasPressedThisFrame() && _jumpsUsed < _maxJumps)
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
