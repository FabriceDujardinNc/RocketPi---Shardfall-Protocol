// PowerUpPickup.cs — Bonus ramassable : flotte, tourne, et applique son effet
// au joueur qui le touche, puis réapparaît après un délai.
//
// Génère son propre visuel coloré (cube) en code, avec une couleur par type.
// Collider trigger pour détecter le joueur (CharacterController).

using UnityEngine;

namespace Rocketpi.Gameplay.PowerUps
{
    public class PowerUpPickup : MonoBehaviour
    {
        [SerializeField] private PowerUpType _type = PowerUpType.Shield;
        [Tooltip("Vrai modèle 3D (.glb/.fbx/prefab). Si vide → icône primitive fallback.")]
        [SerializeField] private GameObject _model;
        [Tooltip("Hauteur cible (m) : tous les modèles sont mis à cette hauteur, peu " +
                 "importe leur taille native, pour une apparence uniforme.")]
        [SerializeField] private float _modelHeight = 0.9f;
        [SerializeField] private float _respawnDelay = 15f;
        [SerializeField] private float _triggerRadius = 1.2f;

        [Header("Animation")]
        [SerializeField] private float _spinSpeed = 90f;     // deg/s
        [SerializeField] private float _bobAmplitude = 0.35f;
        [SerializeField] private float _bobSpeed = 2f;

        private Transform _visual;
        private SphereCollider _trigger;
        private Vector3 _visualBaseLocalPos;
        private bool _consumed;

        public PowerUpType Type => _type;
        public void SetType(PowerUpType t) => _type = t;

        private void Awake()
        {
            _trigger = GetComponent<SphereCollider>();
            if (_trigger == null) _trigger = gameObject.AddComponent<SphereCollider>();
            _trigger.isTrigger = true;
            _trigger.radius = _triggerRadius;

            BuildVisual();
        }

        private void BuildVisual()
        {
            var holder = new GameObject("Visual");
            holder.transform.SetParent(transform, false);
            holder.transform.localPosition = new Vector3(0f, 1f, 0f);
            _visual = holder.transform;
            _visualBaseLocalPos = _visual.localPosition;

            // Faisceau de mise en valeur (pilier de lumière émissif, visible de loin).
            BuildBeam(ColorFor(_type));

            if (_model != null)
            {
                // Vrai modèle 3D : instancié puis normalisé à une hauteur commune.
                var inst = Instantiate(_model, holder.transform);
                inst.transform.localPosition = Vector3.zero;
                inst.transform.localRotation = Quaternion.identity;
                inst.transform.localScale = Vector3.one;
                foreach (var col in inst.GetComponentsInChildren<Collider>(true))
                    Destroy(col);
                NormalizeToHeight(inst, holder.transform, _modelHeight);
            }
            else
            {
                // Fallback : icône composée de primitives.
                BuildIcon(_type, holder.transform, ColorFor(_type));
            }
        }

        // Met le modèle à la hauteur cible (uniformise les tailles natives très
        // variables des modèles poly.pizza) et le recentre sur le holder.
        private static void NormalizeToHeight(GameObject inst, Transform holder, float targetHeight)
        {
            var renderers = inst.GetComponentsInChildren<Renderer>();
            if (renderers.Length == 0) return;

            var b = renderers[0].bounds;
            for (var i = 1; i < renderers.Length; i++) b.Encapsulate(renderers[i].bounds);
            var h = b.size.y;
            if (h < 1e-4f) return;

            inst.transform.localScale = Vector3.one * (targetHeight / h);

            // Recentre : le centre du mesh est ramené sur l'origine du holder
            // → tous les pickups flottent à la même hauteur visuelle.
            var b2 = renderers[0].bounds;
            for (var i = 1; i < renderers.Length; i++) b2.Encapsulate(renderers[i].bounds);
            inst.transform.position += holder.position - b2.center;
        }

        // Pilier de lumière émissif sous le pickup — repérable de loin sur toute la map.
        private void BuildBeam(Color c)
        {
            var beam = GameObject.CreatePrimitive(PrimitiveType.Cylinder);
            var col = beam.GetComponent<Collider>();
            if (col != null) Destroy(col);
            beam.name = "Beam";
            beam.transform.SetParent(transform, false);
            beam.transform.localPosition = new Vector3(0f, 4f, 0f);
            beam.transform.localScale = new Vector3(0.18f, 4f, 0.18f);  // fin et haut
            var r = beam.GetComponent<MeshRenderer>();
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.EnableKeyword("_EMISSION");
            mat.SetColor("_EmissionColor", c * 2.2f);
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            r.sharedMaterial = mat;
            r.shadowCastingMode = UnityEngine.Rendering.ShadowCastingMode.Off;
        }

