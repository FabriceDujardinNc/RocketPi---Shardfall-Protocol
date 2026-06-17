// OperatorData.cs — ScriptableObject décrivant un opérateur du roster.
// Source de vérité côté client (visuel, stats locales). Le serveur Laravel
// reste autoritaire pour le déblocage, l'affinité et l'XP — Unity ne fait
// QUE le rendre jouable.

using UnityEngine;

namespace Rocketpi.Gameplay.Operators
{
    public enum OperatorFaction { ORBIT, FERRO, VEIL }
    public enum OperatorRarity  { Common, Rare, Epic, Legendary }

    /// <summary>Rôle/classe — détermine la capacité de classe par défaut (touche Q).</summary>
    public enum OperatorRole { Sniper, Healer, Scout, Tank, Explosives, Assault, Infiltrator, Hacker }

    [CreateAssetMenu(menuName = "RocketPi/Operator Data", fileName = "Op_NEW", order = 0)]
    public class OperatorData : ScriptableObject
    {
        [Header("Identité (doit matcher le serveur Laravel)")]
        [Tooltip("Codename serveur, ex. VX-01 — utilisé pour le matching opérateur côté Laravel.")]
        public string Codename;
        public string DisplayName;
        public OperatorFaction Faction;
        public OperatorRarity  Rarity;
        [Tooltip("Classe : détermine la capacité de base (touche Q).")]
        public OperatorRole Role;

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
        [Tooltip("Si true (non-humain / créature), reçoit une arme de mêlée par défaut " +
                 "au lieu d'une arme à distance lors du Build Weapon Prefabs.")]
        public bool IsMelee = false;

        [Header("Body 3D (Mixamo Humanoid)")]
        [Tooltip("Prefab visuel : root vide + SkinnedMeshRenderer enfant + Animator (controller locomotion) + collider capsule." +
                 " Servira pour le joueur (3rd person) ET pour les NPCs (NavMeshAgent).")]
        public GameObject BodyPrefab;

        [Tooltip("Hauteur de la capsule body (m). Sert au CharacterController / NavMeshAgent.")]
        public float BodyHeight = 1.85f;

        [Tooltip("Rayon de la capsule body (m).")]
        public float BodyRadius = 0.4f;

        [Tooltip("Offset Y de la caméra third-person par rapport à la racine du body (hauteur des épaules ~1.6m).")]
        public float CameraShoulderHeight = 1.6f;
    }
}
