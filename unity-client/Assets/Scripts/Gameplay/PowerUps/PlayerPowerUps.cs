// PlayerPowerUps.cs — Gère les effets des bonus ramassés par le joueur.
//
// Posé sur le Player (à côté de PlayerController + HealthSystem). Les pickups
// (PowerUpPickup) appellent Apply(type) au contact. Les effets temporaires
// sont gérés par des timers ; MegaBomb et HealthPack sont instantanés.

using Rocketpi.Gameplay.NPC;
using Rocketpi.Gameplay.Weapons;
using UnityEngine;

namespace Rocketpi.Gameplay.PowerUps
{
    [RequireComponent(typeof(PlayerController))]
    [RequireComponent(typeof(HealthSystem))]
    public class PlayerPowerUps : MonoBehaviour
    {
        [Header("Durées (s)")]
        [SerializeField] private float _shieldDuration = 8f;
        [SerializeField] private float _bounceDuration = 12f;
        [SerializeField] private float _rapidDuration  = 10f;
        [SerializeField] private float _quadDuration   = 12f;
        [SerializeField] private float _speedDuration  = 12f;

        [Header("Valeurs")]
        [SerializeField] private float _rapidFireMult = 3f;
        [SerializeField] private float _quadDamageMult = 4f;
        [SerializeField] private float _speedMult = 1.7f;
        [SerializeField] private int   _healAmount = 100;

        [Header("MegaBomb")]
        [SerializeField] private float _megaBombRadius = 18f;
        [SerializeField] private int   _megaBombDamage = 9999;

        [Header("Shockwave")]
        [SerializeField] private float _shockwaveRadius = 14f;
        [SerializeField] private float _shockwavePush   = 10f;

        // État actif courant (pour l'UI éventuelle).
        public PowerUpType? Active { get; private set; }
        public float ActiveUntil { get; private set; }

        private PlayerController _player;
        private HealthSystem _health;

        private float _shieldUntil, _bounceUntil, _rapidUntil, _quadUntil, _speedUntil;

        private void Awake()
        {
            _player = GetComponent<PlayerController>();
            _health = GetComponent<HealthSystem>();
        }

        public void Apply(PowerUpType type)
        {
            var now = Time.time;
            switch (type)
            {
                case PowerUpType.Shield:
                    _shieldUntil = now + _shieldDuration;
                    _health.SetInvulnerable(true);
                    SetActive(type, _shieldUntil);
                    break;

                case PowerUpType.BouncingBullets:
                    _bounceUntil = now + _bounceDuration;
                    if (_player.Weapon != null) _player.Weapon.BouncingBullets = true;
                    SetActive(type, _bounceUntil);
                    break;

                case PowerUpType.RapidFire:
                    _rapidUntil = now + _rapidDuration;
                    if (_player.Weapon != null) _player.Weapon.FireRateMultiplier = _rapidFireMult;
                    SetActive(type, _rapidUntil);
                    break;

                case PowerUpType.QuadDamage:
                    _quadUntil = now + _quadDuration;
                    if (_player.Weapon != null) _player.Weapon.DamageMultiplier = _quadDamageMult;
                    SetActive(type, _quadUntil);
                    break;

                case PowerUpType.SpeedBoost:
                    _speedUntil = now + _speedDuration;
                    _player.SpeedMultiplier = _speedMult;
                    SetActive(type, _speedUntil);
                    break;

                case PowerUpType.HealthPack:
                    _health.Heal(_healAmount);
                    break;

                case PowerUpType.MegaBomb:
                    DetonateMegaBomb();
                    break;

                case PowerUpType.Shockwave:
                    DetonateShockwave();
                    break;
            }
        }

        private void SetActive(PowerUpType type, float until)
        {
            Active = type;
            ActiveUntil = until;
        }

        private void Update()
        {
            var now = Time.time;

            if (_shieldUntil > 0f && now > _shieldUntil)
            {
                _shieldUntil = 0f;
                _health.SetInvulnerable(false);
            }
            if (_bounceUntil > 0f && now > _bounceUntil)
            {
                _bounceUntil = 0f;
                if (_player.Weapon != null) _player.Weapon.BouncingBullets = false;
            }
            if (_rapidUntil > 0f && now > _rapidUntil)
            {
                _rapidUntil = 0f;
                if (_player.Weapon != null) _player.Weapon.FireRateMultiplier = 1f;
            }
            if (_quadUntil > 0f && now > _quadUntil)
            {
                _quadUntil = 0f;
                if (_player.Weapon != null) _player.Weapon.DamageMultiplier = 1f;
            }
            if (_speedUntil > 0f && now > _speedUntil)
            {
                _speedUntil = 0f;
                _player.SpeedMultiplier = 1f;
            }

            if (Active.HasValue && now > ActiveUntil) Active = null;
        }

