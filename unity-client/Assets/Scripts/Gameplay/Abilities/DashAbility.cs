// DashAbility.cs — Dash directionnel rapide avec brève invulnérabilité.
// Convient à des opérateurs mobiles (ex. Vex, Apex).

using System.Collections;
using UnityEngine;

namespace Rocketpi.Gameplay.Abilities
{
    public class DashAbility : AbilityBase
    {
        [Header("Dash")]
        [SerializeField] private float _dashSpeed = 24f;
        [SerializeField] private float _dashDuration = 0.18f;
        [SerializeField] private bool  _grantInvulnerability = true;

        protected override void Activate()
        {
            if (Owner == null) return;
            StartCoroutine(DashRoutine());
        }

        private IEnumerator DashRoutine()
        {
            var cc = Owner.GetComponent<CharacterController>();
            var health = Owner.Health;
            var direction = Owner.transform.forward;
            var endAt = Time.time + _dashDuration;

            if (_grantInvulnerability) health?.SetInvulnerable(true);

            while (Time.time < endAt)
            {
                cc.Move(direction * _dashSpeed * Time.deltaTime);
                yield return null;
            }

            if (_grantInvulnerability) health?.SetInvulnerable(false);
        }
    }
}
