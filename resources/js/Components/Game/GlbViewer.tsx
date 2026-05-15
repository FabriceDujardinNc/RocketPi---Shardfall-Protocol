import { Component, Suspense, useState, type CSSProperties, type ErrorInfo, type ReactNode } from 'react';
import { Canvas } from '@react-three/fiber';
import { OrbitControls, Stage, useGLTF } from '@react-three/drei';

interface Props {
    src: string;
    alt?: string;
    /** Hauteur CSS du viewer. Défaut 320px. */
    height?: number | string;
    className?: string;
}

function Model({ src }: { src: string }) {
    const { scene } = useGLTF(src);
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

export default function GlbViewer({ src, alt = 'Aperçu 3D', height = 320, className = '' }: Props) {
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
                            <Stage environment={null} intensity={1.5} adjustCamera shadows="contact">
                                <Model src={src} />
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
