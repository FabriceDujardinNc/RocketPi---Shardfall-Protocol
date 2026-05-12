// HitscanWeapon.cs — Tir instantané par raycast. Pour fusils d'assaut, SMG, etc.
// Émet un event OnHit qui permet aux managers de match de récupérer les hits
// (utilisé par TrainingMatchManager pour calculer score/kills).

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    public class HitscanWeapon : WeaponBase
    {
        [Header("Hitscan")]
        [SerializeField] private float _maxRange = 80f;
        [SerializeField] private LayerMask _hittableLayers = ~0;
        [SerializeField] private float _spreadAngle = 0.6f;        // degrés
        [SerializeField] private float _headshotMultiplier = 2f;

        /// <summary>Émis à chaque hit valide. Args: (target HealthSystem, damage dealt, was headshot).</summary>
        public event Action<HealthSystem, int, bool> OnHit;

        protected override void Fire()
        {
            var origin = Owner != null && Owner.GetComponentInChildren<Camera>() is Camera cam
                ? cam.transform
                : transform;

            var direction = ApplySpread(origin.forward);

            if (!Physics.Raycast(origin.position, direction, out var hit, _maxRange, _hittableLayers))
                return;

            var health = hit.collider.GetComponentInParent<HealthSystem>();
            if (health == null || health.IsDead) return;

            var isHeadshot = hit.collider.CompareTag("Hitbox") &&
                             hit.collider.gameObject.name.IndexOf("head", StringComparison.OrdinalIgnoreCase) >= 0;
            var damage = Mathf.CeilToInt(_baseDamage * (isHeadshot ? _headshotMultiplier : 1f));

            health.TakeDamage(damage);
            OnHit?.Invoke(health, damage, isHeadshot);
        }

        private Vector3 ApplySpread(Vector3 forward)
        {
            if (_spreadAngle <= 0f) return forward;
            var x = UnityEngine.Random.Range(-_spreadAngle, _spreadAngle);
            var y = UnityEngine.Random.Range(-_spreadAngle, _spreadAngle);
            return Quaternion.Euler(x, y, 0f) * forward;
        }
    }
}
