// BridgePayloadTests.cs — Garantit que la sérialisation JsonUtility des DTOs
// du bridge reste compatible avec ce que React envoie côté Laravel.

using NUnit.Framework;
using Rocketpi.Bridge;
using UnityEngine;

namespace Rocketpi.Tests
{
    public class BridgePayloadTests
    {
        [Test]
        public void ConfigPayload_RoundTrip_PreservesAllFields()
        {
            var json = @"{""apiBaseUrl"":""https://rocketpi.pro"",""apiToken"":""abc"",""userId"":42,""locale"":""fr"",""photonAppId"":""xyz""}";
            var p = JsonUtility.FromJson<ConfigPayload>(json);

            Assert.AreEqual("https://rocketpi.pro", p.apiBaseUrl);
            Assert.AreEqual("abc", p.apiToken);
            Assert.AreEqual(42, p.userId);
            Assert.AreEqual("fr", p.locale);
            Assert.AreEqual("xyz", p.photonAppId);
        }

        [Test]
        public void SessionPayload_HandlesMinusOneAsAbsentOperator()
        {
            var json = @"{""sessionToken"":""deadbeef"",""mode"":""training"",""rankType"":""casual"",""operatorUsedId"":-1}";
            var p = JsonUtility.FromJson<SessionPayload>(json);

            Assert.AreEqual("deadbeef", p.sessionToken);
            Assert.AreEqual("training", p.mode);
            Assert.AreEqual(-1, p.operatorUsedId);
        }

        [Test]
        public void MatchResultPayload_SerializesAllNumericFields()
        {
            var src = new MatchResultPayload
            {
                sessionToken = "tok",
                score = 1500,
                kills = 12,
                deaths = 4,
                assists = 3,
                won = true,
                isMvp = false,
                durationSeconds = 540,
            };

            var json = JsonUtility.ToJson(src);
            var back = JsonUtility.FromJson<MatchResultPayload>(json);

            Assert.AreEqual(1500, back.score);
            Assert.AreEqual(12, back.kills);
            Assert.IsTrue(back.won);
            Assert.IsFalse(back.isMvp);
            Assert.AreEqual(540, back.durationSeconds);
        }
    }
}
