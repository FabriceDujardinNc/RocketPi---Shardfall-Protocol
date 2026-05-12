// Projectile.cs — Projectile linéaire avec lifespan + AoE optionnelle.
// Le hit envoie un event consommable par les managers de match.

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    [RequireComponent(typeof(Rigidbody))]
    public class Projectile : MonoBehaviour
    {
        [SerializeField] private float _lifespan = 4f;
        [SerializeField] private float _explosionRadius = 0f;       // 0 = pas d'AoE, hit direct
        [SerializeField] private LayerMask _hittableLayers = ~0;
        [SerializeField] private GameObject _explosionVfx;

        public event Action<HealthSystem, int> OnHit;

        private int        _damage;
        private GameObject _owner;
        private Rigidbody  _rb;
        private bool       _hasExploded;

        private void Awake()
        {
            _rb = GetComponent<Rigidbody>();
            _rb.useGravity = false;
        }

        public void Launch(Vector3 velocity, int damage, GameObject owner)
        {
            _damage = damage;
            _owner  = owner;
            _rb.linearVelocity = velocity;
            Destroy(gameObject, _lifespan);
        }

        private void OnCollisionEnter(Collision collision)
        {
            if (_hasExploded) return;
            if (_owner != null && collision.gameObject == _owner) return;
            _hasExploded = true;

            if (_explosionRadius > 0f) ApplySplashDamage();
            else ApplyDirectDamage(collision.collider);

            if (_explosionVfx != null) Instantiate(_explosionVfx, transform.position, Quaternion.identity);
            Destroy(gameObject);
        }

        private void ApplyDirectDamage(Collider c)
        {
            var hs = c.GetComponentInParent<HealthSystem>();
            if (hs == null || hs.IsDead) return;
            hs.TakeDamage(_damage);
            OnHit?.Invoke(hs, _damage);
        }

        private void ApplySplashDamage()
        {
            var hits = Physics.OverlapSphere(transform.position, _explosionRadius, _hittableLayers);
            foreach (var c in hits)
            {
                var hs = c.GetComponentInParent<HealthSystem>();
                if (hs == null || hs.IsDead) continue;
                var distance = Vector3.Distance(transform.position, c.transform.position);
                var falloff = Mathf.Clamp01(1f - distance / _explosionRadius);
                var dmg = Mathf.CeilToInt(_damage * falloff);
                if (dmg <= 0) continue;
                hs.TakeDamage(dmg);
                OnHit?.Invoke(hs, dmg);
            }
        }
    }
}
