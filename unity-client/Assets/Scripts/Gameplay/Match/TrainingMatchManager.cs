// TrainingMatchManager.cs — Mode "Training" autoritaire serveur.
//
// Flow :
//  1. Au Start, écoute RocketpiBridge.OnSessionStarted (event poussé par
//     RocketpiBridge.OnSessionStart depuis JS).
//  2. Démarre un compte à rebours (durée fixe ex. 120s).
//  3. Compte les kills sur PracticeTarget + score.
//  4. À la fin (timer ou abandon), envoie via RocketpiApiClient.SubmitMatchResult.
//  5. Notifie React via RocketpiBridge.SubmitMatchResult (pour recharger /play).
//
// Ce manager est attaché à un GameObject de scène (cf. SceneScaffolder).

using System;
using System.Collections;
using System.Collections.Generic;
using Rocketpi.Bridge;
using Rocketpi.Gameplay.Targets;
using Rocketpi.RestClient;
using UnityEngine;

namespace Rocketpi.Gameplay.Match
{
    public class TrainingMatchManager : MonoBehaviour
    {
        [Header("Durée")]
        [SerializeField] private int _matchDurationSeconds = 120;
        [SerializeField] private int _minDurationForSubmit = 30;     // matche MatchService::MIN_DURATION_SECONDS côté Laravel

        [Header("Refs scène")]
        [SerializeField] private PracticeTarget[] _initialTargets;
        [SerializeField] private PlayerController _player;

        public int    CurrentScore  { get; private set; }
        public int    CurrentKills  { get; private set; }
        public float  TimeRemaining { get; private set; }
        public bool   IsRunning     { get; private set; }
        public string SessionToken  { get; private set; }

        public event Action<int>   OnScoreChanged;
        public event Action<float> OnTimeTick;
        public event Action<MatchResultResponse> OnMatchSubmitted;
        public event Action<string> OnMatchError;

        private readonly List<PracticeTarget> _targets = new();
        private float _matchStartedAt;

        // ── Lifecycle ──────────────────────────────────────────────────────

        private void Start()
        {
            if (RocketpiBridge.Instance != null)
                RocketpiBridge.Instance.OnSessionStarted += HandleSessionStarted;

            // Précâbler les cibles initiales de la scène.
            foreach (var t in _initialTargets) RegisterTarget(t);
        }

        private void OnDestroy()
        {
            if (RocketpiBridge.Instance != null)
                RocketpiBridge.Instance.OnSessionStarted -= HandleSessionStarted;
            foreach (var t in _targets)
                if (t != null) t.OnDestroyed -= HandleTargetDestroyed;
        }

        private void Update()
        {
            if (!IsRunning) return;

            TimeRemaining -= Time.deltaTime;
            OnTimeTick?.Invoke(TimeRemaining);
            if (TimeRemaining <= 0f) EndMatch(won: true);
        }

        // ── API publique ───────────────────────────────────────────────────

        public void RegisterTarget(PracticeTarget target)
        {
            if (target == null || _targets.Contains(target)) return;
            _targets.Add(target);
            target.OnDestroyed += HandleTargetDestroyed;
        }

        /// <summary>
        /// Enregistre un kill générique (ex. NPC opérateur abattu). Appelé par
        /// OperatorNpcController.HandleDeath. Compte le kill + ajoute le score même
        /// hors match formel (entraînement libre).
        /// </summary>
        public void RegisterKill(int scoreValue)
        {
            CurrentKills++;
            CurrentScore += scoreValue;
            OnScoreChanged?.Invoke(CurrentScore);
        }

        /// <summary>Annule explicitement la session sans soumission (joueur quitte).</summary>
        public void AbortMatch()
        {
            if (!IsRunning) return;
            IsRunning = false;
            RocketpiBridge.Instance?.Log("info", "Training match aborted by player.");
        }

        // ── Handlers ───────────────────────────────────────────────────────

        private void HandleSessionStarted(SessionPayload payload)
        {
            if (payload == null || string.IsNullOrEmpty(payload.sessionToken))
            {
                OnMatchError?.Invoke("SessionPayload invalide.");
                return;
            }
            if (payload.mode != "training")
            {
                // Pas notre mode, on ignore (les autres managers s'en chargeront).
                return;
            }

            SessionToken    = payload.sessionToken;
            CurrentScore    = 0;
            CurrentKills    = 0;
            TimeRemaining   = _matchDurationSeconds;
            _matchStartedAt = Time.time;
            IsRunning       = true;
            OnScoreChanged?.Invoke(0);
        }

        private void HandleTargetDestroyed(PracticeTarget t)
        {
            if (!IsRunning) return;
            CurrentKills++;
            CurrentScore += t.ScoreOnKill;
            OnScoreChanged?.Invoke(CurrentScore);
        }

        // ── Fin de match → soumission serveur ──────────────────────────────

        private void EndMatch(bool won)
        {
            if (!IsRunning) return;
            IsRunning = false;
            var duration = Mathf.RoundToInt(Time.time - _matchStartedAt);
            // Coup de sécurité côté client : si la durée est trop courte (joueur
            // qui spam "lancer/quitter"), on n'envoie pas, le serveur refuserait
            // de toute façon avec une erreur "durée trop courte".
            if (duration < _minDurationForSubmit)
            {
                OnMatchError?.Invoke($"Match trop court ({duration}s).");
                return;
            }
            StartCoroutine(SubmitToServer(duration, won));
        }

        private IEnumerator SubmitToServer(int durationSeconds, bool won)
        {
            var request = new MatchResultRequest
            {
                SessionToken    = SessionToken,
                Score           = CurrentScore,
                Kills           = CurrentKills,
                Deaths          = 0,
                Assists         = 0,
                Won             = won,
                IsMvp           = false,
                DurationSeconds = durationSeconds,
            };

            yield return RocketpiApiClient.Instance.SubmitMatchResult(
                request,
                onSuccess: response =>
                {
                    OnMatchSubmitted?.Invoke(response);

                    // Notifie React pour recharger la page Inertia avec le nouveau rang.
                    var notif = new MatchResultPayload
                    {
                        sessionToken    = SessionToken,
                        score           = request.Score,
                        kills           = request.Kills,
                        deaths          = 0,
                        assists         = 0,
                        won             = request.Won,
                        isMvp           = false,
                        durationSeconds = durationSeconds,
                    };
                    RocketpiBridge.Instance?.SubmitMatchResult(notif);
                },
                onError: err =>
                {
                    OnMatchError?.Invoke(err);
                    RocketpiBridge.Instance?.Log("error", $"Match result failed: {err}");
                }
            );
        }
    }
}
