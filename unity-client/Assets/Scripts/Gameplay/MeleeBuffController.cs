// MeleeBuffController.cs — Buff "Battlecry" pour les opérateurs IsMelee.
//
// Mécanique demandée par le user :
//   1. Détecte la transition walk → run (sprint enclenché APRÈS avoir marché).
//   2. Lance l'anim "Standing Taunt Battlecry" via OperatorBody.TriggerBattlecry().
//      Pendant l'anim, le joueur est figé (locked = true).
//   3. À la fin de l'anim : ×2 vitesse pendant 15 s.
//   4. Cooldown 60 s avant de pouvoir re-trigger (sinon le joueur spammerait).
//
// Posé automatiquement par PlayerController sur les opérateurs IsMelee.

using Rocketpi.Gameplay.Body;
using UnityEngine;

namespace Rocketpi.Gameplay
{
    [DisallowMultipleComponent]
    public class MeleeBuffController : MonoBehaviour
    {
        [Header("Buff")]
        [Tooltip("Multiplicateur de vitesse appliqué pendant le buff.")]
        [SerializeField] private float _speedMultiplier = 2f;
        [Tooltip("Durée du boost de vitesse après la fin de l'anim Battlecry (secondes).")]
        [SerializeField] private float _buffDuration = 15f;
        [Tooltip("Cooldown global entre deux Battlecry (secondes).")]
        [SerializeField] private float _cooldown = 60f;
        [Tooltip("Durée de l'anim Battlecry (s). Lue depuis le clip si dispo, sinon ce fallback.")]
        [SerializeField] private float _battlecryDurationFallback = 2.5f;

        public bool BuffActive { get; private set; }
        public bool IsCharging  { get; private set; }
        public float BuffRemaining     { get; private set; }
        public float CooldownRemaining { get; private set; }

        private PlayerController _player;
        private OperatorBody     _body;
        private bool _wasRunning;
        private float _chargeEndsAt;

        private void Awake()
        {
            _player = GetComponent<PlayerController>();
        }

        private void Update()
        {
            if (_player == null) return;
            _body ??= _player.Body;

            // Compte à rebours du cooldown.
            if (CooldownRemaining > 0f) CooldownRemaining -= Time.deltaTime;

            // Phase 2 : on est encore en anim Battlecry, on attend la fin (locked).
            if (IsCharging)
            {
                if (Time.time >= _chargeEndsAt)
                {
                    IsCharging = false;
                    BuffActive = true;
                    BuffRemaining = _buffDuration;
                    _player.SpeedMultiplier = _speedMultiplier;
                    _player.LockMovement = false;
                }
                _wasRunning = _player.IsRunning();
                return;
            }

            // Phase 3 : buff actif, on décompte la durée.
            if (BuffActive)
            {
                BuffRemaining -= Time.deltaTime;
                if (BuffRemaining <= 0f)
                {
                    BuffActive = false;
                    BuffRemaining = 0f;
                    _player.SpeedMultiplier = 1f;
                    CooldownRemaining = _cooldown;
                }
            }

            // Phase 1 : détecte la transition walk → run.
            var running = _player.IsRunning();
            var canTrigger = !running == false                  // running == true
                          && !_wasRunning                       // était en walk ou idle juste avant
                          && !BuffActive
                          && !IsCharging
                          && CooldownRemaining <= 0f
                          && _body != null;

            if (canTrigger)
            {
                IsCharging = true;
                _player.LockMovement = true;
                _body.TriggerBattlecry();
                var clipLen = _body.GetClipLength("Battlecry");
                if (clipLen <= 0.1f) clipLen = _battlecryDurationFallback;
                _chargeEndsAt = Time.time + clipLen;
            }

            _wasRunning = running;
        }

        private void OnDisable()
        {
            // Sécurité : on remet le joueur dans un état propre si le composant est désactivé.
            if (_player != null)
            {
                _player.SpeedMultiplier = 1f;
                _player.LockMovement = false;
            }
            BuffActive = false;
            IsCharging = false;
        }
    }
}
