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
        const onLoad = () => setError(null);
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
