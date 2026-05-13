import { useEffect, useRef, useState } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { installBridge, type MatchResultPayload } from '@/lib/rocketpi-bridge';

const wrapperStyles = cva(
    'relative rounded-lg overflow-hidden border border-border-default bg-black',
    {
        variants: {
            ratio: {
                wide: 'aspect-[16/9]',
                square: 'aspect-square',
                tall: 'aspect-[9/16]',
            },
        },
        defaultVariants: { ratio: 'wide' },
    }
);

type WrapperProps = VariantProps<typeof wrapperStyles>;

interface UnityInstance {
    SendMessage(gameObject: string, methodName: string, value?: string): void;
    Quit(): Promise<void>;
}

declare global {
    interface Window {
        createUnityInstance?: (
            canvas: HTMLCanvasElement,
            config: Record<string, unknown>,
            onProgress?: (progress: number) => void
        ) => Promise<UnityInstance>;
    }
}

interface UnityBuildManifest {
    version?: string;
    buildGuid?: string;
    builtAt?: string;
    isDevelopmentBuild?: boolean;
    /**
     * URLs relatives au buildPath. Si absent on tombe sur les défauts
     * "Build/Build.{loader.js, data.unityweb, framework.js.unityweb, wasm.unityweb}".
     */
    urls?: {
        loader?: string;
        data?: string;
        framework?: string;
        code?: string;
    };
}

interface Props extends WrapperProps {
    /** Bearer Sanctum éphémère (TTL court) — injecté depuis PlayController. */
    apiToken: string;
    apiBaseUrl: string;
    userId: number;
    locale?: string;
    photonAppId?: string | null;
    /** Base URL des assets WebGL (relatif à l'origin Laravel). */
    buildPath?: string;
    /** Notifie le parent quand Unity envoie un résultat de match. */
    onMatchFinished?: (payload: MatchResultPayload) => void;
    /** Notifie le parent que Unity demande un reload. */
    onRequestReload?: () => void;
}

type Status =
    | { kind: 'missing' }                            // Build pas trouvé → placeholder
    | { kind: 'loading'; progress: number }
    | { kind: 'ready' }
    | { kind: 'error'; message: string };

const DEFAULT_BUILD_PATH = '/unity';

