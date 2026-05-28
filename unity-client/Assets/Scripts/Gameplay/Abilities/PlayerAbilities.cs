// PlayerAbilities.cs — Sorts du joueur : capacité de CLASSE (touche Q, selon le
// rôle) + ULTIME signature (touche R, selon l'opérateur). Cooldowns séparés.
//
// Tout est centralisé ici (plutôt que 16 prefabs) pour rester simple à itérer.
// Les effets temporaires (buffs) sont gérés par timers dans Update.

using Rocketpi.Gameplay.NPC;
using Rocketpi.Gameplay.Operators;
using UnityEngine;

namespace Rocketpi.Gameplay.Abilities
{
    [RequireComponent(typeof(PlayerController))]
    [RequireComponent(typeof(HealthSystem))]
    [RequireComponent(typeof(CharacterController))]
    public class PlayerAbilities : MonoBehaviour
    {
        [Header("Cooldowns (s)")]
        [SerializeField] private float _classCooldown = 8f;
        [SerializeField] private float _ultimateCooldown = 30f;

        [Header("Modèles 3D des sorts (optionnel — fallback primitive sinon)")]
        [Tooltip("Modèle du mur de bouclier (Tank/Crag). Ex : Assets/Models/PowerUps/Shield.glb")]
        [SerializeField] private GameObject _shieldModel;

        public float ClassCooldownRemaining => Mathf.Max(0f, _classReadyAt - Time.time);
        public float UltCooldownRemaining   => Mathf.Max(0f, _ultReadyAt - Time.time);
        public float ClassCooldownTotal => _classCooldown;
        public float UltCooldownTotal   => _ultimateCooldown;
        // 0..1 : fraction rechargée (1 = prêt).
        public float ClassReadyFraction => _classCooldown <= 0f ? 1f : 1f - ClassCooldownRemaining / _classCooldown;
        public float UltReadyFraction   => _ultimateCooldown <= 0f ? 1f : 1f - UltCooldownRemaining / _ultimateCooldown;
        public string LastCast { get; private set; } = "";
        public float  LastCastAt { get; private set; } = -10f;

        private PlayerController _player;
        private HealthSystem _health;
        private CharacterController _cc;

        private float _classReadyAt;
        private float _ultReadyAt;

        // Buffs temporaires : (endTime, type) — restaurés à l'expiration.
        private float _dmgBuffUntil, _fireBuffUntil, _speedBuffUntil, _invulnUntil, _camoUntil, _infAmmoUntil, _regenUntil;
        private int   _regenPerTick;

        private void Awake()
        {
            _player = GetComponent<PlayerController>();
            _health = GetComponent<HealthSystem>();
            _cc     = GetComponent<CharacterController>();
        }

        // ── Entrées ────────────────────────────────────────────────────────

        public void UseClassAbility()
        {
            if (_player.Operator == null || Time.time < _classReadyAt) return;
            _classReadyAt = Time.time + _classCooldown;

            switch (_player.Operator.Role)
            {
                case OperatorRole.Tank:        CastShieldWall(2f);                         LastCast = "Mur de bouclier"; break;
                case OperatorRole.Healer:      HealPercent(0.25f);                          LastCast = "Soin +25%";       break;
                case OperatorRole.Sniper:      BuffDamage(3f, 4f);                          LastCast = "Focus ×3 dégâts"; break;
                case OperatorRole.Scout:       Dash(8f);                                    LastCast = "Dash";            break;
                case OperatorRole.Explosives:  ExplodeAhead(8f, 6f, 80);                    LastCast = "Grenade";         break;
                case OperatorRole.Assault:     BuffDamage(1f, 5f); BuffFireRate(2f, 5f); BuffSpeed(1.3f, 5f); LastCast = "Adrénaline"; break;
                case OperatorRole.Infiltrator: Camouflage(5f);                              LastCast = "Camouflage";      break;
                case OperatorRole.Hacker:      StunAround(12f, 3f);                         LastCast = "EMP";             break;
            }
            LastCastAt = Time.time;
        }

        public void UseUltimate()
        {
            if (_player.Operator == null || Time.time < _ultReadyAt) return;
            _ultReadyAt = Time.time + _ultimateCooldown;

            switch (_player.Operator.Codename)
            {
                case "VX-01": OrbitalStrike();                       LastCast = "ULT Vex — Frappe orbitale"; break;
                case "HL-02": Regen(0.20f, 4f);                      LastCast = "ULT Halo — Champ de soin";  break;
                case "DR-03": BuffSpeed(3f, 6f);                     LastCast = "ULT Drift — Hypervitesse";  break;
                case "CR-04": Invuln(4f); CastFortress(4f);          LastCast = "ULT Crag — Forteresse";     break;
                case "BK-05": Barrage(15f, 5, 70);                   LastCast = "ULT Brick — Barrage";       break;
                case "IR-06": InfiniteAmmo(6f); BuffFireRate(4f, 6f);LastCast = "ULT Iron — Suppression";    break;
                case "WR-07": Camouflage(8f); BuffSpeed(2f, 8f);     LastCast = "ULT Wraith — Phase";        break;
                case "EC-08": StunAll(5f);                           LastCast = "ULT Echo — Black-out";      break;
                default:      Regen(0.15f, 3f);                      LastCast = "ULT générique";             break;
            }
            LastCastAt = Time.time;
        }

