// OperatorData.cs — ScriptableObject décrivant un opérateur du roster.
// Source de vérité côté client (visuel, stats locales). Le serveur Laravel
// reste autoritaire pour le déblocage, l'affinité et l'XP — Unity ne fait
// QUE le rendre jouable.

using UnityEngine;

namespace Rocketpi.Gameplay.Operators
{
    public enum OperatorFaction { ORBIT, FERRO, VEIL }
    public enum OperatorRarity  { Common, Rare, Epic, Legendary }

    [CreateAssetMenu(menuName = "RocketPi/Operator Data", fileName = "Op_NEW", order = 0)]
    public class OperatorData : ScriptableObject
    {
        [Header("Identité (doit matcher le serveur Laravel)")]
        [Tooltip("Codename serveur, ex. VX-01 — utilisé pour le matching opérateur côté Laravel.")]
        public string Codename;
        public string DisplayName;
        public OperatorFaction Faction;
        public OperatorRarity  Rarity;

        [Header("Stats locales")]
        [Tooltip("HP de base au level 0. Le serveur ne valide pas ce nombre, c'est juste un défaut.")]
        public int BaseHp = 100;
        public float WalkSpeed = 5.5f;
        public float SprintSpeed = 8.5f;

        [Header("Visuel")]
        public Sprite Portrait;
        public Color  AccentColor = Color.white;

        [Header("Loadout")]
        public GameObject WeaponPrefab;          // doit avoir un WeaponBase
        public GameObject[] AbilityPrefabs;      // ex. [DashAbility, ShieldAbility]
        public GameObject UltimatePrefab;        // facultatif
    }
}
