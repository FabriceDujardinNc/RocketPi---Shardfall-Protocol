// Dtos.cs — DTOs requête/réponse pour l'API Laravel /api/unity/*.
//
// Les noms snake_case correspondent à ce que Laravel renvoie. On utilise
// Newtonsoft.Json (pas JsonUtility) car il supporte mieux les nullables,
// les attributs et les conversions de naming.

using Newtonsoft.Json;

namespace Rocketpi.RestClient
{
    // ── POST /api/unity/session/start ─────────────────────────────────────

    public class SessionStartRequest
    {
        [JsonProperty("mode")]              public string Mode;
        [JsonProperty("rank_type")]         public string RankType;
        [JsonProperty("operator_used_id")]  public int?   OperatorUsedId;
        [JsonProperty("client_fingerprint")]public string ClientFingerprint;
    }

    public class SessionStartResponse
    {
        [JsonProperty("session_token")] public string SessionToken;
        [JsonProperty("session_id")]    public int    SessionId;
        [JsonProperty("expires_in")]    public int    ExpiresInSeconds;
    }

    // ── POST /api/unity/match/result ──────────────────────────────────────

    public class MatchResultRequest
    {
        [JsonProperty("session_token")]    public string SessionToken;
        [JsonProperty("score")]            public int    Score;
        [JsonProperty("kills")]            public int    Kills;
        [JsonProperty("deaths")]           public int    Deaths;
        [JsonProperty("assists")]          public int    Assists;
        [JsonProperty("won")]              public bool   Won;
        [JsonProperty("is_mvp")]           public bool   IsMvp;
        [JsonProperty("duration_seconds")] public int    DurationSeconds;
    }

    public class MatchResultResponse
    {
        [JsonProperty("match_result_id")]    public int    MatchResultId;
        [JsonProperty("rank_points_delta")]  public int    RankPointsDelta;
        [JsonProperty("rank_points_after")]  public int    RankPointsAfter;
        [JsonProperty("tier_after")]         public string TierAfter;
        [JsonProperty("promoted")]           public bool   Promoted;
    }

    // ── Generic error ─────────────────────────────────────────────────────

    public class ApiError
    {
        [JsonProperty("error")]   public string Error;
        [JsonProperty("message")] public string Message;
    }
}
