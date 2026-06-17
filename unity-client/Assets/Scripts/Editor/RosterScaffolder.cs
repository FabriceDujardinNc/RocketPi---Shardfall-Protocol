// RosterScaffolder.cs — Génère les 8 OperatorData ScriptableObjects matchant
// le roster Laravel (cf. database/seeders/OperatorSeeder.php).
//
// Tools > RocketPi > Scaffold Roster
//
// Idempotent : si un asset existe déjà, on ne le recrée pas. Pour reset,
// supprimer manuellement Assets/Resources/Operators/*.asset.

#if UNITY_EDITOR
using System.IO;
using Rocketpi.Gameplay.Operators;
using UnityEditor;
using UnityEngine;

namespace Rocketpi.Editor
{
    public static class RosterScaffolder
    {
        private const string OperatorsDir = "Assets/Resources/Operators";

        private readonly struct RosterEntry
        {
            public readonly string Codename;
            public readonly string DisplayName;
            public readonly OperatorFaction Faction;
            public readonly OperatorRarity  Rarity;
            public readonly int Hp;
            public readonly Color Accent;
            public RosterEntry(string codename, string name, OperatorFaction f, OperatorRarity r, int hp, Color accent)
            { Codename = codename; DisplayName = name; Faction = f; Rarity = r; Hp = hp; Accent = accent; }
        }

        // Aligné sur database/seeders/OperatorSeeder.php
        private static readonly RosterEntry[] Roster =
        {
            new("VX-01", "Vex",    OperatorFaction.ORBIT, OperatorRarity.Legendary, 100, new(0.30f, 0.80f, 1.00f)),
            new("HL-02", "Halo",   OperatorFaction.ORBIT, OperatorRarity.Epic,      110, new(0.50f, 0.70f, 1.00f)),
            new("DR-03", "Drift",  OperatorFaction.ORBIT, OperatorRarity.Rare,      105, new(0.40f, 0.65f, 0.90f)),
            new("CR-04", "Crag",   OperatorFaction.FERRO, OperatorRarity.Legendary, 140, new(0.95f, 0.55f, 0.20f)),
            new("BK-05", "Brick",  OperatorFaction.FERRO, OperatorRarity.Epic,      130, new(0.85f, 0.50f, 0.30f)),
            new("IR-06", "Iron",   OperatorFaction.FERRO, OperatorRarity.Common,    120, new(0.70f, 0.55f, 0.45f)),
            new("WR-07", "Wraith", OperatorFaction.VEIL,  OperatorRarity.Epic,       90, new(0.65f, 0.40f, 0.90f)),
            new("EC-08", "Echo",   OperatorFaction.VEIL,  OperatorRarity.Rare,       95, new(0.55f, 0.45f, 0.85f)),
        };

        [MenuItem("Tools/RocketPi/Scaffold Roster")]
        public static void ScaffoldRoster()
        {
            EnsureFolder(OperatorsDir);

            var created = 0;
            var skipped = 0;
            foreach (var entry in Roster)
            {
                var path = $"{OperatorsDir}/Op_{entry.Codename.Replace("-", "")}_{entry.DisplayName}.asset";
                if (File.Exists(path))
                {
                    skipped++;
                    continue;
                }

                var so = ScriptableObject.CreateInstance<OperatorData>();
                so.Codename     = entry.Codename;
                so.DisplayName  = entry.DisplayName;
                so.Faction      = entry.Faction;
                so.Rarity       = entry.Rarity;
                so.BaseHp       = entry.Hp;
                so.AccentColor  = entry.Accent;
                so.WalkSpeed    = 5.5f;
                so.SprintSpeed  = 8.5f;

                AssetDatabase.CreateAsset(so, path);
                created++;
            }

            AssetDatabase.SaveAssets();
            AssetDatabase.Refresh();
            Debug.Log($"[RocketPi] Roster scaffold: {created} créés, {skipped} déjà existants.");
        }

        private static void EnsureFolder(string path)
        {
            if (AssetDatabase.IsValidFolder(path)) return;
            var parts = path.Split('/');
            var current = parts[0];
            for (var i = 1; i < parts.Length; i++)
            {
                var next = $"{current}/{parts[i]}";
                if (!AssetDatabase.IsValidFolder(next))
                    AssetDatabase.CreateFolder(current, parts[i]);
                current = next;
            }
        }
    }
}
#endif
