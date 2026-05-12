// HealthSystemTests.cs — EditMode tests pour HealthSystem.
// Couvre : init max HP, damage, heal, invulnerability, death event.

using NUnit.Framework;
using Rocketpi.Gameplay;
using UnityEngine;

namespace Rocketpi.Tests
{
    public class HealthSystemTests
    {
        private GameObject _go;
        private HealthSystem _h;

        [SetUp]
        public void Setup()
        {
            _go = new GameObject("test-health");
            _h = _go.AddComponent<HealthSystem>();
            _h.SetMaxHealth(100, fillCurrent: true);
        }

        [TearDown]
        public void TearDown() => Object.DestroyImmediate(_go);

        [Test]
        public void TakeDamage_ReducesCurrentHp()
        {
            _h.TakeDamage(30);
            Assert.AreEqual(70, _h.Current);
            Assert.IsFalse(_h.IsDead);
        }

        [Test]
        public void TakeDamage_DoesNotGoBelowZero()
        {
            _h.TakeDamage(500);
            Assert.AreEqual(0, _h.Current);
            Assert.IsTrue(_h.IsDead);
        }

        [Test]
        public void Heal_DoesNotOverfillMax()
        {
            _h.TakeDamage(10);
            _h.Heal(100);
            Assert.AreEqual(100, _h.Current);
        }

        [Test]
        public void Invulnerable_AbsorbsDamage()
        {
            _h.SetInvulnerable(true);
            _h.TakeDamage(50);
            Assert.AreEqual(100, _h.Current);
        }

        [Test]
        public void Dies_FiresOnDiedExactlyOnce()
        {
            var diedCount = 0;
            _h.OnDied += () => diedCount++;
            _h.TakeDamage(120);
            _h.TakeDamage(20);
            Assert.AreEqual(1, diedCount);
        }
    }
}
