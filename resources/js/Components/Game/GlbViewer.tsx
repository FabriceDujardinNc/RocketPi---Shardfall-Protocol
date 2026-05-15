import { useEffect, useRef, useState, type CSSProperties } from 'react';

// Web component <model-viewer> de Google — chargé une seule fois côté client.
// Pas de side-effects côté SSR : Inertia hydrate après mount.
let loaded = false;
function ensureLoaded() {
    if (loaded) return;
    loaded = true;
    void import('@google/model-viewer');
}

declare global {
    namespace JSX {
        interface IntrinsicElements {
            'model-viewer': React.DetailedHTMLProps<
                React.HTMLAttributes<HTMLElement> & {
                    src?: string;
                    alt?: string;
                    poster?: string;
                    'camera-controls'?: boolean | '';
                    'auto-rotate'?: boolean | '';
                    'auto-rotate-delay'?: string | number;
                    'rotation-per-second'?: string;
                    'shadow-intensity'?: string | number;
                    exposure?: string | number;
                    'environment-image'?: string;
                    'skybox-image'?: string;
                    'tone-mapping'?: 'auto' | 'aces' | 'commerce' | 'agx' | 'neutral';
                    'interaction-prompt'?: 'auto' | 'when-focused' | 'none';
                    'camera-orbit'?: string;
                    'field-of-view'?: string;
                    ar?: boolean | '';
                    loading?: 'lazy' | 'eager' | 'auto';
                    reveal?: 'auto' | 'manual';
                },
                HTMLElement
            >;
        }
    }
}

interface Props {
    src: string;
    alt?: string;
    /** Hauteur CSS du viewer. Défaut 320px. */
    height?: number | string;
    className?: string;
}

export default function GlbViewer({ src, alt = 'Aperçu 3D', height = 320, className = '' }: Props) {
    const ref = useRef<HTMLElement>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        ensureLoaded();
    }, []);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const onError = (e: Event) => {
            const detail = (e as CustomEvent).detail;
            setError(typeof detail === 'string' ? detail : 'Échec du chargement du modèle 3D.');
            // eslint-disable-next-line no-console
            console.error('[GlbViewer] error:', detail);
        };
        const onLoad = async () => {
            setError(null);
            // Workaround pour les meshes Meshy mode=preview : POSITION seul,
            // pas de NORMAL ni de matériau. Sans normales, three.js rend en
            // noir → on les recalcule à la volée pour avoir au moins une
            // surface ombrée visible.
            const anyEl = el as unknown as { model?: { materials: unknown[]; raw?: { scene?: unknown } } };
            const raw = anyEl.model?.raw?.scene as { traverse?: (cb: (n: unknown) => void) => void } | undefined;
            if (raw?.traverse) {
                const { Mesh, MeshStandardMaterial } = await import('three');
                raw.traverse((node) => {
                    const n = node as { isMesh?: boolean; geometry?: { attributes?: Record<string, unknown>; computeVertexNormals?: () => void }; material?: unknown };
                    if (!n.isMesh) return;
                    if (n.geometry?.attributes && !('normal' in n.geometry.attributes) && n.geometry.computeVertexNormals) {
                        n.geometry.computeVertexNormals();
                    }
                    if (!n.material) {
                        n.material = new MeshStandardMaterial({ color: 0xcccccc, roughness: 0.6, metalness: 0.1 });
                    }
                });
            }
        };
        el.addEventListener('error', onError as EventListener);
        el.addEventListener('load', onLoad);
        return () => {
            el.removeEventListener('error', onError as EventListener);
            el.removeEventListener('load', onLoad);
        };
    }, [src]);

    const style: CSSProperties = {
        width: '100%',
        height: typeof height === 'number' ? `${height}px` : height,
        backgroundColor: '#1a1a1a',
        borderRadius: '0.5rem',
        '--poster-color': 'transparent',
    } as CSSProperties;

    return (
        <div className={className}>
            <model-viewer
                ref={ref}
                src={src}
                alt={alt}
                camera-controls
                auto-rotate
                auto-rotate-delay="2000"
                shadow-intensity="1"
                exposure="1"
                environment-image="neutral"
                tone-mapping="aces"
                interaction-prompt="auto"
                loading="eager"
                reveal="auto"
                style={style}
            />
            {error && (
                <p className="text-danger text-xs mt-2 font-mono">⚠ {error}</p>
            )}
        </div>
    );
}
