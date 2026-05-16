import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Alert from '@ui/Alert';
import Button from '@ui/Button';
import GenerationStatusBadge, { type GenerationStatus } from '@game/GenerationStatusBadge';
import GlbViewer from '@game/GlbViewer';
import SkinForm from './SkinForm';
import { ArrowLeft, RotateCw } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface SkinPayload {
    id: number;
    operator_id: number;
    name: string;
    slug: string;
    rarity: Rarity;
    palette_json: Array<{ slot: string; hex: string }> | null;
    is_active: boolean;
    is_default: boolean;
    generation_status: GenerationStatus;
    meshy_task_id: string | null;
    model_url: string | null;
    texture_url: string | null;
    preview_url: string | null;
    operator: {
        id: number;
        slug: string;
        name: string;
        codename: string;
        base_model_url: string | null;
        base_generation_status: GenerationStatus;
    };
}

interface Props {
    skin: SkinPayload;
    operators: Array<{ id: number; name: string; codename: string; faction: string; rarity: Rarity }>;
    enums: { rarities: Rarity[] };
}

interface PageProps { flash?: { status?: string; error?: string }; [key: string]: unknown }

const NON_TERMINAL: GenerationStatus[] = ['queued', 'generating'];

export default function SkinEdit({ skin, operators, enums }: Props) {
    const { props } = usePage<PageProps>();

    const generate = () => {
        const force = skin.generation_status === 'ready';
        router.post(`/admin/skins/${skin.slug}/generate`, { force }, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Admin · Skin · ${skin.name}`} />
            <Link href="/admin/skins" className="text-text-low hover:text-text-high inline-flex items-center gap-1 text-sm mb-3">
                <ArrowLeft className="h-4 w-4" /> Retour liste skins
            </Link>

            <header className="mb-6 flex items-end justify-between flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{skin.name}</h1>
                    <p className="text-text-low text-sm mt-1">
                        Skin de <Link href={`/admin/operators/${skin.operator.slug}/assets`} className="text-shard-400 hover:text-shard-300">{skin.operator.name}</Link>
                        {' '}· slug <span className="font-mono">{skin.slug}</span>
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <GenerationStatusBadge status={skin.generation_status} />
                    <Button
                        variant={skin.generation_status === 'ready' ? 'ghost' : 'primary'}
                        disabled={NON_TERMINAL.includes(skin.generation_status)}
                        onClick={generate}
                        icon={<RotateCw size={14} />}
                    >
                        {skin.generation_status === 'ready' ? 'Regénérer' : 'Lancer la génération 3D'}
                    </Button>
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.flash?.error  && <div className="mb-4"><Alert variant="danger">{props.flash.error}</Alert></div>}

            {(() => {
                // Préfère le .glb retexturé (UVs et matériel propres) au fallback
                // base.glb + texture override (souvent UV-mismatched).
                const useOwnModel = skin.generation_status === 'ready' && !!skin.model_url;
                const useFallback = !useOwnModel
                    && skin.operator.base_model_url
                    && skin.operator.base_generation_status === 'ready';
                if (!useOwnModel && !useFallback) return null;

                const heading = useOwnModel
                    ? `Aperçu 3D — ${skin.name} (modèle retexturé)`
                    : `Aperçu 3D — ${skin.operator.name} ${skin.texture_url ? `avec texture ${skin.name}` : '(mesh de base, skin non générée)'}`;

                return (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6">
                        <h2 className="text-sm font-display uppercase tracking-wide text-text-low mb-3">
                            {heading}
                        </h2>
                        <GlbViewer
                            src={useOwnModel
                                ? `/storage/${skin.model_url}`
                                : `/storage/${skin.operator.base_model_url}`}
                            textureOverrideUrl={useOwnModel
                                ? null
                                : (skin.texture_url ? `/storage/${skin.texture_url}` : null)}
                            alt={`Aperçu 3D ${skin.name}`}
                            height={420}
                        />
                    </div>
                );
            })()}

            {(skin.preview_url || skin.texture_url) && (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 flex items-start gap-4">
                    {skin.preview_url ? (
                        <img
                            src={`/storage/${skin.preview_url}`}
                            alt={`Preview ${skin.name}`}
                            className="h-32 w-32 rounded border border-border-default bg-bg-elev2 object-cover flex-shrink-0"
                        />
                    ) : (
                        <div className="h-32 w-32 rounded border border-dashed border-border-default bg-bg-elev2 flex-shrink-0" />
                    )}
                    <div className="text-xs font-mono text-text-medium flex-1 min-w-0">
                        {skin.texture_url && (
                            <div>
                                <span className="text-text-low">Texture :</span>{' '}
                                <a href={`/storage/${skin.texture_url}`} target="_blank" rel="noopener" className="text-shard-400 hover:text-shard-300 break-all">/storage/{skin.texture_url}</a>
                            </div>
                        )}
                        {skin.preview_url && (
                            <div className="mt-1">
                                <span className="text-text-low">Preview :</span>{' '}
                                <a href={`/storage/${skin.preview_url}`} target="_blank" rel="noopener" className="text-shard-400 hover:text-shard-300 break-all">/storage/{skin.preview_url}</a>
                            </div>
                        )}
                        {skin.meshy_task_id && (
                            <div className="mt-1 text-text-low">Task Meshy : {skin.meshy_task_id}</div>
                        )}
                    </div>
                </div>
            )}

            <SkinForm
                initial={{
                    ...skin,
                    palette_json: skin.palette_json ?? [],
                }}
                operators={operators}
                enums={enums}
                submitLabel="Mettre à jour"
                submitUrl={`/admin/skins/${skin.slug}`}
                method="put"
            />
        </>
    );
}

SkinEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
