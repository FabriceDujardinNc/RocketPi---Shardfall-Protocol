// DamageHitbox.cs — Trigger qui inflige des dégâts au HealthSystem trouvé en parent.
// Utilisé pour les zones de dégât (DoT, environnement) et certaines abilities AoE.
// Pour les projectiles, voir ProjectileWeapon.

using UnityEngine;

namespace Rocketpi.Gameplay
{
    [RequireComponent(typeof(Collider))]
    public class DamageHitbox : MonoBehaviour
    {
        [SerializeField] private int _damagePerTick = 5;
        [SerializeField] private float _tickInterval = 0.5f;
        [SerializeField] private LayerMask _targetLayers = ~0;
        [SerializeField] private string _ignoreTag = "Player"; // ignorer le porteur

        private float _nextTickAt;

        private void OnTriggerStay(Collider other)
        {
            if (Time.time < _nextTickAt) return;
            if (!IsValidTarget(other)) return;

            var hs = other.GetComponentInParent<HealthSystem>();
            if (hs == null || hs.IsDead) return;

            hs.TakeDamage(_damagePerTick);
            _nextTickAt = Time.time + _tickInterval;
        }

        private bool IsValidTarget(Collider c)
        {
            if (((1 << c.gameObject.layer) & _targetLayers) == 0) return false;
            if (!string.IsNullOrEmpty(_ignoreTag) && c.CompareTag(_ignoreTag)) return false;
            return true;
        }
    }
}
