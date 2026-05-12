// RocketpiApiClient.cs — Client REST côté Unity pour appeler /api/unity/*.
//
// Authentification : token Bearer Sanctum (TTL court, injecté par OnConfig
// dans RocketpiBridge.Config.apiToken). Voir unity-client/CLAUDE.md.
//
// On utilise UnityWebRequest (intégré, dispo sur WebGL) et Newtonsoft.Json
// pour la sérialisation. Pas d'async/await direct sur UnityWebRequest en
// 2022+ ils sont awaitables via une extension utilitaire incluse ici.

using System;
using System.Collections;
using System.Text;
using Newtonsoft.Json;
using Rocketpi.Bridge;
using UnityEngine;
using UnityEngine.Networking;

namespace Rocketpi.RestClient
{
    /// <summary>
    /// Singleton minimal qui sait parler à l'API Laravel via le token Sanctum
    /// reçu via RocketpiBridge.Config. Démarre tes coroutines via un MonoBehaviour
    /// host (cf. Gameplay/Match/TrainingMatchManager pour un exemple).
    /// </summary>
    public class RocketpiApiClient
    {
        private const int TIMEOUT_SECONDS = 15;

        public static RocketpiApiClient Instance { get; } = new();

        private static readonly JsonSerializerSettings JsonSettings = new()
        {
            NullValueHandling = NullValueHandling.Ignore,
            MissingMemberHandling = MissingMemberHandling.Ignore,
        };

        public string BaseUrl => RocketpiBridge.Instance?.Config?.apiBaseUrl;
        public string Token   => RocketpiBridge.Instance?.Config?.apiToken;
        public bool   IsConfigured => !string.IsNullOrEmpty(BaseUrl) && !string.IsNullOrEmpty(Token);

        // ── Endpoints ──────────────────────────────────────────────────────

        public IEnumerator StartSession(
            SessionStartRequest req,
            Action<SessionStartResponse> onSuccess,
            Action<string> onError)
        {
            return PostJson($"{BaseUrl}/api/unity/session/start", req, onSuccess, onError);
        }

        public IEnumerator SubmitMatchResult(
            MatchResultRequest req,
            Action<MatchResultResponse> onSuccess,
            Action<string> onError)
        {
            return PostJson($"{BaseUrl}/api/unity/match/result", req, onSuccess, onError);
        }

        // ── Plomberie ──────────────────────────────────────────────────────

        private IEnumerator PostJson<TRequest, TResponse>(
            string url,
            TRequest body,
            Action<TResponse> onSuccess,
            Action<string> onError)
        {
            if (!IsConfigured)
            {
                onError?.Invoke("API client non configuré (RocketpiBridge.Config absent).");
                yield break;
            }

            var json = JsonConvert.SerializeObject(body, JsonSettings);
            var bytes = Encoding.UTF8.GetBytes(json);

            using var req = new UnityWebRequest(url, UnityWebRequest.kHttpVerbPOST)
            {
                uploadHandler   = new UploadHandlerRaw(bytes),
                downloadHandler = new DownloadHandlerBuffer(),
                timeout         = TIMEOUT_SECONDS,
            };
            req.SetRequestHeader("Content-Type", "application/json");
            req.SetRequestHeader("Accept", "application/json");
            req.SetRequestHeader("Authorization", $"Bearer {Token}");

            yield return req.SendWebRequest();

            if (req.result != UnityWebRequest.Result.Success)
            {
                var detail = TryExtractError(req.downloadHandler?.text);
                onError?.Invoke($"{req.responseCode} — {detail ?? req.error}");
                yield break;
            }

            try
            {
                var parsed = JsonConvert.DeserializeObject<TResponse>(req.downloadHandler.text, JsonSettings);
                onSuccess?.Invoke(parsed);
            }
            catch (Exception e)
            {
                onError?.Invoke($"Parse JSON failed : {e.Message}");
            }
        }

        private static string TryExtractError(string body)
        {
            if (string.IsNullOrEmpty(body)) return null;
            try
            {
                var err = JsonConvert.DeserializeObject<ApiError>(body, JsonSettings);
                return err?.Message ?? err?.Error;
            }
            catch
            {
                return body.Length > 200 ? body.Substring(0, 200) : body;
            }
        }
    }
}
