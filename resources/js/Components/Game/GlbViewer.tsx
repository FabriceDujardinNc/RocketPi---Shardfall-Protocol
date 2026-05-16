import { Component, Suspense, useEffect, useState, type CSSProperties, type ErrorInfo, type ReactNode } from 'react';
import { Canvas } from '@react-three/fiber';
import { OrbitControls, Stage, useGLTF } from '@react-three/drei';
import { Mesh, MeshStandardMaterial, SRGBColorSpace, TextureLoader, type Texture } from 'three';

interface Props {
    src: string;
    /** Optionnel : URL d'une texture (PNG/KTX2) à appliquer en override sur tous
     *  les Mesh du .glb (slot _BaseMap / albedo). Sert à prévisualiser un skin
     *  qui est juste une texture déposée sur le mesh de base d'un opérateur. */
    textureOverrideUrl?: string | null;
    alt?: string;
    /** Hauteur CSS du viewer. Défaut 320px. */
    height?: number | string;
    className?: string;
}

function Model({ src, textureOverrideUrl }: { src: string; textureOverrideUrl?: string | null }) {
    const { scene } = useGLTF(src);
    const [overrideTexture, setOverrideTexture] = useState<Texture | null>(null);

    // Charge la texture override quand l'URL change. Pas via useLoader pour
    // ne pas remonter dans Suspense quand on switch entre skins.
    useEffect(() => {
        if (! textureOverrideUrl) {
            setOverrideTexture(null);
            return;
        }
        const loader = new TextureLoader();
        loader.setCrossOrigin('anonymous');
        let cancelled = false;
        loader.load(
            textureOverrideUrl,
            (tex) => {
                if (cancelled) return;
                tex.colorSpace = SRGBColorSpace;
                tex.flipY = false; // glTF convention
                setOverrideTexture(tex);
            },
            undefined,
            () => {
                if (! cancelled) setOverrideTexture(null);
            },
        );
        return () => {
            cancelled = true;
        };
    }, [textureOverrideUrl]);

    // MeshStandardMaterial + env HDRI (cf. Stage environment="city") = rendu PBR
    // qui restitue les ombres et reflets propres aux skins sombres (Eclipse, etc.).
    // Sans env map, ce material rendrait tout noir — la `environment` du <Stage>
    // est donc indispensable.
    useEffect(() => {
        if (! overrideTexture) return;
        const cleanups: Array<() => void> = [];
        scene.traverse((obj) => {
            if (obj instanceof Mesh) {
                const original = obj.material;
                const mat = new MeshStandardMaterial({
                    map: overrideTexture,
                    roughness: 0.7,
                    metalness: 0.3,
                });
                obj.material = mat;
                cleanups.push(() => {
                    obj.material = original;
                    mat.dispose();
                });
            }
        });
        return () => cleanups.forEach((fn) => fn());
    }, [scene, overrideTexture]);

    return <primitive object={scene} />;
}

class GlbErrorBoundary extends Component<{ onError: (msg: string) => void; children: ReactNode }, { hasError: boolean }> {
    state = { hasError: false };
    static getDerivedStateFromError() {
        return { hasError: true };
    }
    componentDidCatch(err: Error, _info: ErrorInfo) {
        this.props.onError(err.message || 'Erreur de chargement du modèle 3D.');
    }
    render() {
        return this.state.hasError ? null : this.props.children;
    }
}

export default function GlbViewer({ src, textureOverrideUrl, alt = 'Aperçu 3D', height = 320, className = '' }: Props) {
    const [error, setError] = useState<string | null>(null);

    const style: CSSProperties = {
        width: '100%',
        height: typeof height === 'number' ? `${height}px` : height,
        borderRadius: '0.5rem',
        overflow: 'hidden',
    };

    return (
        <div className={className} aria-label={alt}>
            <div className="bg-bg-elev2" style={style}>
                <Canvas shadows dpr={[1, 2]} camera={{ fov: 35, position: [0, 1, 4] }}>
                    <GlbErrorBoundary onError={setError}>
                        <Suspense fallback={null}>
                            <Stage environment="city" intensity={0.5} adjustCamera shadows="contact">
                                <Model src={src} textureOverrideUrl={textureOverrideUrl} />
                            </Stage>
                        </Suspense>
                    </GlbErrorBoundary>
                    <OrbitControls autoRotate autoRotateSpeed={0.7} enablePan={false} makeDefault />
                </Canvas>
            </div>
            {error && (
                <p className="text-danger text-xs mt-2 font-mono">⚠ {error}</p>
            )}
        </div>
    );
}
