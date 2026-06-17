// AbilityBase.cs — Classe abstraite pour les capacités d'opérateur.
// Trois slots gérés au niveau du PlayerController : Q (ability1), E (ability2), R (ultimate).

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Abilities
{
    public abstract class AbilityBase : MonoBehaviour
    {
        public enum Slot { Primary, Secondary, Ultimate }

        [SerializeField] protected Slot   _slot = Slot.Primary;
        [SerializeField] protected float  _cooldown = 8f;
        [SerializeField] protected int    _maxCharges = 1;
        [SerializeField] protected string _displayName = "Ability";

        public Slot   AbilitySlot => _slot;
        public string DisplayName => _displayName;
        public int    Charges     { get; protected set; }
        public int    MaxCharges  => _maxCharges;
        public float  Cooldown    => _cooldown;
        public float  CooldownRemaining { get; protected set; }
        public bool   IsReady     => Charges > 0 && CooldownRemaining <= 0f;

        public PlayerController Owner { get; protected set; }

        public event Action<int>   OnChargesChanged;
        public event Action<float> OnCooldownTick;
        public event Action        OnUsed;

        protected virtual void Awake()
        {
            Charges = _maxCharges;
        }

        public virtual void Initialize(PlayerController owner)
        {
            Owner = owner;
            Charges = _maxCharges;
            CooldownRemaining = 0f;
            OnChargesChanged?.Invoke(Charges);
        }

        protected virtual void Update()
        {
            if (CooldownRemaining > 0f)
            {
                CooldownRemaining = Mathf.Max(0f, CooldownRemaining - Time.deltaTime);
                OnCooldownTick?.Invoke(CooldownRemaining);
                if (CooldownRemaining <= 0f && Charges < _maxCharges)
                {
                    Charges++;
                    OnChargesChanged?.Invoke(Charges);
                    if (Charges < _maxCharges) CooldownRemaining = _cooldown;
                }
            }
        }

        public bool TryActivate()
        {
            if (!IsReady) return false;
            Charges--;
            CooldownRemaining = _cooldown;
            OnChargesChanged?.Invoke(Charges);
            OnUsed?.Invoke();
            Activate();
            return true;
        }

        protected abstract void Activate();
    }
}
