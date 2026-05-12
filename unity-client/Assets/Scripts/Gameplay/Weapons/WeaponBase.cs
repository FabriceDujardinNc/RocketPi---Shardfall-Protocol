// WeaponBase.cs — Classe abstraite des armes signature.
// Une arme = un GameObject prefab attaché au socket main droite du joueur.
// Pas de logique économie côté Unity : pas de score-on-kill local, le score
// remonte via les events PracticeTarget → TrainingMatchManager.

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    public abstract class WeaponBase : MonoBehaviour
    {
        [Header("Tir")]
        [SerializeField] protected int   _magazineSize = 30;
        [SerializeField] protected float _fireRate = 8f;          // tirs / seconde
        [SerializeField] protected float _reloadTime = 2f;
        [SerializeField] protected int   _baseDamage = 18;

        [Header("Refs visuelles (optionnel)")]
        [SerializeField] protected Transform _muzzle;

        public int   CurrentAmmo { get; protected set; }
        public int   MagazineSize => _magazineSize;
        public bool  IsReloading  { get; protected set; }
        public float NextShotAt   { get; protected set; }

        public PlayerController Owner { get; protected set; }

        public event Action<int, int> OnAmmoChanged;   // (current, max)
        public event Action           OnReloadStarted;
        public event Action           OnReloadFinished;

        protected virtual void Awake()
        {
            CurrentAmmo = _magazineSize;
        }

        public virtual void Initialize(PlayerController owner)
        {
            Owner = owner;
            CurrentAmmo = _magazineSize;
            OnAmmoChanged?.Invoke(CurrentAmmo, _magazineSize);
        }

        // ── Hooks input depuis PlayerController ────────────────────────────

        public virtual void OnFireHeld()
        {
            if (IsReloading) return;
            if (Time.time < NextShotAt) return;
            if (CurrentAmmo <= 0)
            {
                BeginReload();
                return;
            }

            NextShotAt = Time.time + (1f / _fireRate);
            CurrentAmmo--;
            OnAmmoChanged?.Invoke(CurrentAmmo, _magazineSize);
            Fire();
        }

        public virtual void OnFireReleased() { }

        public virtual void OnAltFire() { }

        // ── Implémentations spécifiques ────────────────────────────────────

        protected abstract void Fire();

        protected virtual void BeginReload()
        {
            if (IsReloading || CurrentAmmo == _magazineSize) return;
            IsReloading = true;
            OnReloadStarted?.Invoke();
            Invoke(nameof(FinishReload), _reloadTime);
        }

        private void FinishReload()
        {
            CurrentAmmo = _magazineSize;
            IsReloading = false;
            OnAmmoChanged?.Invoke(CurrentAmmo, _magazineSize);
            OnReloadFinished?.Invoke();
        }
    }
}
