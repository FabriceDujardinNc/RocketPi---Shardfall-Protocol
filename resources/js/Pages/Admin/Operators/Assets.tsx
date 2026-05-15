import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Card from '@ui/Card';
import Alert from '@ui/Alert';
import GenerationStatusBadge, { type GenerationStatus } from '@game/GenerationStatusBadge';
import { ArrowLeft, RotateCw, Boxes, Shirt, Wrench } from 'lucide-react';

interface OperatorAsset {
    id: number;
    slug: string;
    codename: string;
    name: string;
    faction: 'ORBIT' | 'FERRO' | 'VEIL';
    rarity: string;
    base_generation_status: GenerationStatus;
    base_meshy_task_id: string | null;
    base_model_url: string | null;
    base_rig_version: string;
    updated_at: string;
}

interface SkinAsset {
    id: number;
    slug: string;
    name: string;
    rarity: string;
    is_active: boolean;
    generation_status: GenerationStatus;
    meshy_task_id: string | null;
    texture_url: string | null;
    preview_url: string | null;
    updated_at: string;
}

interface AccessoryAsset {
    id: number;
    slug: string;
    name: string;
    slot: string;
    socket_name: string;
    is_default: boolean;
    generation_status: GenerationStatus;
    meshy_task_id: string | null;
    base_model_url: string | null;
    updated_at: string;
}

interface PageProps {
    operator: OperatorAsset;
    skins: SkinAsset[];
    accessories: AccessoryAsset[];
    flash?: { status?: string; error?: string };
    [key: string]: unknown;
}

const POLL_INTERVAL_MS = 4000;
const NON_TERMINAL: GenerationStatus[] = ['queued', 'generating'];

function isPolling(operator: OperatorAsset, skins: SkinAsset[], accessories: AccessoryAsset[]): boolean {
    if (NON_TERMINAL.includes(operator.base_generation_status)) return true;
    if (skins.some((s) => NON_TERMINAL.includes(s.generation_status))) return true;
    if (accessories.some((a) => NON_TERMINAL.includes(a.generation_status))) return true;
    return false;
}

