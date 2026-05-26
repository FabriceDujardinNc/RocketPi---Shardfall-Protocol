// WorldHealthBar.cs — Barre de vie flottante au-dessus d'une entité.
//
// Implémentée avec des quads MeshRenderer + matériau URP/Unlit (rendu garanti
// en URP, contrairement à un Canvas world-space capricieux). Le quad de
// remplissage est scalé en X et ancré à gauche pour se vider vers la droite.
// Billboard vers la caméra chaque frame.
//
// Posé sur le même GameObject que le HealthSystem (NPC root ou joueur).

using UnityEngine;

namespace Rocketpi.Gameplay.NPC
{
    [RequireComponent(typeof(HealthSystem))]
    public class WorldHealthBar : MonoBehaviour
    {
        [Header("Placement / taille")]
        [SerializeField] private float _height = 2.3f;     // hauteur au-dessus du pivot (m)
        [SerializeField] private float _width  = 1.1f;
        [SerializeField] private float _thickness = 0.18f;

        [Header("Couleurs")]
        [SerializeField] private Color _backColor = new(0.05f, 0.05f, 0.05f, 1f);
        [SerializeField] private Color _fullColor = new(0.25f, 0.9f, 0.35f, 1f);
        [SerializeField] private Color _lowColor  = new(0.95f, 0.2f, 0.15f, 1f);

        private HealthSystem _health;
        private Transform _root;
        private Transform _fill;
        private Material  _fillMat;
        private Camera _cam;

        private void Awake()
        {
            _health = GetComponent<HealthSystem>();
            BuildBar();
        }

        private void OnEnable()
        {
            if (_health != null)
            {
                _health.OnHealthChanged += HandleHealthChanged;
                _health.OnDied += HandleDied;
            }
        }

        private void OnDisable()
        {
            if (_health != null)
            {
                _health.OnHealthChanged -= HandleHealthChanged;
                _health.OnDied -= HandleDied;
            }
        }

        private void Start()
        {
            _cam = Camera.main;
            Refresh(_health.Current, _health.Max);
        }

        private void BuildBar()
        {
            _root = new GameObject("HealthBar").transform;
            _root.SetParent(transform, false);
            _root.localPosition = new Vector3(0f, _height, 0f);

            // Fond (légèrement plus grand, derrière)
            var back = GameObject.CreatePrimitive(PrimitiveType.Quad);
            back.name = "Back";
            DestroyImmediate(back.GetComponent<Collider>());
            back.transform.SetParent(_root, false);
            back.transform.localPosition = new Vector3(0f, 0f, 0.01f);
            back.transform.localScale = new Vector3(_width + 0.05f, _thickness + 0.05f, 1f);
            SetColor(back.GetComponent<MeshRenderer>(), _backColor);

            // Remplissage : pivot ancré à gauche via un parent décalé.
            // On crée un "anchor" placé au bord gauche, et le quad fill grandit
            // vers la droite depuis cet anchor.
            var anchor = new GameObject("FillAnchor").transform;
            anchor.SetParent(_root, false);
            anchor.localPosition = new Vector3(-_width * 0.5f, 0f, 0f);

            var fill = GameObject.CreatePrimitive(PrimitiveType.Quad);
            fill.name = "Fill";
            DestroyImmediate(fill.GetComponent<Collider>());
            fill.transform.SetParent(anchor, false);
            // Quad par défaut centré : on le décale de +0.5 en X pour que son bord
            // gauche soit sur l'anchor, puis on scale l'anchor en X.
            fill.transform.localPosition = new Vector3(0.5f, 0f, 0f);
            fill.transform.localScale = new Vector3(1f, _thickness, 1f);
            var fillRenderer = fill.GetComponent<MeshRenderer>();
            SetColor(fillRenderer, _fullColor);
            _fillMat = fillRenderer.sharedMaterial;   // material cloné par SetColor

            _fill = anchor; // on scale l'anchor en X (0.._width)
        }

        /// <summary>
        /// Clone le matériau par défaut du primitive (shader URP garanti présent,
        /// évite le magenta de Shader.Find raté au runtime) et applique la couleur.
        /// </summary>
        private static void SetColor(MeshRenderer r, Color c)
        {
            var mat = new Material(r.sharedMaterial);
            mat.color = c;
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            r.sharedMaterial = mat;
        }

        private void HandleHealthChanged(int current, int max) => Refresh(current, max);

        private void HandleDied()
        {
            if (_root != null) _root.gameObject.SetActive(false);
        }

        private void Refresh(int current, int max)
        {
            if (_fill == null || max <= 0) return;
            // Réaffiche la barre si elle avait été masquée à la mort et que l'entité revit.
            if (_root != null && !_root.gameObject.activeSelf && current > 0)
                _root.gameObject.SetActive(true);
            var ratio = Mathf.Clamp01((float)current / max);
            // L'anchor scale en X de 0 à _width ; le quad fill (largeur locale 1,
            // décalé +0.5) grandit vers la droite depuis le bord gauche.
            _fill.localScale = new Vector3(_width * ratio, 1f, 1f);
            if (_fillMat != null)
            {
                var c = Color.Lerp(_lowColor, _fullColor, ratio);
                _fillMat.color = c;
                _fillMat.SetColor("_BaseColor", c);
                _fillMat.SetColor("_Color", c);
            }
        }

        private void LateUpdate()
        {
            if (_root == null) return;
            if (_cam == null) { _cam = Camera.main; if (_cam == null) return; }
            _root.rotation = Quaternion.LookRotation(_root.position - _cam.transform.position);
        }
    }
}
