import { useEffect, type CSSProperties } from 'react';

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
    useEffect(() => { ensureLoaded(); }, []);

    const style: CSSProperties = {
        width: '100%',
        height: typeof height === 'number' ? `${height}px` : height,
        backgroundColor: 'var(--color-bg-elev1, #111)',
        borderRadius: '0.5rem',
    };

    return (
        <model-viewer
            src={src}
            alt={alt}
            camera-controls
            auto-rotate
            shadow-intensity="1"
            exposure="1"
            loading="lazy"
            reveal="auto"
            style={style}
            className={className}
        />
    );
}
