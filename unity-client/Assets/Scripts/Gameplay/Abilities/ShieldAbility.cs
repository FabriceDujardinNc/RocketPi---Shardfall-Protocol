// ShieldAbility.cs — Active un bouclier temporaire qui absorbe N points de dégâts.
// Implémenté via temporary HP additionnel sur HealthSystem (boucle d'absorption
// gérée via un wrapper d'event).

using System.Collections;
using UnityEngine;

namespace Rocketpi.Gameplay.Abilities
{
    public class ShieldAbility : AbilityBase
    {
        [Header("Bouclier")]
        [SerializeField] private int   _shieldHp = 80;
        [SerializeField] private float _duration = 5f;

        private int _absorbed;

        protected override void Activate()
        {
            if (Owner == null) return;
            StartCoroutine(ShieldRoutine());
        }

        private IEnumerator ShieldRoutine()
        {
            var health = Owner.Health;
            if (health == null) yield break;

            _absorbed = 0;
            void OnDamaged(int amount)
            {
                _absorbed += amount;
                if (_absorbed < _shieldHp)
                {
                    // Le bouclier absorbe : on rend les PV.
                    health.Heal(amount);
                }
            }

            health.OnDamaged += OnDamaged;
            yield return new WaitForSeconds(_duration);
            health.OnDamaged -= OnDamaged;
        }
    }
}
