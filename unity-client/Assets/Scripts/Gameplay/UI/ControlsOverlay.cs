// ControlsOverlay.cs — Panneau d'aide listant toutes les touches du jeu.
// Ouvre/ferme avec la touche I (toggle). Construit son UI en code (Canvas overlay).
//
// ⚠️ MAINTENIR À JOUR : ajouter ici chaque nouvelle touche (idem encart /play).

using UnityEngine;
using UnityEngine.InputSystem;
using UnityEngine.UI;

namespace Rocketpi.Gameplay.UI
{
    public class ControlsOverlay : MonoBehaviour
    {
        // (touche, action) — source unique des contrôles affichés in-game.
        private static readonly (string key, string action)[] Controls =
        {
            ("Z Q S D / W A S D", "Se déplacer"),
            ("Maj. gauche",       "Courir (en avançant)"),
            ("Espace",            "Sauter"),
            ("Souris",            "Tourner la caméra / viser"),
            ("Molette",           "Zoom caméra"),
            ("Clic gauche",       "Tirer"),
            ("Clic droit",        "Tir secondaire"),
            ("F",                 "Capacité de classe (sort)"),
            ("R",                 "Ultime de l'opérateur"),
            ("V",                 "Vue 1ère / 3ème personne"),
            ("I",                 "Afficher / masquer cette aide"),
            ("Échap",             "Libérer la souris"),
            ("Marcher dessus",    "Ramasser un bonus"),
        };

        private GameObject _canvasGo;
        private bool _visible;
        private InputAction _toggle;
        private Font _font;

        private void Awake()
        {
            _font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            _toggle = new InputAction("ToggleControls", binding: "<Keyboard>/i");
            BuildUI();
            SetVisible(false);
        }

        private void OnEnable()  => _toggle.Enable();
        private void OnDisable() => _toggle.Disable();

        private void Update()
        {
            if (_toggle.WasPressedThisFrame()) SetVisible(!_visible);
        }

        private void SetVisible(bool v)
        {
            _visible = v;
            if (_canvasGo != null) _canvasGo.SetActive(v);
        }

        private void BuildUI()
        {
            _canvasGo = new GameObject("ControlsOverlayCanvas");
            var canvas = _canvasGo.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvas.sortingOrder = 90;
            var scaler = _canvasGo.AddComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1920, 1080);
            _canvasGo.AddComponent<GraphicRaycaster>();

            // Voile sombre
            var dim = NewImage(_canvasGo.transform, "Dim", new Color(0f, 0f, 0f, 0.55f));
            Stretch(dim.rectTransform);

            // Panneau central
            var panel = NewImage(_canvasGo.transform, "Panel", new Color(0.06f, 0.08f, 0.11f, 0.96f));
            var prt = panel.rectTransform;
            prt.anchorMin = prt.anchorMax = prt.pivot = new Vector2(0.5f, 0.5f);
            prt.sizeDelta = new Vector2(720f, 640f);

            // Titre
            var title = NewText(panel.transform, "Title", "CONTRÔLES", 44, TextAnchor.UpperCenter);
            var trt = title.rectTransform;
            trt.anchorMin = new Vector2(0f, 1f); trt.anchorMax = new Vector2(1f, 1f); trt.pivot = new Vector2(0.5f, 1f);
            trt.anchoredPosition = new Vector2(0f, -24f);
            trt.sizeDelta = new Vector2(0f, 60f);
            title.color = new Color(0.3f, 0.8f, 1f);

            // Liste
            var list = new GameObject("List", typeof(RectTransform));
            list.transform.SetParent(panel.transform, false);
            var lrt = list.GetComponent<RectTransform>();
            lrt.anchorMin = new Vector2(0f, 0f); lrt.anchorMax = new Vector2(1f, 1f);
            lrt.offsetMin = new Vector2(40f, 70f); lrt.offsetMax = new Vector2(-40f, -100f);
            var vlg = list.AddComponent<VerticalLayoutGroup>();
            vlg.spacing = 6f;
            vlg.childControlHeight = true; vlg.childControlWidth = true;
            vlg.childForceExpandHeight = false; vlg.childForceExpandWidth = true;

            foreach (var (key, action) in Controls)
                BuildRow(list.transform, key, action);

            // Pied
            var hint = NewText(panel.transform, "Hint", "Appuie sur I pour fermer", 20, TextAnchor.LowerCenter);
            var hrt = hint.rectTransform;
            hrt.anchorMin = new Vector2(0f, 0f); hrt.anchorMax = new Vector2(1f, 0f); hrt.pivot = new Vector2(0.5f, 0f);
            hrt.anchoredPosition = new Vector2(0f, 18f);
            hrt.sizeDelta = new Vector2(0f, 30f);
            hint.color = new Color(0.6f, 0.6f, 0.65f);
        }

        private void BuildRow(Transform parent, string key, string action)
        {
            var row = new GameObject("Row", typeof(RectTransform));
            row.transform.SetParent(parent, false);
            row.AddComponent<LayoutElement>().preferredHeight = 34f;
            var hlg = row.AddComponent<HorizontalLayoutGroup>();
            hlg.spacing = 14f;
            hlg.childControlWidth = true; hlg.childForceExpandWidth = false;
            hlg.childAlignment = TextAnchor.MiddleLeft;

            // Badge touche
            var badge = NewImage(row.transform, "Key", new Color(0.16f, 0.2f, 0.26f, 1f));
            badge.rectTransform.sizeDelta = new Vector2(0f, 30f);
            var be = badge.gameObject.AddComponent<LayoutElement>();
            be.preferredWidth = 230f; be.minWidth = 230f;
            var keyTxt = NewText(badge.transform, "K", key, 20, TextAnchor.MiddleCenter);
            Stretch(keyTxt.rectTransform);
            keyTxt.color = Color.white;

            // Action
            var act = NewText(row.transform, "Action", action, 22, TextAnchor.MiddleLeft);
            act.color = new Color(0.85f, 0.87f, 0.9f);
            act.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1f;
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
            t.font = _font;
            t.text = content;
            t.fontSize = size;
            t.alignment = anchor;
            t.horizontalOverflow = HorizontalWrapMode.Overflow;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            return t;
        }
    }
}
