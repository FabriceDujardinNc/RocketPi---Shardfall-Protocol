// SessionPayload.cs — DTOs de sérialisation JSON entre Laravel (Inertia/React)
// et le client Unity WebGL. Ces structures DOIVENT rester synchronisées avec
// resources/js/lib/rocketpi-bridge.ts côté Laravel.
//
// On utilise JsonUtility (intégré Unity) pour rester sans dépendance externe.
// Limitations connues de JsonUtility : pas de dictionnaires, pas de Nullable<T>
// pour les types valeur — d'où les conventions ci-dessous (int -1 pour "absent").

using System;
using UnityEngine;

namespace Rocketpi.Bridge
{
    /// <summary>
    /// Envoyé par React via SendMessage('RocketpiBridge', 'OnConfig', json)
    /// au boot, juste après l'instanciation Unity.
    /// </summary>
    [Serializable]
    public class ConfigPayload
    {
        /// <summary>Base URL de l'API REST Laravel, sans trailing slash. Ex : "https://rocketpi.pro".</summary>
        public string apiBaseUrl;

        /// <summary>Token Bearer Sanctum à envoyer en Authorization header. TTL court (1h).</summary>
        public string apiToken;

        /// <summary>ID interne du joueur Laravel (DB primary key).</summary>
        public int userId;

        /// <summary>Code locale (ex. "fr", "en"). Default "fr".</summary>
        public string locale;

        /// <summary>App ID Photon Fusion (peut être null si Photon désactivé).</summary>
        public string photonAppId;
    }

    /// <summary>
    /// Envoyé par React via SendMessage('RocketpiBridge', 'OnSessionStart', json)
    /// quand le joueur clique "Lancer un match".
    /// </summary>
    [Serializable]
    public class SessionPayload
    {
        /// <summary>Token de session (SHA-256 64 chars) issu par MatchService::start côté Laravel.</summary>
        public string sessionToken;

        /// <summary>Mode de jeu : "deathmatch" | "pve" | "custom" | "training".</summary>
        public string mode;

        /// <summary>Type de classement : "ranked" | "casual".</summary>
        public string rankType;

        /// <summary>ID opérateur sélectionné par le joueur. -1 si aucun (mode training).</summary>
        public int operatorUsedId;
    }

    /// <summary>
    /// Émis par Unity via RocketpiSubmitMatchResult(json) → window.rocketpi.onMatchFinished.
    /// Laravel re-valide ensuite via /api/unity/match/result (l'envoi REST direct se fait
    /// dans Unity, ce payload sert juste à notifier React pour recharger la page).
    /// </summary>
    [Serializable]
    public class MatchResultPayload
    {
        public string sessionToken;
        public int score;
        public int kills;
        public int deaths;
        public int assists;
        public bool won;
        public bool isMvp;
        public int durationSeconds;
    }
}
