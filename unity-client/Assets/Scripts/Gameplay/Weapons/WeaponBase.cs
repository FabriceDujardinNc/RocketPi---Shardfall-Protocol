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

        // Multiplicateurs power-ups (1 = normal). Modifiés par PlayerPowerUps.
        public float DamageMultiplier   { get; set; } = 1f;
        public float FireRateMultiplier { get; set; } = 1f;
        public bool  BouncingBullets    { get; set; } = false;
        protected int BaseDamage => _baseDamage;

        public event Action<int, int> OnAmmoChanged;   // (current, max)
        public event Action           OnReloadStarted;
        public event Action           OnReloadFinished;
        public event Action           OnFired;          // émis à chaque tir effectif (pour anim/SFX)

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

        /// <summary>Aligne la durée de rechargement gameplay sur celle de l'animation
        /// (sinon les balles reviennent avant la fin de l'anim de recharge).</summary>
        public void SetReloadTime(float seconds)
        {
            if (seconds > 0.1f) _reloadTime = seconds;
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

            NextShotAt = Time.time + (1f / (_fireRate * Mathf.Max(0.1f, FireRateMultiplier)));
            CurrentAmmo--;
            OnAmmoChanged?.Invoke(CurrentAmmo, _magazineSize);
            Fire();
            OnFired?.Invoke();
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
