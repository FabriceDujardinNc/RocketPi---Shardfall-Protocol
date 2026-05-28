// HideSeekManager.cs — Mode "infiltration / Où est Charlie".
//
// Boucle : une foule de FAUX opérateurs (NPC) ne font que MARCHER. Parmi eux se
// cache un IMPOSTEUR (ici une IA simulant un vrai joueur ; en multi ce sera un
// vrai joueur). L'imposteur marche pour se fondre, mais se trahit en COURANT par
// moments. Objectif du joueur : repérer et éliminer l'imposteur.
//
//   • Tuer l'IMPOSTEUR  → gros score (réussite).
//   • Tuer un FAUX op   → le joueur PERD de la vie (mauvaise cible).
//   • Tir + sorts uniquement en COURANT (cf. PlayerController.IsRunning) :
//     marcher = se cacher, courir = agir mais s'exposer.
//
// Singleton léger posé en scène par "Tools > RocketPi > Setup Hide & Seek".
// Quand le netcode (Photon) arrivera, l'imposteur IA sera remplacé par les vrais
// joueurs — cette logique (pénalité/score/gating) reste valable.

using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.Gameplay.Match
{
    public class HideSeekManager : MonoBehaviour
    {
        [SerializeField] private int _decoyKillPenalty  = 35;   // vie perdue si on tue un faux op
        [SerializeField] private int _impostorKillScore = 500;  // score si on trouve l'imposteur

        public static HideSeekManager Instance { get; private set; }

        private PlayerController _player;
        private TrainingMatchManager _match;
        private Font _font;
        private Text _objective, _flash;
        private float _flashUntil;
        private int _impostorsFound;

        private void Awake()
        {
            Instance = this;
            _font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            BuildHud();
        }

        private void OnDestroy() { if (Instance == this) Instance = null; }

        private void Start()
        {
            _player = FindAnyObjectByType<PlayerController>();
            _match  = FindAnyObjectByType<TrainingMatchManager>();
        }

        /// <summary>Appelé par OperatorNpcController quand le joueur abat un opérateur.</summary>
        public void OnOperatorKilled(bool isImpostor)
        {
            if (isImpostor)
            {
                _impostorsFound++;
                _match?.RegisterKill(_impostorKillScore);
                Flash($"IMPOSTEUR DÉMASQUÉ !  +{_impostorKillScore}", new Color(0.3f, 1f, 0.5f));
            }
            else
            {
                _player?.Health.TakeDamage(_decoyKillPenalty);
                Flash($"Mauvaise cible — c'était un civil !  -{_decoyKillPenalty} PV", new Color(1f, 0.4f, 0.3f));
            }
        }

        /// <summary>Marque la position de tous les imposteurs pendant <paramref name="duration"/>
        /// secondes (faisceau rouge qui les suit). Déclenché par le bonus RevealImpostor.</summary>
        public void RevealImpostors(float duration)
        {
            var npcs = FindObjectsByType<Rocketpi.Gameplay.NPC.OperatorNpcController>(FindObjectsInactive.Exclude);
            int n = 0;
            foreach (var npc in npcs)
                if (npc.IsImpostor) { SpawnRevealMarker(npc.transform, duration); n++; }
            Flash(n > 0 ? "📡 Imposteur localisé (2 s) !" : "Aucun imposteur en vue",
                  new Color(1f, 0.85f, 0.2f));
        }

        private void SpawnRevealMarker(Transform target, float duration)
        {
            var marker = GameObject.CreatePrimitive(PrimitiveType.Cylinder);
            var col = marker.GetComponent<Collider>();
            if (col != null) Destroy(col);
            marker.name = "ImpostorMarker";
            marker.transform.SetParent(target, false);
            marker.transform.localPosition = new Vector3(0f, 6f, 0f);   // au-dessus de la tête
            marker.transform.localScale = new Vector3(0.4f, 4f, 0.4f);
            var r = marker.GetComponent<MeshRenderer>();
            var c = new Color(1f, 0.12f, 0.12f);
            var mat = new Material(r.sharedMaterial) { color = c };
            mat.EnableKeyword("_EMISSION");
            mat.SetColor("_EmissionColor", c * 2.5f);
            mat.SetColor("_BaseColor", c);
            mat.SetColor("_Color", c);
            r.sharedMaterial = mat;
            r.shadowCastingMode = UnityEngine.Rendering.ShadowCastingMode.Off;
            Destroy(marker, duration);
        }

        private void Update()
        {
            if (_objective != null)
                _objective.text = "INFILTRATION  •  Marche pour te cacher, COURS pour tirer\n" +
                                  "Trouve l'imposteur (il se trahit en courant) — ne tire pas sur les civils";
            if (_flash != null)
            {
                var on = Time.time < _flashUntil;
                _flash.enabled = on;
            }
        }

        private void Flash(string msg, Color c)
        {
            if (_flash == null) return;
            _flash.text = msg;
            _flash.color = c;
            _flash.enabled = true;
            _flashUntil = Time.time + 2.5f;
        }

        private void BuildHud()
        {
            var go = new GameObject("HideSeekHudCanvas");
            go.transform.SetParent(transform, false);
            var canvas = go.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvas.sortingOrder = 60;
            var scaler = go.AddComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1920, 1080);

            _objective = NewText(go.transform, "Objective", 26, TextAnchor.UpperCenter,
                new Vector2(0.5f, 1f), new Vector2(0f, -16f), new Vector2(1100f, 70f));
            _objective.color = new Color(0.85f, 0.9f, 1f);

            _flash = NewText(go.transform, "Flash", 40, TextAnchor.MiddleCenter,
                new Vector2(0.5f, 0.5f), new Vector2(0f, 220f), new Vector2(1200f, 70f));
            _flash.fontStyle = FontStyle.Bold;
            _flash.enabled = false;
        }

        private Text NewText(Transform parent, string name, int size, TextAnchor anchor,
            Vector2 anchorPivot, Vector2 pos, Vector2 sizeDelta)
        {
            var go = new GameObject(name, typeof(RectTransform));
            go.transform.SetParent(parent, false);
            var t = go.AddComponent<Text>();
            t.font = _font; t.fontSize = size; t.alignment = anchor;
            t.horizontalOverflow = HorizontalWrapMode.Overflow;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            var rt = t.rectTransform;
            rt.anchorMin = rt.anchorMax = rt.pivot = anchorPivot;
            rt.anchoredPosition = pos;
            rt.sizeDelta = sizeDelta;
            return t;
        }
    }
}
