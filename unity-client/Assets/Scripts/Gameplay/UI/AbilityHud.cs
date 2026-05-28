// AbilityHud.cs — Jauges de cooldown en bas de l'écran pour la capacité de classe
// (F) et l'ultime (R). Chaque jauge se remplit du bas pendant la recharge ; verte
// = prête. Construit son UI en code.

using Rocketpi.Gameplay.Abilities;
using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.Gameplay.UI
{
    public class AbilityHud : MonoBehaviour
    {
        private PlayerAbilities _abilities;
        private Image _classFill, _ultFill;
        private Text _castLabel;
        private Font _font;
        private static Sprite _white;

        private static readonly Color Ready    = new(0.25f, 0.9f, 0.45f, 0.95f); // vert prêt
        private static readonly Color Charging = new(0.45f, 0.55f, 0.7f, 0.6f);  // gris en charge

        private void Awake()
        {
            _font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            BuildUI();
        }

        private void Start() => _abilities = FindAnyObjectByType<PlayerAbilities>();

        private void Update()
        {
            if (_abilities == null) { _abilities = FindAnyObjectByType<PlayerAbilities>(); return; }
            UpdateGauge(_classFill, _abilities.ClassReadyFraction);
            UpdateGauge(_ultFill,   _abilities.UltReadyFraction);

            // Feedback texte du dernier sort lancé (2s).
            if (_castLabel != null)
            {
                var since = Time.time - _abilities.LastCastAt;
                if (since < 2f)
                {
                    _castLabel.text = _abilities.LastCast;
                    var a = Mathf.Clamp01(1f - since / 2f);
                    _castLabel.color = new Color(0.3f, 0.9f, 1f, a);
                }
                else _castLabel.text = "";
            }
        }

        private static void UpdateGauge(Image fill, float fraction)
        {
            if (fill == null) return;
            fill.fillAmount = Mathf.Clamp01(fraction);
            fill.color = fraction >= 0.999f ? Ready : Charging;
        }

        private static Sprite White()
        {
            if (_white == null)
            {
                var tex = Texture2D.whiteTexture;
                _white = Sprite.Create(tex, new Rect(0, 0, tex.width, tex.height), new Vector2(0.5f, 0.5f), 100f);
            }
            return _white;
        }

        private void BuildUI()
        {
            var canvasGo = new GameObject("AbilityHudCanvas");
            canvasGo.transform.SetParent(transform, false);
            var canvas = canvasGo.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvas.sortingOrder = 50;
            var scaler = canvasGo.AddComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1920, 1080);

            // Jauges positionnées en absolu (bas-centre) — pas de LayoutGroup
            // (childControlHeight donnait une hauteur 0 → jauges invisibles).
            _classFill = BuildGauge(canvasGo.transform, "F", new Vector2(-60f, 50f));
            _ultFill   = BuildGauge(canvasGo.transform, "R", new Vector2( 60f, 50f));

            // Label du sort lancé (flash au-dessus des jauges).
            _castLabel = NewText(canvasGo.transform, "CastLabel", "", 30, TextAnchor.MiddleCenter);
            var clrt = _castLabel.rectTransform;
            clrt.anchorMin = new Vector2(0.5f, 0f); clrt.anchorMax = new Vector2(0.5f, 0f);
            clrt.pivot = new Vector2(0.5f, 0f);
            clrt.anchoredPosition = new Vector2(0f, 150f);
            clrt.sizeDelta = new Vector2(700f, 44f);
            _castLabel.fontStyle = FontStyle.Bold;
        }

        private Image BuildGauge(Transform parent, string letter, Vector2 anchoredPos)
        {
            var holder = new GameObject($"Gauge_{letter}", typeof(RectTransform));
            holder.transform.SetParent(parent, false);
            var hrt = holder.GetComponent<RectTransform>();
            hrt.anchorMin = new Vector2(0.5f, 0f);
            hrt.anchorMax = new Vector2(0.5f, 0f);
            hrt.pivot = new Vector2(0.5f, 0f);
            hrt.anchoredPosition = anchoredPos;
            hrt.sizeDelta = new Vector2(84f, 84f);

            // Fond
            var bg = NewImage(holder.transform, "BG", new Color(0.05f, 0.06f, 0.09f, 0.85f));
            Stretch(bg.rectTransform);

            // Remplissage (du bas vers le haut)
            var fill = NewImage(holder.transform, "Fill", Charging);
            Stretch(fill.rectTransform);
            fill.sprite = White();
            fill.type = Image.Type.Filled;
            fill.fillMethod = Image.FillMethod.Vertical;
            fill.fillOrigin = (int)Image.OriginVertical.Bottom;
            fill.fillAmount = 1f;

            // Lettre
            var label = NewText(holder.transform, "Letter", letter, 44, TextAnchor.MiddleCenter);
            Stretch(label.rectTransform);
            label.color = Color.white;
            label.fontStyle = FontStyle.Bold;

            return fill;
        }

        // ── Helpers ────────────────────────────────────────────────────────
        private static void Stretch(RectTransform rt)
        {
            rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one;
            rt.offsetMin = Vector2.zero; rt.offsetMax = Vector2.zero;
        }

        private Image NewImage(Transform parent, string name, Color color)
        {
            var go = new GameObject(name, typeof(RectTransform));
            go.transform.SetParent(parent, false);
            var img = go.AddComponent<Image>();
            img.color = color;
            return img;
        }

        private Text NewText(Transform parent, string name, string content, int size, TextAnchor anchor)
        {
            var go = new GameObject(name, typeof(RectTransform));
            go.transform.SetParent(parent, false);
            var t = go.AddComponent<Text>();
            t.font = _font; t.text = content; t.fontSize = size; t.alignment = anchor;
            return t;
        }
    }
}