        // ── Update : expiration des buffs ──────────────────────────────────

        private void Update()
        {
            var now = Time.time;
            if (_dmgBuffUntil   > 0f && now > _dmgBuffUntil)   { _dmgBuffUntil = 0f;   if (_player.Weapon != null) _player.Weapon.DamageMultiplier = 1f; }
            if (_fireBuffUntil  > 0f && now > _fireBuffUntil)  { _fireBuffUntil = 0f;  if (_player.Weapon != null) _player.Weapon.FireRateMultiplier = 1f; }
            if (_speedBuffUntil > 0f && now > _speedBuffUntil) { _speedBuffUntil = 0f; _player.SpeedMultiplier = 1f; }
            if (_invulnUntil    > 0f && now > _invulnUntil)    { _invulnUntil = 0f;    _health.SetInvulnerable(false); }
            if (_camoUntil      > 0f && now > _camoUntil)      { _camoUntil = 0f;      _player.SetBodyVisible(true); }
            if (_infAmmoUntil   > 0f && now > _infAmmoUntil)   { _infAmmoUntil = 0f;   if (_player.Weapon != null) _player.Weapon.InfiniteAmmo = false; }

            if (_regenUntil > 0f)
            {
                if (now <= _regenUntil) { if (Time.frameCount % 30 == 0) _health.Heal(_regenPerTick); }
                else _regenUntil = 0f;
            }
        }

        // ── Effets ─────────────────────────────────────────────────────────

        private void HealPercent(float pct) => _health.Heal(Mathf.RoundToInt(_health.Max * pct));

        private void Regen(float pctPerSecond, float duration)
        {
            _regenPerTick = Mathf.Max(1, Mathf.RoundToInt(_health.Max * pctPerSecond / 2f)); // ~2 ticks/s
            _regenUntil = Time.time + duration;
        }

        private void BuffDamage(float mult, float duration)
        {
            if (_player.Weapon != null) _player.Weapon.DamageMultiplier = mult;
            _dmgBuffUntil = Time.time + duration;
        }

        private void BuffFireRate(float mult, float duration)
        {
            if (_player.Weapon != null) _player.Weapon.FireRateMultiplier = mult;
            _fireBuffUntil = Time.time + duration;
        }

        private void BuffSpeed(float mult, float duration)
        {
            _player.SpeedMultiplier = mult;
            _speedBuffUntil = Time.time + duration;
        }

        private void Invuln(float duration)
        {
            _health.SetInvulnerable(true);
            _invulnUntil = Time.time + duration;
        }

        private void Camouflage(float duration)
        {
            _player.SetBodyVisible(false);
            _camoUntil = Time.time + duration;
        }

        private void InfiniteAmmo(float duration)
        {
            if (_player.Weapon != null) _player.Weapon.InfiniteAmmo = true;
            _infAmmoUntil = Time.time + duration;
        }

        private void Dash(float distance)
        {
            var dir = transform.forward; dir.y = 0f; dir.Normalize();
            _cc.Move(dir * distance);
        }

        private void CastShieldWall(float duration)
        {
            // Mur plat devant le joueur, bloque tirs/NPCs, disparaît après duration.
            SpawnWall(transform.position + transform.forward * 2f, transform.forward, duration);
        }

        private void CastFortress(float duration)
        {
            // 4 murs autour du joueur.
            SpawnWall(transform.position + transform.forward  * 2f,  transform.forward,  duration);
            SpawnWall(transform.position - transform.forward  * 2f, -transform.forward,  duration);
            SpawnWall(transform.position + transform.right    * 2f,  transform.right,    duration);
            SpawnWall(transform.position - transform.right    * 2f, -transform.right,    duration);
        }

        private void SpawnWall(Vector3 pos, Vector3 normal, float duration)
        {
            var root = new GameObject("ShieldWall");
            root.transform.position = pos + Vector3.up * 1.2f;
            root.transform.rotation = Quaternion.LookRotation(normal);

            // Collider qui bloque les tirs/NPCs (indépendant du visuel).
            var box = root.AddComponent<BoxCollider>();
            box.size = new Vector3(3f, 2.4f, 0.3f);

            if (_shieldModel != null)
            {
                // Vrai modèle 3D de bouclier (réutilise l'asset power-up).
                var model = Instantiate(_shieldModel, root.transform);
                model.transform.localPosition = Vector3.zero;
                model.transform.localRotation = Quaternion.identity;
                foreach (var c in model.GetComponentsInChildren<Collider>(true)) Destroy(c);
                NormalizeHeight(model, 2.4f);
            }
            else
            {
                // Fallback : panneau cyan translucide.
                var quad = GameObject.CreatePrimitive(PrimitiveType.Cube);
                Destroy(quad.GetComponent<Collider>());
                quad.transform.SetParent(root.transform, false);
                quad.transform.localScale = new Vector3(3f, 2.4f, 0.2f);
                var r = quad.GetComponent<MeshRenderer>();
                var c = new Color(0.3f, 0.8f, 1f, 0.6f);
                var mat = new Material(r.sharedMaterial) { color = c };
                mat.SetColor("_BaseColor", c);
                mat.SetColor("_Color", c);
                mat.EnableKeyword("_EMISSION");
                mat.SetColor("_EmissionColor", new Color(0.3f, 0.8f, 1f) * 1.5f);
                r.sharedMaterial = mat;
            }

            Destroy(root, duration);
        }

