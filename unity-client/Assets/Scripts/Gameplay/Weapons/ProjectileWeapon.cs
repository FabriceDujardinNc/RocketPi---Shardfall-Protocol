// ProjectileWeapon.cs — Tir d'un projectile physique (lance-roquettes, grenade,
// fusil arbalète). Instancie un Projectile prefab depuis le muzzle.

using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    public class ProjectileWeapon : WeaponBase
    {
        [Header("Projectile")]
        [SerializeField] private GameObject _projectilePrefab;
        [SerializeField] private float      _muzzleVelocity = 35f;

        protected override void Fire()
        {
            if (_projectilePrefab == null || _muzzle == null) return;

            var go = Instantiate(_projectilePrefab, _muzzle.position, _muzzle.rotation);
            if (go.TryGetComponent<Projectile>(out var p))
            {
                p.Launch(_muzzle.forward * _muzzleVelocity, _baseDamage, Owner != null ? Owner.gameObject : null);
            }
        }
    }
}