        private void DetonateMegaBomb()
        {
            var hits = Physics.OverlapSphere(transform.position, _megaBombRadius);
            foreach (var h in hits)
            {
                var hs = h.GetComponentInParent<HealthSystem>();
                if (hs == null || hs == _health || hs.IsDead) continue;
                hs.TakeDamage(_megaBombDamage);
            }
            SpawnBlastFx();
        }

        private void SpawnBlastFx()
        {
            // Sphère lumineuse expansive éphémère (feedback visuel).
            var fx = GameObject.CreatePrimitive(PrimitiveType.Sphere);
            fx.name = "MegaBombFx";
            Destroy(fx.GetComponent<Collider>());
            fx.transform.position = transform.position + Vector3.up * 1f;
            fx.transform.localScale = Vector3.one * 0.5f;
            var r = fx.GetComponent<MeshRenderer>();
            var mat = new Material(r.sharedMaterial) { color = new Color(1f, 0.55f, 0.1f, 1f) };
            mat.SetColor("_BaseColor", new Color(1f, 0.55f, 0.1f, 1f));
            r.sharedMaterial = mat;
            fx.AddComponent<MegaBombFx>().Init(_megaBombRadius);
        }

        // ── Shockwave : repousse tous les NPCs + vague visible au sol ──────
        private void DetonateShockwave()
        {
            var hits = Physics.OverlapSphere(transform.position, _shockwaveRadius);
            var pushed = new System.Collections.Generic.HashSet<OperatorNpcController>();
            foreach (var h in hits)
            {
                var npc = h.GetComponentInParent<OperatorNpcController>();
                if (npc == null || pushed.Contains(npc)) continue;
                pushed.Add(npc);
                npc.ApplyKnockback(transform.position, _shockwavePush);
            }
            SpawnShockwaveFx();
        }

        private void SpawnShockwaveFx()
        {
            // Disque (cylindre aplati) cyan qui s'étend depuis le joueur = onde au sol.
            var fx = GameObject.CreatePrimitive(PrimitiveType.Cylinder);
            fx.name = "ShockwaveFx";
            Destroy(fx.GetComponent<Collider>());
            fx.transform.position = transform.position + Vector3.up * 0.15f;
            fx.transform.localScale = new Vector3(0.5f, 0.04f, 0.5f);
            var r = fx.GetComponent<MeshRenderer>();
            var c = new Color(0.3f, 0.75f, 1f, 1f);
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            mat.EnableKeyword("_EMISSION");
            mat.SetColor("_EmissionColor", c * 2.2f);
            r.sharedMaterial = mat;
            fx.AddComponent<ShockwaveFx>().Init(_shockwaveRadius);
        }
    }

    /// <summary>Anime la sphère d'explosion (expansion + fondu) puis se détruit.</summary>
    public class MegaBombFx : MonoBehaviour
    {
        private float _radius = 18f;
        private float _t;

        public void Init(float radius) => _radius = radius;

        private void Update()
        {
            _t += Time.deltaTime * 2.5f;
            var s = Mathf.Lerp(0.5f, _radius * 2f, Mathf.Clamp01(_t));
            transform.localScale = Vector3.one * s;
            if (_t >= 1f) Destroy(gameObject);
        }
    }

    /// <summary>Anime l'onde de choc : disque qui s'étend au sol + fondu, puis se détruit.</summary>
    public class ShockwaveFx : MonoBehaviour
    {
        private float _radius = 14f;
        private float _t;
        private Renderer _renderer;
        private Color _baseColor;

        public void Init(float radius)
        {
            _radius = radius;
            _renderer = GetComponent<Renderer>();
            if (_renderer != null) _baseColor = _renderer.sharedMaterial.color;
        }

        private void Update()
        {
            _t += Time.deltaTime * 2f;
            var k = Mathf.Clamp01(_t);
            // Le rayon du cylindre = localScale.x/z (diamètre = 2×rayon → scale = rayon).
            var s = Mathf.Lerp(0.5f, _radius, k);
            transform.localScale = new Vector3(s, 0.04f, s);

            // Fondu en fin d'expansion.
            if (_renderer != null)
            {
                var col = _baseColor;
                col.a = 1f - k;
                _renderer.sharedMaterial.color = col;
                _renderer.sharedMaterial.SetColor("_BaseColor", col);
            }
            if (_t >= 1f) Destroy(gameObject);
        }
    }
}
