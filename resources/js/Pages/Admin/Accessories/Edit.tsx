import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Alert from '@ui/Alert';
import Button from '@ui/Button';
import GenerationStatusBadge, { type GenerationStatus } from '@game/GenerationStatusBadge';
import GlbViewer from '@game/GlbViewer';
import AccessoryForm from './AccessoryForm';
import { ArrowLeft, RotateCw } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';
type Slot = 'head' | 'face' | 'back' | 'hands' | 'legs';

interface AccessoryPayload {
    id: number;
    name: string;
    slug: string;
    slot: Slot;
    rarity: Rarity;
    socket_name: string;
    is_active: boolean;
    generation_status: GenerationStatus;
    meshy_task_id: string | null;
    base_model_url: string | null;
    preview_url: string | null;
    operator_ids: number[];
    default_operator_id: number | null;
}

interface Props {
    accessory: AccessoryPayload;
    operators: Array<{ id: number; name: string; codename: string; faction: string; rarity: Rarity }>;
    enums: { slots: Slot[]; rarities: Rarity[]; sockets: Record<Slot, string> };
}

interface PageProps { flash?: { status?: string; error?: string }; [key: string]: unknown }

const NON_TERMINAL: GenerationStatus[] = ['queued', 'generating'];

export default function AccessoryEdit({ accessory, operators, enums }: Props) {
    const { props } = usePage<PageProps>();

    const generate = () => {
        const force = accessory.generation_status === 'ready';
        router.post(`/admin/accessories/${accessory.slug}/generate`, { force }, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Admin · Accessoire · ${accessory.name}`} />
            <Link href="/admin/accessories" className="text-text-low hover:text-text-high inline-flex items-center gap-1 text-sm mb-3">
                <ArrowLeft className="h-4 w-4" /> Retour liste accessoires
            </Link>

            <header className="mb-6 flex items-end justify-between flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{accessory.name}</h1>
                    <p className="text-text-low text-sm mt-1">
                        Slot {accessory.slot} · socket <span className="font-mono">{accessory.socket_name}</span> · slug <span className="font-mono">{accessory.slug}</span>
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <GenerationStatusBadge status={accessory.generation_status} />
                    <Button
                        variant={accessory.generation_status === 'ready' ? 'ghost' : 'primary'}
                        disabled={NON_TERMINAL.includes(accessory.generation_status)}
                        onClick={generate}
                        icon={<RotateCw size={14} />}
                    >
                        {accessory.generation_status === 'ready' ? 'Regénérer' : 'Lancer la génération 3D'}
                    </Button>
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.flash?.error  && <div className="mb-4"><Alert variant="danger">{props.flash.error}</Alert></div>}

            {accessory.base_model_url && accessory.generation_status === 'ready' && (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6">
                    <h2 className="text-sm font-display uppercase tracking-wide text-text-low mb-3">
                        Aperçu 3D — {accessory.name}
                    </h2>
                    <GlbViewer
                        src={`/storage/${accessory.base_model_url}`}
                        alt={`Aperçu 3D ${accessory.name}`}
                        height={420}
                    />
                </div>
            )}

            {(accessory.preview_url || accessory.base_model_url) && (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 flex items-start gap-4">
                    {accessory.preview_url ? (
                        <img
                            src={`/storage/${accessory.preview_url}`}
                            alt={`Preview ${accessory.name}`}
                            className="h-32 w-32 rounded border border-border-default bg-bg-elev2 object-cover flex-shrink-0"
                        />
                    ) : (
                        <div className="h-32 w-32 rounded border border-dashed border-border-default bg-bg-elev2 flex-shrink-0" />
                    )}
                    <div className="text-xs font-mono text-text-medium flex-1 min-w-0">
                        {accessory.base_model_url && (
                            <div>
                                <span className="text-text-low">Mesh :</span>{' '}
                                <a href={`/storage/${accessory.base_model_url}`} target="_blank" rel="noopener" className="text-shard-400 hover:text-shard-300 break-all">/storage/{accessory.base_model_url}</a>
                            </div>
                        )}
                        {accessory.preview_url && (
                            <div className="mt-1">
                                <span className="text-text-low">Preview :</span>{' '}
                                <a href={`/storage/${accessory.preview_url}`} target="_blank" rel="noopener" className="text-shard-400 hover:text-shard-300 break-all">/storage/{accessory.preview_url}</a>
                            </div>
                        )}
                        {accessory.meshy_task_id && (
                            <div className="mt-1 text-text-low">Task Meshy : {accessory.meshy_task_id}</div>
                        )}
                    </div>
                </div>
            )}

            <AccessoryForm
                initial={accessory}
                operators={operators}
                enums={enums}
                submitLabel="Mettre à jour"
                submitUrl={`/admin/accessories/${accessory.slug}`}
                method="put"
            />
        </>
    );
}

AccessoryEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