export default function OperatorAssets() {
    const { props } = usePage<PageProps>();
    const { operator, skins, accessories, flash } = props;

    useEffect(() => {
        if (!isPolling(operator, skins, accessories)) return;
        const id = setInterval(() => {
            router.reload({ only: ['operator', 'skins', 'accessories'] });
        }, POLL_INTERVAL_MS);
        return () => clearInterval(id);
    }, [operator, skins, accessories]);

    const trigger = (entityType: string, slug: string, force = false) => {
        router.post(
            `/admin/operators/${operator.slug}/assets/generate`,
            { entity_type: entityType, entity_slug: slug, force },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Admin · ${operator.name} · Assets 3D`} />

            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <Link href={`/admin/operators/${operator.slug}`} className="text-text-low hover:text-text-high inline-flex items-center gap-1 text-sm mb-2">
                        <ArrowLeft className="h-4 w-4" /> Retour fiche opérateur
                    </Link>
                    <h1 className="text-3xl font-display text-text-high">{operator.name} · Assets 3D</h1>
                    <p className="text-text-low text-sm mt-1">
                        {operator.codename} · {operator.faction} · rig {operator.base_rig_version}
                    </p>
                </div>
            </header>

            {flash?.status && <Alert variant="success" className="mb-4">{flash.status}</Alert>}
            {flash?.error && <Alert variant="danger" className="mb-4">{flash.error}</Alert>}

            {/* ─── Mesh de base ───────────────────────────────────────── */}
            <Card className="mb-6">
                <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div className="flex items-center gap-2 mb-2">
                            <Boxes className="h-5 w-5 text-text-medium" />
                            <h2 className="text-xl font-display text-text-high">Mesh de base</h2>
                            <GenerationStatusBadge status={operator.base_generation_status} />
                        </div>
                        {operator.base_model_url ? (
                            <a
                                href={`/storage/${operator.base_model_url}`}
                                target="_blank"
                                rel="noopener"
                                className="text-shard-400 hover:text-shard-300 text-sm font-mono"
                            >
                                /storage/{operator.base_model_url}
                            </a>
                        ) : (
                            <p className="text-text-low text-sm italic">Aucun fichier généré</p>
                        )}
                        {operator.base_meshy_task_id && (
                            <p className="text-text-low text-xs font-mono mt-1">task: {operator.base_meshy_task_id}</p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant={operator.base_generation_status === 'ready' ? 'ghost' : 'primary'}
                            disabled={NON_TERMINAL.includes(operator.base_generation_status)}
                            onClick={() => trigger('operator', operator.slug, operator.base_generation_status === 'ready')}
                        >
                            <RotateCw className="h-4 w-4" />
                            {operator.base_generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                        </Button>
                    </div>
                </div>
            </Card>

            {/* ─── Skins ──────────────────────────────────────────────── */}
            <Card className="mb-6">
                <div className="flex items-center gap-2 mb-4">
                    <Shirt className="h-5 w-5 text-text-medium" />
                    <h2 className="text-xl font-display text-text-high">Skins ({skins.length})</h2>
                </div>
                {skins.length === 0 ? (
                    <p className="text-text-low text-sm italic">Aucun skin lié à cet opérateur.</p>
                ) : (
                    <ul className="space-y-2">
                        {skins.map((skin) => (
                            <li key={skin.id} className="flex items-center justify-between gap-4 py-2 border-b border-border-default last:border-0">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="text-text-high font-display">{skin.name}</span>
                                        <GenerationStatusBadge status={skin.generation_status} />
                                        {skin.is_active && <span className="text-xs text-success">actif</span>}
                                    </div>
                                    {skin.texture_url && (
                                        <a
                                            href={`/storage/${skin.texture_url}`}
                                            target="_blank"
                                            rel="noopener"
                                            className="text-shard-400 hover:text-shard-300 text-xs font-mono truncate block"
                                        >
                                            /storage/{skin.texture_url}
                                        </a>
                                    )}
                                </div>
                                <Button
                                    variant={skin.generation_status === 'ready' ? 'ghost' : 'primary'}
                                    size="sm"
                                    disabled={NON_TERMINAL.includes(skin.generation_status)}
                                    onClick={() => trigger('operator_skin', skin.slug, skin.generation_status === 'ready')}
                                >
                                    <RotateCw className="h-3 w-3" />
                                    {skin.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            {/* ─── Accessoires ────────────────────────────────────────── */}
            <Card>
                <div className="flex items-center gap-2 mb-4">
                    <Wrench className="h-5 w-5 text-text-medium" />
                    <h2 className="text-xl font-display text-text-high">Accessoires ({accessories.length})</h2>
                </div>
                {accessories.length === 0 ? (
                    <p className="text-text-low text-sm italic">Aucun accessoire lié à cet opérateur.</p>
                ) : (
                    <ul className="space-y-2">
                        {accessories.map((acc) => (
                            <li key={acc.id} className="flex items-center justify-between gap-4 py-2 border-b border-border-default last:border-0">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="text-text-high font-display">{acc.name}</span>
                                        <span className="text-xs text-text-low uppercase">{acc.slot}</span>
                                        <span className="text-xs text-text-low font-mono">{acc.socket_name}</span>
                                        <GenerationStatusBadge status={acc.generation_status} />
                                        {acc.is_default && <span className="text-xs text-success">défaut</span>}
                                    </div>
                                    {acc.base_model_url && (
                                        <a
                                            href={`/storage/${acc.base_model_url}`}
                                            target="_blank"
                                            rel="noopener"
                                            className="text-shard-400 hover:text-shard-300 text-xs font-mono truncate block"
                                        >
                                            /storage/{acc.base_model_url}
                                        </a>
                                    )}
                                </div>
                                <Button
                                    variant={acc.generation_status === 'ready' ? 'ghost' : 'primary'}
                                    size="sm"
                                    disabled={NON_TERMINAL.includes(acc.generation_status)}
                                    onClick={() => trigger('accessory', acc.slug, acc.generation_status === 'ready')}
                                >
                                    <RotateCw className="h-3 w-3" />
                                    {acc.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </>
    );
}

OperatorAssets.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
