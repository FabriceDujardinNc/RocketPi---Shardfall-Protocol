// MeleeWeapon.cs — Arme de mêlée pour les opérateurs non-humains (créatures).
// Pas de balles, pas de tracer : zone de hit courte portée devant le joueur.
// L'anim de coup ("Melee-Combo-Attack") est un combo de 3 frappes qui se déclenche
// d'un clic ; à chaque fenêtre de hit du combo on refait un check de proximité —
// si l'ennemi s'éloigne en cours de combo, les coups suivants ne touchent plus.

using System;
using System.Collections.Generic;
using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    public class MeleeWeapon : WeaponBase
    {
        [Header("Mêlée")]
        [Tooltip("Portée maximale du coup (m).")]
        [SerializeField] private float _meleeRange = 2.8f;
        [Tooltip("Rayon de la sphère de coup (largeur de la zone touchée).")]
        [SerializeField] private float _hitRadius = 1.4f;
        [Tooltip("LayerMask des cibles touchables.")]
        [SerializeField] private LayerMask _hittableLayers = ~0;

        [Header("Combo")]
        [Tooltip("Durée totale du combo (s). 0 = lue depuis l'anim Melee-Combo-Attack.")]
        [SerializeField] private float _comboDuration = 0f;
        [Tooltip("Timings normalisés (0-1) des 3 fenêtres de hit dans le combo.")]
        [SerializeField] private float[] _hitTimings = { 0.20f, 0.50f, 0.80f };

        /// <summary>Émis à chaque hit valide. Args: (target HealthSystem, damage dealt).</summary>
        public event Action<HealthSystem, int> OnHit;

        private bool   _swinging;
        private float  _swingStartTime;
        private float  _swingDuration;
        private int    _nextHitIdx;
        private readonly HashSet<HealthSystem> _hitThisSwing = new();  // anti double-tap par swing
        // Buffer alloué une fois pour les overlap calls (pas d'alloc en update).
        private readonly Collider[] _overlapBuffer = new Collider[16];

        protected override void Awake()
        {
            base.Awake();
            // Mêlée = beaucoup plus risqué que distance (il faut s'approcher en mode
            // infiltration sans se faire repérer), donc on cogne fort. Forçage
            // INCONDITIONNEL : les anciens prefabs (sauvés avant cette tuning) prennent
            // aussi ces stats au runtime.
            _fireRate    = 1.0f;                  // 1 combo par seconde min
            _baseDamage  = 80;                    // 80 × 3 hits = 240 max par combo (one-shot 200 hp)
            _magazineSize = 1;
            _reloadTime  = 0.4f;

            // PAS DE RECHARGEMENT pour la mêlée : un swing n'a pas de chargeur, juste
            // un cooldown de combo. InfiniteAmmo désactive le RefillAmmo() de WeaponBase
            // et l'anim de reload côté body.
            InfiniteAmmo = true;
        }

        protected override void Fire()
        {
            // Un combo en cours bloque le redémarrage — la cadence WeaponBase fait aussi
            // le gating, double sécurité.
            if (_swinging) return;

            _swinging = true;
            _swingStartTime = Time.time;
            _nextHitIdx = 0;
            _hitThisSwing.Clear();

            // Durée du combo = anim length si dispo (sinon 1.2 s fallback). Sert à étaler
            // les 3 fenêtres de hit sur toute l'anim.
            var animLen = (Owner != null && Owner.Body != null)
                ? Owner.Body.GetClipLength("Melee-Combo-Attack")
                : 0f;
            _swingDuration = _comboDuration > 0f ? _comboDuration
                           : animLen > 0.1f ? animLen
                           : 1.2f;
        }

        private void Update()
        {
            if (!_swinging) return;

            var elapsed = Time.time - _swingStartTime;
            // Tente le prochain hit window si on a atteint son timing.
            while (_nextHitIdx < _hitTimings.Length
                   && elapsed >= _hitTimings[_nextHitIdx] * _swingDuration)
            {
                ApplyMeleeHit();
                _nextHitIdx++;
            }

            if (elapsed >= _swingDuration) _swinging = false;
        }

        private void ApplyMeleeHit()
        {
            if (Owner == null) return;

            // Origine : devant l'épaule du joueur, dans la direction caméra (ce que voit
            // le joueur). On utilise OverlapSphere (et pas SphereCast) parce que c'est
            // plus fiable quand l'ennemi est COLLÉ à toi — SphereCast ignore les colliders
            // déjà chevauchés au point de départ.
            var cam = Owner.GetComponentInChildren<Camera>() ?? Camera.main;
            Vector3 forward = cam != null ? cam.transform.forward : Owner.transform.forward;
            Vector3 origin  = Owner.transform.position
                            + Vector3.up * 1.2f                      // hauteur torse
                            + forward * (_meleeRange * 0.5f);        // devant le joueur

            int n = Physics.OverlapSphereNonAlloc(origin, _hitRadius + _meleeRange * 0.4f,
                                                  _overlapBuffer, _hittableLayers,
                                                  QueryTriggerInteraction.Ignore);
            for (var i = 0; i < n; i++)
            {
                var c = _overlapBuffer[i];
                if (c == null) continue;
                var health = c.GetComponentInParent<HealthSystem>();
                if (health == null) continue;
                if (health == Owner.Health) continue;   // pas auto-dégât
                if (health.IsDead) continue;
                if (_hitThisSwing.Contains(health)) continue;   // déjà touché par CE swing

                // Vérifie cône frontal : seuls les ennemis devant le joueur sont touchés
                // (évite de frapper quelqu'un dans le dos).
                var toTarget = (health.transform.position - Owner.transform.position);
                toTarget.y = 0f;
                if (toTarget.sqrMagnitude > 0.01f)
                {
                    var fwd = forward; fwd.y = 0f;
                    if (Vector3.Dot(fwd.normalized, toTarget.normalized) < 0.25f) continue; // ~75° de demi-cône
                }

                var dmg = Mathf.CeilToInt(_baseDamage * DamageMultiplier);
                health.TakeDamage(dmg);
                _hitThisSwing.Add(health);
                OnHit?.Invoke(health, dmg);
            }
        }

        private void OnDrawGizmosSelected()
        {
            Gizmos.color = new Color(1f, 0.6f, 0.2f, 0.4f);
            var fwd = Application.isPlaying && Owner != null ? Owner.transform.forward : transform.forward;
            var origin = Application.isPlaying && Owner != null
                ? Owner.transform.position + Vector3.up * 1.2f + fwd * (_meleeRange * 0.5f)
                : transform.position + fwd * _meleeRange * 0.5f;
            Gizmos.DrawWireSphere(origin, _hitRadius + _meleeRange * 0.4f);
        }
    }
}