export default function UnityCanvas({
    apiToken,
    apiBaseUrl,
    userId,
    locale = 'fr',
    photonAppId = null,
    buildPath = DEFAULT_BUILD_PATH,
    ratio,
    onMatchFinished,
    onRequestReload,
}: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const instanceRef = useRef<UnityInstance | null>(null);
    const [status, setStatus] = useState<Status>({ kind: 'loading', progress: 0 });

    // Installer/désinstaller window.rocketpi.* au montage du composant.
    useEffect(() => {
        const uninstall = installBridge({
            onReady: () => {
                const config = {
                    apiBaseUrl,
                    apiToken,
                    userId,
                    locale,
                    photonAppId: photonAppId ?? '',
                };
                instanceRef.current?.SendMessage(
                    'RocketpiBridge',
                    'OnConfig',
                    JSON.stringify(config)
                );
                setStatus({ kind: 'ready' });
            },
            onMatchFinished: (payload) => onMatchFinished?.(payload),
            onRequestReload: () => onRequestReload?.(),
        });
        return uninstall;
    }, [apiBaseUrl, apiToken, userId, locale, photonAppId, onMatchFinished, onRequestReload]);

    // Charger le build WebGL si présent (manifest.json sentinelle).
    useEffect(() => {
        let cancelled = false;
        let cleanupScript: (() => void) | null = null;

        (async () => {
            try {
                const manifestResp = await fetch(`${buildPath}/manifest.json`, { cache: 'no-store' });
                if (!manifestResp.ok) {
                    if (!cancelled) setStatus({ kind: 'missing' });
                    return;
                }
                const manifest = (await manifestResp.json()) as UnityBuildManifest;
                const cacheBust = encodeURIComponent(manifest.buildGuid ?? 'dev');
                const loaderRel = manifest.urls?.loader ?? 'Build/Build.loader.js';
                const dataRel = manifest.urls?.data ?? 'Build/Build.data.unityweb';
                const frameworkRel = manifest.urls?.framework ?? 'Build/Build.framework.js.unityweb';
                const codeRel = manifest.urls?.code ?? 'Build/Build.wasm.unityweb';

                const loaderUrl = `${buildPath}/${loaderRel}?v=${cacheBust}`;
                const script = document.createElement('script');
                script.src = loaderUrl;
                script.async = true;

                const onLoad = async () => {
                    if (cancelled) return;
                    if (typeof window.createUnityInstance !== 'function') {
                        setStatus({ kind: 'error', message: 'createUnityInstance non défini par le loader Unity.' });
                        return;
                    }
                    if (!canvasRef.current) return;
                    try {
                        instanceRef.current = await window.createUnityInstance(
                            canvasRef.current,
                            {
                                dataUrl: `${buildPath}/${dataRel}?v=${cacheBust}`,
                                frameworkUrl: `${buildPath}/${frameworkRel}?v=${cacheBust}`,
                                codeUrl: `${buildPath}/${codeRel}?v=${cacheBust}`,
                                streamingAssetsUrl: `${buildPath}/StreamingAssets`,
                                companyName: 'RocketPi',
                                productName: 'Shardfall Protocol',
                                productVersion: manifest.version ?? '0.0.0',
                            },
                            (progress) => {
                                if (!cancelled) setStatus({ kind: 'loading', progress });
                            }
                        );
                    } catch (e) {
                        if (!cancelled) {
                            setStatus({
                                kind: 'error',
                                message: e instanceof Error ? e.message : String(e),
                            });
                        }
                    }
                };

                const onError = () => {
                    if (!cancelled) setStatus({ kind: 'error', message: `Loader inaccessible : ${loaderUrl}` });
                };

                script.addEventListener('load', onLoad);
                script.addEventListener('error', onError);
                document.body.appendChild(script);

                cleanupScript = () => {
                    script.removeEventListener('load', onLoad);
                    script.removeEventListener('error', onError);
                    script.remove();
                };
            } catch (e) {
                if (!cancelled) setStatus({ kind: 'missing' });
            }
        })();

        return () => {
            cancelled = true;
            cleanupScript?.();
            instanceRef.current?.Quit().catch(() => { /* swallow */ });
            instanceRef.current = null;
        };
    }, [buildPath]);

    return (
        <div className={wrapperStyles({ ratio })} data-status={status.kind}>
            <canvas
                ref={canvasRef}
                id="unity-canvas"
                className="w-full h-full block"
                aria-label="Canvas de jeu Unity"
            />
            {status.kind === 'loading' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-bg-base/80 backdrop-blur-sm pointer-events-none">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-3">
                        Chargement du jeu
                    </p>
                    <div className="w-48 h-1 rounded-full bg-bg-elev2 overflow-hidden">
                        <div
                            className="h-full bg-gradient-to-r from-shard-400 to-shard-600 transition-all duration-fast"
                            style={{ width: `${Math.round(status.progress * 100)}%` }}
                        />
                    </div>
                </div>
            )}
            {status.kind === 'missing' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-2">Unity 6 WebGL</p>
                    <p className="font-body text-sm text-text-medium">
                        Build non disponible — déposer les fichiers dans <code className="text-text-high">public/unity/</code>.
                    </p>
                    <p className="font-mono text-xs text-text-low mt-3">
                        Cf. <span className="text-text-medium">unity-client/README.md</span> pour générer le build.
                    </p>
                </div>
            )}
            {status.kind === 'error' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center text-center px-6 bg-bg-base/90">
                    <p className="font-display text-xs uppercase tracking-mega text-danger mb-2">Erreur Unity</p>
                    <p className="font-body text-sm text-text-medium">{status.message}</p>
                </div>
            )}
        </div>
    );
}
