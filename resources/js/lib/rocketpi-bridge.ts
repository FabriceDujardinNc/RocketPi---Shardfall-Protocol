// rocketpi-bridge.ts — installe window.rocketpi.* pour le bridge JS ↔ Unity.
//
// Le client Unity WebGL (unity-client/) appelle ces handlers via le jslib
// `Assets/Plugins/WebGL/RocketpiBridge.jslib`. Tout changement de signature
// doit être reflété simultanément côté Unity. Voir unity-client/docs/INTEGRATION.md.

export interface MatchResultPayload {
    sessionToken: string;
    score: number;
    kills: number;
    deaths: number;
    assists: number;
    won: boolean;
    isMvp: boolean;
    durationSeconds: number;
}

export type BridgeLogLevel = 'debug' | 'info' | 'warn' | 'error';

export interface BridgeHandlers {
    /** Unity est prêt à recevoir SendMessage('RocketpiBridge', 'OnConfig', ...). */
    onReady?: () => void;
    /** Match terminé — payload identique à ce qu'Unity poste à /api/unity/match/result. */
    onMatchFinished?: (payload: MatchResultPayload) => void;
    /** Unity demande à React de recharger la page (erreur fatale, session expirée…). */
    onRequestReload?: () => void;
    /** Logging cross-domain depuis Unity. */
    onLog?: (level: BridgeLogLevel, message: string) => void;
}

declare global {
    interface Window {
        rocketpi?: BridgeHandlers;
    }
}

/**
 * Installe `window.rocketpi.*` avec les handlers fournis et retourne une fonction
 * cleanup à appeler au unmount du composant pour éviter les fuites entre pages.
 *
 * Les handlers absents tombent sur des no-op sûrs côté jslib (`window.rocketpi`
 * existe toujours mais les méthodes manquantes sont ignorées par le bridge).
 */
export function installBridge(handlers: BridgeHandlers): () => void {
    const previous = window.rocketpi;
    window.rocketpi = {
        onReady: handlers.onReady,
        onMatchFinished: handlers.onMatchFinished,
        onRequestReload: handlers.onRequestReload,
        onLog: handlers.onLog ?? defaultLog,
    };

    return () => {
        window.rocketpi = previous;
    };
}

function defaultLog(level: BridgeLogLevel, message: string): void {
    const fn = level === 'error' ? console.error
        : level === 'warn'  ? console.warn
        : level === 'debug' ? console.debug
        : console.info;
    fn('[Unity]', message);
}
