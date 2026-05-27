// HitscanWeapon.cs — Tir instantané par raycast. Pour fusils d'assaut, SMG, etc.
// Émet un event OnHit qui permet aux managers de match de récupérer les hits
// (utilisé par TrainingMatchManager pour calculer score/kills).

using System;
using UnityEngine;

namespace Rocketpi.Gameplay.Weapons
{
    public class HitscanWeapon : WeaponBase
    {
        [Header("Hitscan")]
        [SerializeField] private float _maxRange = 80f;
        [SerializeField] private LayerMask _hittableLayers = ~0;
        [SerializeField] private float _spreadAngle = 0.6f;        // degrés
        [SerializeField] private float _headshotMultiplier = 2f;

        /// <summary>Émis à chaque hit valide. Args: (target HealthSystem, damage dealt, was headshot).</summary>
        public event Action<HealthSystem, int, bool> OnHit;

        protected override void Fire()
        {
            // Aim 3rd person : on tire vers le CENTRE de l'écran (réticule), pas selon
            // Camera.forward. En 3rd person la caméra regarde le dos du joueur, donc
            // forward ≠ direction visée. ScreenPointToRay(centre) donne le bon rayon.
            var cam = (Owner != null && Owner.GetComponentInChildren<Camera>() is Camera ownerCam)
                ? ownerCam
                : Camera.main;

            Vector3 originPos, direction;
            if (cam != null)
            {
                var ray = cam.ScreenPointToRay(new Vector3(Screen.width * 0.5f, Screen.height * 0.5f, 0f));
                originPos = ray.origin;
                direction = ApplySpread(ray.direction);
            }
            else
            {
                originPos = transform.position;
                direction = ApplySpread(transform.forward);
            }

            // Points du tracer : muzzle → impacts successifs (rebonds).
            var from = _muzzle != null ? _muzzle.position : transform.position;
            var tracer = new System.Collections.Generic.List<Vector3> { from };

            var curOrigin = originPos;
            var curDir = direction;
            var maxBounces = BouncingBullets ? 3 : 0;
            var hitSomeone = false;

            for (var bounce = 0; bounce <= maxBounces && !hitSomeone; bounce++)
            {
                var hits = Physics.RaycastAll(curOrigin, curDir, _maxRange, _hittableLayers);
                System.Array.Sort(hits, (a, b) => a.distance.CompareTo(b.distance));

                var segmentResolved = false;
                foreach (var hit in hits)
                {
                    var health = hit.collider.GetComponentInParent<HealthSystem>();
                    if (Owner != null && health == Owner.Health) continue; // skip self
                    if (health != null && health.IsDead) continue;

                    tracer.Add(hit.point);

                    if (health != null)
                    {
                        // Cible vivante : dégâts (avec multiplicateur power-up).
                        var isHeadshot = hit.collider.CompareTag("Hitbox") &&
                                         hit.collider.gameObject.name.IndexOf("head", StringComparison.OrdinalIgnoreCase) >= 0;
                        var damage = Mathf.CeilToInt(_baseDamage * (isHeadshot ? _headshotMultiplier : 1f) * DamageMultiplier);
                        health.TakeDamage(damage);
                        OnHit?.Invoke(health, damage, isHeadshot);
                        hitSomeone = true;
                    }
                    else if (bounce < maxBounces)
                    {
                        // Mur : rebond (balles rebondissantes).
                        curDir = Vector3.Reflect(curDir, hit.normal).normalized;
                        curOrigin = hit.point + curDir * 0.05f;
                    }
                    segmentResolved = true;
                    break;
                }

                if (!segmentResolved)
                {
                    // Rien touché : segment droit jusqu'au bout de portée.
                    tracer.Add(curOrigin + curDir * _maxRange);
                    break;
                }
            }

            SpawnTracer(tracer);
        }

        private void SpawnTracer(System.Collections.Generic.List<Vector3> points)
        {
            if (points == null || points.Count < 2) return;
            var go = new GameObject("ShotTracer");
            var lr = go.AddComponent<LineRenderer>();
            lr.positionCount = points.Count;
            for (var i = 0; i < points.Count; i++) lr.SetPosition(i, points[i]);
            lr.startWidth = 0.04f;
            lr.endWidth   = 0.01f;
            lr.numCapVertices = 2;
            lr.material = new Material(
                Shader.Find("Universal Render Pipeline/Unlit")
                ?? Shader.Find("Sprites/Default"));
            var c0 = new Color(1f, 0.92f, 0.45f, 1f);
            var c1 = new Color(1f, 0.6f, 0.15f, 0.25f);
            lr.startColor = c0;
            lr.endColor   = c1;
            lr.material.SetColor("_BaseColor", c0);
            lr.material.SetColor("_Color", c0);
            Destroy(go, 0.06f);
        }

        private Vector3 ApplySpread(Vector3 forward)
        {
            if (_spreadAngle <= 0f) return forward;
            var x = UnityEngine.Random.Range(-_spreadAngle, _spreadAngle);
            var y = UnityEngine.Random.Range(-_spreadAngle, _spreadAngle);
            return Quaternion.Euler(x, y, 0f) * forward;
        }
    }
}