        // Icône 3D composée de primitives, reconnaissable par type.
        private void BuildIcon(PowerUpType type, Transform parent, Color c)
        {
            switch (type)
            {
                case PowerUpType.HealthPack: // croix médicale
                    AddPrim(parent, PrimitiveType.Cube, Vector3.zero, new Vector3(0.22f, 0.66f, 0.22f), c);
                    AddPrim(parent, PrimitiveType.Cube, Vector3.zero, new Vector3(0.66f, 0.22f, 0.22f), c);
                    break;

                case PowerUpType.MegaBomb: // sphère + mèche
                    AddPrim(parent, PrimitiveType.Sphere, Vector3.zero, Vector3.one * 0.6f, c);
                    AddPrim(parent, PrimitiveType.Cylinder, new Vector3(0f, 0.42f, 0f),
                        new Vector3(0.06f, 0.14f, 0.06f), new Color(1f, 0.85f, 0.3f));
                    break;

                case PowerUpType.Shield: // écu bombé (capsule aplatie)
                    AddPrim(parent, PrimitiveType.Capsule, Vector3.zero, new Vector3(0.6f, 0.4f, 0.16f), c);
                    break;

                case PowerUpType.QuadDamage: // diamant (cube tourné)
                    var d = AddPrim(parent, PrimitiveType.Cube, Vector3.zero, Vector3.one * 0.5f, c);
                    d.localRotation = Quaternion.Euler(45f, 0f, 45f);
                    break;

                case PowerUpType.SpeedBoost: // flèche (capsule couchée)
                    var s = AddPrim(parent, PrimitiveType.Capsule, Vector3.zero, new Vector3(0.22f, 0.45f, 0.22f), c);
                    s.localRotation = Quaternion.Euler(90f, 0f, 0f);
                    break;

                case PowerUpType.RapidFire: // rafale (3 sphères)
                    AddPrim(parent, PrimitiveType.Sphere, new Vector3(-0.22f, 0f, 0f), Vector3.one * 0.24f, c);
                    AddPrim(parent, PrimitiveType.Sphere, Vector3.zero, Vector3.one * 0.24f, c);
                    AddPrim(parent, PrimitiveType.Sphere, new Vector3(0.22f, 0f, 0f), Vector3.one * 0.24f, c);
                    break;

                case PowerUpType.Shockwave: // anneau plat (disque)
                    AddPrim(parent, PrimitiveType.Cylinder, Vector3.zero, new Vector3(0.55f, 0.06f, 0.55f), c);
                    break;

                case PowerUpType.BouncingBullets: // bille
                default:
                    AddPrim(parent, PrimitiveType.Sphere, Vector3.zero, Vector3.one * 0.5f, c);
                    break;
            }
        }

        private Transform AddPrim(Transform parent, PrimitiveType prim, Vector3 localPos, Vector3 scale, Color c)
        {
            var go = GameObject.CreatePrimitive(prim);
            DestroyImmediate(go.GetComponent<Collider>());
            go.transform.SetParent(parent, false);
            go.transform.localPosition = localPos;
            go.transform.localScale = scale;

            var r = go.GetComponent<MeshRenderer>();
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            mat.EnableKeyword("_EMISSION");
            mat.SetColor("_EmissionColor", c * 1.5f);
            r.sharedMaterial = mat;
            return go.transform;
        }

        private static Color ColorFor(PowerUpType t) => t switch
        {
            PowerUpType.Shield          => new Color(0.30f, 0.80f, 1.00f),  // cyan
            PowerUpType.MegaBomb        => new Color(1.00f, 0.35f, 0.15f),  // orange-rouge
            PowerUpType.BouncingBullets => new Color(1.00f, 0.92f, 0.25f),  // jaune
            PowerUpType.RapidFire       => new Color(1.00f, 0.55f, 0.10f),  // orange
            PowerUpType.QuadDamage      => new Color(0.75f, 0.30f, 1.00f),  // violet
            PowerUpType.SpeedBoost      => new Color(0.30f, 0.95f, 0.45f),  // vert
            PowerUpType.HealthPack      => new Color(0.95f, 0.95f, 0.95f),  // blanc
            PowerUpType.Shockwave       => new Color(0.30f, 0.75f, 1.00f),  // bleu électrique
            PowerUpType.AmmoRefill      => new Color(1.00f, 0.80f, 0.20f),  // ambre
            PowerUpType.RevealImpostor  => new Color(1.00f, 0.20f, 0.20f),  // rouge radar
            _                           => Color.white,
        };

        private void Update()
        {
            if (_visual == null || _consumed) return;
            _visual.localRotation = Quaternion.Euler(0f, Time.time * _spinSpeed, 0f);
            var bob = Mathf.Sin(Time.time * _bobSpeed) * _bobAmplitude;
            _visual.localPosition = _visualBaseLocalPos + new Vector3(0f, bob, 0f);
        }

        private void OnTriggerEnter(Collider other)
        {
            if (_consumed) return;
            var pp = other.GetComponentInParent<PlayerPowerUps>();
            if (pp == null) return;

            pp.Apply(_type);
            Consume();
        }

        private void Consume()
        {
            _consumed = true;
            if (_visual != null) _visual.gameObject.SetActive(false);
            _trigger.enabled = false;
            Invoke(nameof(Respawn), _respawnDelay);
        }

        private void Respawn()
        {
            _consumed = false;
            if (_visual != null) _visual.gameObject.SetActive(true);
            _trigger.enabled = true;
        }

        private void OnDrawGizmosSelected()
        {
            Gizmos.color = ColorFor(_type);
            Gizmos.DrawWireSphere(transform.position + Vector3.up, _triggerRadius);
        }
    }
}
