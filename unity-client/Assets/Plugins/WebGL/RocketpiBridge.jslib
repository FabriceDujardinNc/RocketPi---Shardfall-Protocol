// RocketpiBridge.jslib — Bridge JS exposé au C# via DllImport("__Internal").
//
// Délègue à window.rocketpi.* installé par resources/js/lib/rocketpi-bridge.ts
// côté Laravel. Si window.rocketpi n'est pas dispo (ex. build chargé hors page
// Inertia), on no-op silencieusement pour ne pas crasher Unity.

mergeInto(LibraryManager.library, {

  RocketpiNotifyReady: function () {
    if (typeof window !== 'undefined' && window.rocketpi && typeof window.rocketpi.onReady === 'function') {
      try { window.rocketpi.onReady(); } catch (e) { console.error('[Rocketpi] onReady threw', e); }
    } else {
      console.warn('[Rocketpi] window.rocketpi.onReady not available');
    }
  },

  RocketpiSubmitMatchResult: function (payloadJsonPtr) {
    var payloadJson = UTF8ToString(payloadJsonPtr);
    if (typeof window !== 'undefined' && window.rocketpi && typeof window.rocketpi.onMatchFinished === 'function') {
      try {
        var payload = JSON.parse(payloadJson);
        window.rocketpi.onMatchFinished(payload);
      } catch (e) {
        console.error('[Rocketpi] onMatchFinished threw', e);
      }
    }
  },

  RocketpiRequestReload: function () {
    if (typeof window !== 'undefined' && window.rocketpi && typeof window.rocketpi.onRequestReload === 'function') {
      try { window.rocketpi.onRequestReload(); } catch (e) { console.error('[Rocketpi] onRequestReload threw', e); }
    }
  },

  RocketpiLog: function (levelPtr, messagePtr) {
    var level = UTF8ToString(levelPtr);
    var message = UTF8ToString(messagePtr);
    if (typeof window !== 'undefined' && window.rocketpi && typeof window.rocketpi.onLog === 'function') {
      try { window.rocketpi.onLog(level, message); } catch (e) { /* swallow */ }
    } else {
      // Fallback console pour les builds chargés hors page Inertia
      var fn = console[level] || console.log;
      fn.call(console, '[Unity]', message);
    }
  },

});
