// HealthSystem.cs — Composant HP générique avec events.
// Attaché aux joueurs, cibles d'entraînement, et plus tard aux ennemis bots.

using System;
using UnityEngine;

namespace Rocketpi.Gameplay
{
    public class HealthSystem : MonoBehaviour
    {
        [SerializeField] private int _maxHp = 100;
        [SerializeField] private bool _invulnerable = false;

        public int Current { get; private set; }
        public int Max => _maxHp;
        public bool IsDead => Current <= 0;
        public bool IsInvulnerable => _invulnerable;

        public event Action<int, int> OnHealthChanged; // (current, max)
        public event Action<int>      OnDamaged;       // amount
        public event Action<int>      OnHealed;        // amount
        public event Action           OnDied;

        private void Awake()
        {
            Current = _maxHp;
        }

        public void SetInvulnerable(bool value) => _invulnerable = value;

        public void TakeDamage(int amount)
        {
            if (IsDead || _invulnerable || amount <= 0) return;

            Current = Mathf.Max(0, Current - amount);
            OnDamaged?.Invoke(amount);
            OnHealthChanged?.Invoke(Current, _maxHp);

            if (IsDead) OnDied?.Invoke();
        }

        public void Heal(int amount)
        {
            if (IsDead || amount <= 0) return;
            Current = Mathf.Min(_maxHp, Current + amount);
            OnHealed?.Invoke(amount);
            OnHealthChanged?.Invoke(Current, _maxHp);
        }

        public void ResetHealth()
        {
            Current = _maxHp;
            OnHealthChanged?.Invoke(Current, _maxHp);
        }

        public void SetMaxHealth(int max, bool fillCurrent = true)
        {
            _maxHp = Mathf.Max(1, max);
            if (fillCurrent) Current = _maxHp;
            else Current = Mathf.Min(Current, _maxHp);
            OnHealthChanged?.Invoke(Current, _maxHp);
        }
    }
}
