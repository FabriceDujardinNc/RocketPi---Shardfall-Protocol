// ScanAbility.cs — Reveal des ennemis dans un rayon pendant N secondes.
// En mode training, met en surbrillance les PracticeTarget. En PvP, ferait
// apparaître les ennemis sur la minimap (à brancher plus tard).

using System.Collections.Generic;
using UnityEngine;

namespace Rocketpi.Gameplay.Abilities
{
    public class ScanAbility : AbilityBase
    {
        [Header("Scan")]
        [SerializeField] private float _radius = 25f;
        [SerializeField] private float _duration = 6f;
        [SerializeField] private LayerMask _targetLayers = ~0;
        [SerializeField] private Color _highlightColor = new(1f, 0.4f, 0.1f, 0.8f);

        private readonly List<(Renderer renderer, Color original)> _highlighted = new();

        protected override void Activate()
        {
            if (Owner == null) return;
            HighlightTargets();
            Invoke(nameof(ClearHighlights), _duration);
        }

        private void HighlightTargets()
        {
            _highlighted.Clear();
            var hits = Physics.OverlapSphere(Owner.transform.position, _radius, _targetLayers);
            foreach (var c in hits)
            {
                if (c.GetComponentInParent<PlayerController>() == Owner) continue;
                foreach (var r in c.GetComponentsInChildren<Renderer>())
                {
                    if (r.material == null) continue;
                    var original = r.material.color;
                    _highlighted.Add((r, original));
                    r.material.color = _highlightColor;
                }
            }
        }

        private void ClearHighlights()
        {
            foreach (var (r, original) in _highlighted)
            {
                if (r != null && r.material != null) r.material.color = original;
            }
            _highlighted.Clear();
        }
    }
}