        // Normalise un modèle instancié à une hauteur cible (mesh poly.pizza variés).
        private static void NormalizeHeight(GameObject inst, float targetHeight)
        {
            var renderers = inst.GetComponentsInChildren<Renderer>();
            if (renderers.Length == 0) return;
            var b = renderers[0].bounds;
            for (var i = 1; i < renderers.Length; i++) b.Encapsulate(renderers[i].bounds);
            if (b.size.y < 1e-4f) return;
            inst.transform.localScale *= targetHeight / b.size.y;
        }

        private void ExplodeAhead(float distance, float radius, int damage)
            => Explode(transform.position + transform.forward * distance, radius, damage);

        private void Barrage(float zoneRadius, int count, int dmgEach)
        {
            for (var i = 0; i < count; i++)
            {
                var p = transform.position + new Vector3(
                    Random.Range(-zoneRadius, zoneRadius), 0f, Random.Range(-zoneRadius, zoneRadius));
                Explode(p, 5f, dmgEach);
            }
        }

        private void Explode(Vector3 pos, float radius, int damage)
        {
            var hits = Physics.OverlapSphere(pos, radius);
            foreach (var h in hits)
            {
                var hs = h.GetComponentInParent<HealthSystem>();
                if (hs == null || hs == _health || hs.IsDead) continue;
                hs.TakeDamage(damage);
            }
            SpawnBlast(pos, radius, new Color(1f, 0.55f, 0.1f));
        }

        private void OrbitalStrike()
        {
            // Raycast depuis le centre de l'écran : foudroie la cible visée.
            var cam = Camera.main;
            if (cam == null) return;
            var ray = cam.ScreenPointToRay(new Vector3(Screen.width * 0.5f, Screen.height * 0.5f, 0f));
            var hitList = Physics.RaycastAll(ray, 200f);
            System.Array.Sort(hitList, (a, b) => a.distance.CompareTo(b.distance));
            foreach (var h in hitList)
            {
                var hs = h.collider.GetComponentInParent<HealthSystem>();
                if (hs == _health) continue;
                if (hs != null && !hs.IsDead)
                {
                    SpawnBeam(hs.transform.position);
                    Explode(hs.transform.position, 6f, 9999);
                }
                break;
            }
        }

        private void StunAround(float radius, float duration)
        {
            foreach (var h in Physics.OverlapSphere(transform.position, radius))
                h.GetComponentInParent<OperatorNpcController>()?.Stun(duration);
            SpawnBlast(transform.position, radius, new Color(0.4f, 0.9f, 1f));
        }

        private void StunAll(float duration)
        {
            foreach (var npc in FindObjectsByType<OperatorNpcController>(FindObjectsInactive.Exclude))
                npc.Stun(duration);
            SpawnBlast(transform.position, 4f, new Color(0.6f, 0.3f, 1f));
        }

        // ── FX ─────────────────────────────────────────────────────────────

        private void SpawnBlast(Vector3 pos, float radius, Color c)
        {
            var fx = GameObject.CreatePrimitive(PrimitiveType.Sphere);
            fx.name = "AbilityFx";
            Destroy(fx.GetComponent<Collider>());
            fx.transform.position = pos + Vector3.up * 0.5f;
            fx.transform.localScale = Vector3.one * 0.5f;
            var r = fx.GetComponent<MeshRenderer>();
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            r.sharedMaterial = mat;
            fx.AddComponent<PowerUps.MegaBombFx>().Init(radius);
        }

        private void SpawnBeam(Vector3 groundPos)
        {
            var beam = GameObject.CreatePrimitive(PrimitiveType.Cylinder);
            beam.name = "OrbitalBeam";
            Destroy(beam.GetComponent<Collider>());
            beam.transform.position = groundPos + Vector3.up * 15f;
            beam.transform.localScale = new Vector3(1.2f, 15f, 1.2f);
            var r = beam.GetComponent<MeshRenderer>();
            var c = new Color(0.4f, 0.9f, 1f, 1f);
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.SetColor("_BaseColor", c);
            mat.EnableKeyword("_EMISSION");
            mat.SetColor("_EmissionColor", c * 3f);
            r.sharedMaterial = mat;
            Destroy(beam, 0.4f);
        }
    }
}
