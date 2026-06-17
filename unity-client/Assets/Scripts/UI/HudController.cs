// HudController.cs — Affichage HUD in-match : HP, munitions, timer, score.
// S'attache au Canvas principal et lit les events du PlayerController + Match.

using Rocketpi.Gameplay;
using Rocketpi.Gameplay.Match;
using Rocketpi.Gameplay.Weapons;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace Rocketpi.UI
{
    public class HudController : MonoBehaviour
    {
        [Header("Refs scène")]
        [SerializeField] private PlayerController _player;
        [SerializeField] private TrainingMatchManager _match;

        [Header("UI")]
        [SerializeField] private TMP_Text _hpLabel;
        [SerializeField] private Slider   _hpBar;
        [SerializeField] private TMP_Text _ammoLabel;
        [SerializeField] private TMP_Text _timerLabel;
        [SerializeField] private TMP_Text _scoreLabel;

        private WeaponBase _trackedWeapon;

        private void Start()
        {
            if (_player != null)
            {
                _player.Health.OnHealthChanged += HandleHealthChanged;
                _player.OnWeaponChanged        += HandleWeaponChanged;
                HandleHealthChanged(_player.Health.Current, _player.Health.Max);
                HandleWeaponChanged(_player.Weapon);
            }
            if (_match != null)
            {
                _match.OnScoreChanged += HandleScoreChanged;
                _match.OnTimeTick     += HandleTimeTick;
            }
        }

        private void OnDestroy()
        {
            if (_player != null)
            {
                _player.Health.OnHealthChanged -= HandleHealthChanged;
                _player.OnWeaponChanged        -= HandleWeaponChanged;
            }
            if (_match != null)
            {
                _match.OnScoreChanged -= HandleScoreChanged;
                _match.OnTimeTick     -= HandleTimeTick;
            }
            UnbindWeapon();
        }

        // ── Handlers ───────────────────────────────────────────────────────

        private void HandleHealthChanged(int current, int max)
        {
            if (_hpLabel != null) _hpLabel.text = $"{current} / {max}";
            if (_hpBar != null)
            {
                _hpBar.maxValue = max;
                _hpBar.value    = current;
            }
        }

        private void HandleWeaponChanged(WeaponBase weapon)
        {
            UnbindWeapon();
            _trackedWeapon = weapon;
            if (_trackedWeapon != null)
            {
                _trackedWeapon.OnAmmoChanged += HandleAmmoChanged;
                HandleAmmoChanged(_trackedWeapon.CurrentAmmo, _trackedWeapon.MagazineSize);
            }
            else if (_ammoLabel != null)
            {
                _ammoLabel.text = "—";
            }
        }

        private void UnbindWeapon()
        {
            if (_trackedWeapon != null)
            {
                _trackedWeapon.OnAmmoChanged -= HandleAmmoChanged;
                _trackedWeapon = null;
            }
        }

        private void HandleAmmoChanged(int current, int max)
        {
            if (_ammoLabel != null) _ammoLabel.text = $"{current} / {max}";
        }

        private void HandleTimeTick(float remaining)
        {
            if (_timerLabel == null) return;
            var m = Mathf.FloorToInt(remaining / 60f);
            var s = Mathf.FloorToInt(remaining % 60f);
            _timerLabel.text = $"{m}:{s:D2}";
        }

        private void HandleScoreChanged(int score)
        {
            if (_scoreLabel != null) _scoreLabel.text = score.ToString("N0");
        }
    }
}
