// OperatorSelectionMenu.cs — Écran de sélection d'opérateur avec aperçu 3D.
//
// Cartes plus grandes affichant un mini-rendu 3D du modèle de l'opérateur (RenderTexture
// alimentée par une caméra dédiée pointée sur le BodyPrefab instancié dans une "preview
// room" hors-map). Le model tourne lentement pour montrer ses faces. La carte
// sélectionnée a une bordure colorée selon sa faction + un effet de glow par teinte.
//
// Cycle : menu ouvert → joueur gelé (CanPlay=false), curseur libre, caméra non interactive.
// Au clic JOUER → SetOperator, démarrage, room de preview détruite (libère les RT).

using System.Collections.Generic;
using Rocketpi.Gameplay.CameraControl;
using Rocketpi.Gameplay.Operators;
using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.Gameplay.UI
{
    public class OperatorSelectionMenu : MonoBehaviour
    {
        [SerializeField] private OperatorData[] _operators;
        [SerializeField] private PlayerController _player;
        [SerializeField] private ThirdPersonCamera _camera;

        private OperatorData _selected;
        private GameObject _canvasGo;
        private GameObject _previewRoom;
        private Font _font;
        private bool _isOpen;

        // ── État par carte ────────────────────────────────────────────────
        private class CardSlot
        {
            public OperatorData Op;
            public RectTransform Border;     // contour coloré
            public Image Background;         // fond carte
            public GameObject BodyInstance;  // instance du BodyPrefab dans la preview room
            public Camera PreviewCam;
            public RenderTexture Rt;
            public Text NameText;
        }
        private readonly List<CardSlot> _cards = new();

        public void Configure(OperatorData[] operators, PlayerController player, ThirdPersonCamera cam)
        {
            _operators = operators;
            _player = player;
            _camera = cam;
        }

        private void Start()
        {
            if (_player == null) _player = FindAnyObjectByType<PlayerController>();
            if (_camera == null) _camera = FindAnyObjectByType<ThirdPersonCamera>();
            _font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

            _selected = (_player != null && _player.Operator != null) ? _player.Operator
                      : (_operators != null && _operators.Length > 0 ? _operators[0] : null);

            BuildPreviewRoom();
            BuildUI();
            OpenMenu();
        }

        private void OnDestroy()
        {
            foreach (var c in _cards) if (c.Rt != null) c.Rt.Release();
        }

        private void OpenMenu()
        {
            _isOpen = true;
            _player?.SetMatchReady(false);
            _camera?.SetInteractive(false);
            Cursor.lockState = CursorLockMode.None;
            Cursor.visible = true;
            if (_canvasGo != null) _canvasGo.SetActive(true);
            if (_previewRoom != null) _previewRoom.SetActive(true);
        }

        private void StartMatch()
        {
            _isOpen = false;
            if (_selected != null) _player?.SetOperator(_selected);
            if (_canvasGo != null) _canvasGo.SetActive(false);
            // Libère les ressources de preview (caméras + RT + bodies).
            if (_previewRoom != null) Destroy(_previewRoom);
            foreach (var c in _cards) if (c.Rt != null) c.Rt.Release();
            _cards.Clear();
            _camera?.SetInteractive(true);
            _player?.SetMatchReady(true);
            Cursor.lockState = CursorLockMode.Locked;
            Cursor.visible = false;
        }

        private void Update()
        {
            if (_isOpen && Cursor.lockState != CursorLockMode.None)
            {
                Cursor.lockState = CursorLockMode.None;
                Cursor.visible = true;
            }
            if (!_isOpen) return;
            // Rotation lente des modèles (turntable) pour mettre en valeur.
            foreach (var c in _cards)
                if (c.BodyInstance != null)
                    c.BodyInstance.transform.Rotate(Vector3.up, 32f * Time.deltaTime, Space.World);
        }

        // ── Preview room : 8 bodies + 8 caméras hors-map ──────────────────
        private static readonly Vector3 PreviewBase = new(-5000f, -5000f, -5000f);   // très loin
        private const float PreviewSpacing = 8f;

        private void BuildPreviewRoom()
        {
            _previewRoom = new GameObject("OperatorPreviewRoom");
            // Lumière unique pour toutes les cartes.
            var lightGo = new GameObject("PreviewLight");
            lightGo.transform.SetParent(_previewRoom.transform, false);
            lightGo.transform.position = PreviewBase + new Vector3(0, 3f, 2f);
            lightGo.transform.rotation = Quaternion.Euler(35f, -30f, 0f);
            var light = lightGo.AddComponent<Light>();
            light.type = LightType.Directional;
            light.intensity = 1.4f;
            light.color = Color.white;
            light.cullingMask = 1 << PreviewLayer;   // n'éclaire que le layer Preview
        }

        // Layer dédié pour isoler la preview du reste de la scène.
        private const int PreviewLayer = 30;   // un layer libre (≥8 user, ≤31)

        // ── Construction UI ────────────────────────────────────────────────
        private void BuildUI()
        {
            _canvasGo = new GameObject("OperatorSelectCanvas");
            var canvas = _canvasGo.AddComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvas.sortingOrder = 100;
            var scaler = _canvasGo.AddComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1920, 1080);
            _canvasGo.AddComponent<GraphicRaycaster>();

            // Fond gradient sombre.
            var bg = NewImage(_canvasGo.transform, "Backdrop", new Color(0.04f, 0.05f, 0.08f, 0.96f));
            Stretch(bg.rectTransform);

            // Titre + sous-titre
            var title = NewText(_canvasGo.transform, "Title", "CHOISIS TON OPÉRATEUR", 64, TextAnchor.MiddleCenter);
            var tr = title.rectTransform;
            tr.anchorMin = tr.anchorMax = new Vector2(0.5f, 1f); tr.pivot = new Vector2(0.5f, 1f);
            tr.anchoredPosition = new Vector2(0f, -40f);
            tr.sizeDelta = new Vector2(1400f, 90f);
            title.color = new Color(0.35f, 0.85f, 1f);
            title.fontStyle = FontStyle.Bold;

            var subtitle = NewText(_canvasGo.transform, "Subtitle",
                "Chaque classe a un sort, un ultime et une arme propres.", 22, TextAnchor.MiddleCenter);
            var sr = subtitle.rectTransform;
            sr.anchorMin = sr.anchorMax = new Vector2(0.5f, 1f); sr.pivot = new Vector2(0.5f, 1f);
            sr.anchoredPosition = new Vector2(0f, -125f);
            sr.sizeDelta = new Vector2(1400f, 30f);
            subtitle.color = new Color(0.65f, 0.7f, 0.8f);

            // Grille 4×2.
            var grid = new GameObject("Grid", typeof(RectTransform));
            grid.transform.SetParent(_canvasGo.transform, false);
            var grRt = grid.GetComponent<RectTransform>();
            grRt.anchorMin = grRt.anchorMax = grRt.pivot = new Vector2(0.5f, 0.5f);
            grRt.sizeDelta = new Vector2(1320f, 770f);
            grRt.anchoredPosition = new Vector2(0f, -10f);
            var g = grid.AddComponent<GridLayoutGroup>();
            g.cellSize = new Vector2(300f, 370f);
            g.spacing = new Vector2(20f, 20f);
            g.constraint = GridLayoutGroup.Constraint.FixedColumnCount;
            g.constraintCount = 4;
            g.childAlignment = TextAnchor.MiddleCenter;

            _cards.Clear();
            if (_operators != null)
            {
                int idx = 0;
                foreach (var op in _operators)
                {
                    if (op == null) continue;
                    BuildCard(grid.transform, op, idx);
                    idx++;
                }
            }

            // Bouton JOUER
            var play = NewButton(_canvasGo.transform, "PlayButton", "▶  JOUER", new Color(0.25f, 0.78f, 1f));
            var pr = play.GetComponent<RectTransform>();
            pr.anchorMin = pr.anchorMax = new Vector2(0.5f, 0f); pr.pivot = new Vector2(0.5f, 0f);
            pr.anchoredPosition = new Vector2(0f, 60f);
            pr.sizeDelta = new Vector2(360f, 80f);
            play.GetComponent<Button>().onClick.AddListener(StartMatch);
            // Texte du bouton un peu plus gros
            var btnLabel = play.GetComponentInChildren<Text>();
            if (btnLabel != null) { btnLabel.fontSize = 36; btnLabel.fontStyle = FontStyle.Bold; }

            HighlightSelected();
        }

        private void BuildCard(Transform parent, OperatorData op, int idx)
        {
            var slot = new CardSlot { Op = op };

            // Conteneur de la carte (bouton cliquable).
            var card = new GameObject($"Card_{op.DisplayName}", typeof(RectTransform));
            card.transform.SetParent(parent, false);
            slot.Border = card.GetComponent<RectTransform>();
            var borderImg = card.AddComponent<Image>();
            borderImg.color = FactionColor(op.Faction);
            var btn = card.AddComponent<Button>();
            btn.targetGraphic = borderImg;
            btn.onClick.AddListener(() => { _selected = op; HighlightSelected(); });

            // Fond intérieur (inset → l'image extérieure fait office de bordure).
            var inner = new GameObject("Inner", typeof(RectTransform));
            inner.transform.SetParent(card.transform, false);
            var innerRt = inner.GetComponent<RectTransform>();
            innerRt.anchorMin = Vector2.zero; innerRt.anchorMax = Vector2.one;
            innerRt.offsetMin = new Vector2(4, 4); innerRt.offsetMax = new Vector2(-4, -4);
            slot.Background = inner.AddComponent<Image>();
            slot.Background.color = new Color(0.10f, 0.12f, 0.16f, 1f);
            slot.Background.raycastTarget = false;

            // ─── Aperçu 3D ─────────────────────────────────────────────
            slot.Rt = new RenderTexture(320, 380, 16) { name = $"PreviewRT_{op.DisplayName}" };
            slot.Rt.Create();

            // Body instance dans la preview room.
            if (op.BodyPrefab != null)
            {
                slot.BodyInstance = Instantiate(op.BodyPrefab, _previewRoom.transform);
                slot.BodyInstance.transform.position = PreviewBase + new Vector3(idx * PreviewSpacing, 0f, 0f);
                slot.BodyInstance.transform.rotation = Quaternion.identity;
                SetLayerRecursively(slot.BodyInstance, PreviewLayer);
            }

            // Caméra dédiée pointée sur le body — cadre tête + buste sans tronquer.
            var camGo = new GameObject($"PreviewCam_{op.DisplayName}");
            camGo.transform.SetParent(_previewRoom.transform, false);
            camGo.transform.position = PreviewBase + new Vector3(idx * PreviewSpacing, 1.55f, 3.0f);
            camGo.transform.LookAt(PreviewBase + new Vector3(idx * PreviewSpacing, 1.30f, 0f));
            slot.PreviewCam = camGo.AddComponent<Camera>();
            slot.PreviewCam.clearFlags = CameraClearFlags.SolidColor;
            slot.PreviewCam.backgroundColor = new Color(0.10f, 0.12f, 0.16f, 0f);
            slot.PreviewCam.fieldOfView = 32f;   // un peu plus large → tête + buste + épaules visibles
            slot.PreviewCam.nearClipPlane = 0.1f;
            slot.PreviewCam.farClipPlane = 50f;
            slot.PreviewCam.cullingMask = 1 << PreviewLayer;
            slot.PreviewCam.targetTexture = slot.Rt;

            // RawImage du preview en haut de la carte.
            var preview = new GameObject("Preview", typeof(RectTransform));
            preview.transform.SetParent(inner.transform, false);
            var prRt = preview.GetComponent<RectTransform>();
            prRt.anchorMin = new Vector2(0f, 0.28f); prRt.anchorMax = new Vector2(1f, 1f);
            prRt.offsetMin = new Vector2(6, 4); prRt.offsetMax = new Vector2(-6, -34);
            var raw = preview.AddComponent<RawImage>();
            raw.texture = slot.Rt;
            raw.raycastTarget = false;

            // Bandeau couleur faction haut.
            var topBand = new GameObject("FactionBand", typeof(RectTransform));
            topBand.transform.SetParent(inner.transform, false);
            var tbRt = topBand.GetComponent<RectTransform>();
            tbRt.anchorMin = new Vector2(0f, 1f); tbRt.anchorMax = new Vector2(1f, 1f); tbRt.pivot = new Vector2(0.5f, 1f);
            tbRt.anchoredPosition = Vector2.zero;
            tbRt.sizeDelta = new Vector2(0f, 28f);
            var tbImg = topBand.AddComponent<Image>();
            tbImg.color = FactionColor(op.Faction);
            tbImg.raycastTarget = false;
            var factionLabel = NewText(topBand.transform, "Faction", $"{op.Faction}  •  {op.Codename}", 16, TextAnchor.MiddleCenter);
            Stretch(factionLabel.rectTransform);
            factionLabel.color = Color.white;
            factionLabel.fontStyle = FontStyle.Bold;
            factionLabel.raycastTarget = false;

            // Nom + rôle en bas.
            var nameTxt = NewText(inner.transform, "Name", op.DisplayName, 30, TextAnchor.MiddleCenter);
            var nr = nameTxt.rectTransform;
            nr.anchorMin = new Vector2(0f, 0.13f); nr.anchorMax = new Vector2(1f, 0.28f);
            nr.offsetMin = nr.offsetMax = Vector2.zero;
            nameTxt.color = Color.white;
            nameTxt.fontStyle = FontStyle.Bold;
            nameTxt.raycastTarget = false;
            slot.NameText = nameTxt;

            var roleTxt = NewText(inner.transform, "Role", $"{op.Role}", 18, TextAnchor.MiddleCenter);
            var rr = roleTxt.rectTransform;
            rr.anchorMin = new Vector2(0f, 0f); rr.anchorMax = new Vector2(1f, 0.13f);
            rr.offsetMin = rr.offsetMax = Vector2.zero;
            roleTxt.color = new Color(0.65f, 0.75f, 0.9f);
            roleTxt.raycastTarget = false;

            _cards.Add(slot);
        }

        private void HighlightSelected()
        {
            foreach (var c in _cards)
            {
                bool sel = c.Op == _selected;
                var fc = FactionColor(c.Op.Faction);
                // Bordure : couleur faction vif si sélectionné, sombre sinon.
                c.Border.GetComponent<Image>().color = sel ? fc * 1.4f : fc * 0.4f;
                // Fond intérieur : un peu plus clair si sélectionné.
                c.Background.color = sel ? new Color(0.18f, 0.22f, 0.28f) : new Color(0.10f, 0.12f, 0.16f);
                if (c.NameText != null) c.NameText.color = sel ? fc : Color.white;
            }
        }

        private static Color FactionColor(OperatorFaction f) => f switch
        {
            OperatorFaction.ORBIT => new Color(0.30f, 0.65f, 1.00f),
            OperatorFaction.FERRO => new Color(0.95f, 0.55f, 0.25f),
            OperatorFaction.VEIL  => new Color(0.70f, 0.40f, 0.95f),
            _                     => Color.gray,
        };

        private static void SetLayerRecursively(GameObject go, int layer)
        {
            go.layer = layer;
            foreach (Transform t in go.transform) SetLayerRecursively(t.gameObject, layer);
        }

        // ── Helpers UI ─────────────────────────────────────────────────────
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
            t.horizontalOverflow = HorizontalWrapMode.Wrap;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            return t;
        }

        private GameObject NewButton(Transform parent, string name, string label, Color color)
        {
            var go = new GameObject(name, typeof(RectTransform));
            go.transform.SetParent(parent, false);
            var img = go.AddComponent<Image>();
            img.color = color;
            var btn = go.AddComponent<Button>();
            btn.targetGraphic = img;
            if (!string.IsNullOrEmpty(label))
            {
                var t = NewText(go.transform, "Label", label, 30, TextAnchor.MiddleCenter);
                Stretch(t.rectTransform);
                t.color = Color.white;
            }
            return go;
        }
    }
}
