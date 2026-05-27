// PowerUpType.cs — Catalogue des bonus ramassables du mode Training.

namespace Rocketpi.Gameplay.PowerUps
{
    public enum PowerUpType
    {
        Shield,          // 🛡️ invulnérabilité temporaire
        MegaBomb,        // 💣 explosion instantanée, tue les NPCs autour
        BouncingBullets, // ⚡ les balles rebondissent sur les murs
        RapidFire,       // 🔥 cadence de tir ×3
        QuadDamage,      // ⚔️ dégâts ×4
        SpeedBoost,      // 👟 vitesse de déplacement ×1.7
        HealthPack,      // ➕ soin instantané
        Shockwave,       // 🌊 onde de choc : repousse tous les ennemis autour
    }
}
