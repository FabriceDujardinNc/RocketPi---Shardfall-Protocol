import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Card from '@ui/Card';
import Alert from '@ui/Alert';
import GenerationStatusBadge, { type GenerationStatus } from '@game/GenerationStatusBadge';
import GlbViewer from '@game/GlbViewer';
import { ArrowLeft, RotateCw, Boxes, Shirt, Wrench, ChevronDown, ChevronUp, Palette } from 'lucide-react';

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
    base_preview_url: string | null;
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
    preview_url: string | null;
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

    const [expandedSkins, setExpandedSkins] = useState<Set<number>>(new Set());
    const [expandedAccessories, setExpandedAccessories] = useState<Set<number>>(new Set());

    const toggleSkin = (id: number) => setExpandedSkins((prev) => {
        const next = new Set(prev);
        next.has(id) ? next.delete(id) : next.add(id);
        return next;
    });
    const toggleAccessory = (id: number) => setExpandedAccessories((prev) => {
        const next = new Set(prev);
        next.has(id) ? next.delete(id) : next.add(id);
        return next;
    });

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

    const refine = () => {
        if (!confirm(`Lancer le refine sur ${operator.name} ? Ça applique les textures PBR (couleur, métallicité, normal map) sur le mesh actuel. Coût ≈ 10 crédits Meshy.`)) return;
        router.post(`/admin/operators/${operator.slug}/assets/refine`, {}, { preserveScroll: true });
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

            {flash?.status && <div className="mb-4"><Alert variant="success">{flash.status}</Alert></div>}
            {flash?.error && <div className="mb-4"><Alert variant="danger">{flash.error}</Alert></div>}

            {/* ─── Mesh de base ───────────────────────────────────────── */}
            <Card className="mb-6">
                <div className="flex items-start justify-between gap-4 flex-wrap mb-4">
                    {operator.base_preview_url && (
                        <img
                            src={`/storage/${operator.base_preview_url}`}
                            alt={`Preview ${operator.name}`}
                            className="h-24 w-24 rounded-md border border-border-default bg-bg-elev2 object-cover flex-shrink-0"
                        />
                    )}
                    <div className="flex-1 min-w-0">
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
                        {operator.base_generation_status === 'ready' && (
                            <Button
                                variant="secondary"
                                onClick={refine}
                                title="Applique les textures PBR (couleurs) sur le mesh preview"
                            >
                                <Palette className="h-4 w-4" />
                                Coloriser (refine, ~10 cr)
                            </Button>
                        )}
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
                {operator.base_model_url && operator.base_generation_status === 'ready' && (
                    <GlbViewer
                        src={`/storage/${operator.base_model_url}`}
                        alt={`Mesh de base ${operator.name}`}
                        height={420}
                    />
                )}
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
                        {skins.map((skin) => {
                            const expanded = expandedSkins.has(skin.id);
                            // Skin viewable si l'opérateur a son mesh ET la skin a sa texture
                            const canView = !!operator.base_model_url && operator.base_generation_status === 'ready' && !!skin.texture_url;
                            return (
                                <li key={skin.id} className="border-b border-border-default last:border-0">
                                    <div className="flex items-center justify-between gap-4 py-2">
                                        <div className="flex items-center gap-3 min-w-0 flex-1">
                                            {skin.preview_url ? (
                                                <img
                                                    src={`/storage/${skin.preview_url}`}
                                                    alt={`Preview ${skin.name}`}
                                                    className="h-12 w-12 rounded border border-border-default bg-bg-elev2 object-cover flex-shrink-0"
                                                />
                                            ) : (
                                                <div className="h-12 w-12 rounded border border-border-default bg-bg-elev2 flex items-center justify-center flex-shrink-0">
                                                    <Shirt className="h-5 w-5 text-text-low" />
                                                </div>
                                            )}
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
                                        </div>
                                        <div className="flex gap-1">
                                            {canView && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => toggleSkin(skin.id)}
                                                >
                                                    {expanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
                                                    Voir 3D
                                                </Button>
                                            )}
                                            <Button
                                                variant={skin.generation_status === 'ready' ? 'ghost' : 'primary'}
                                                size="sm"
                                                disabled={NON_TERMINAL.includes(skin.generation_status)}
                                                onClick={() => trigger('operator_skin', skin.slug, skin.generation_status === 'ready')}
                                            >
                                                <RotateCw className="h-3 w-3" />
                                                {skin.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                            </Button>
                                        </div>
                                    </div>
                                    {canView && expanded && (
                                        <div className="pb-3">
                                            <GlbViewer
                                                src={`/storage/${operator.base_model_url}`}
                                                textureOverrideUrl={`/storage/${skin.texture_url}`}
                                                alt={`Aperçu 3D ${skin.name}`}
                                                height={280}
                                            />
                                        </div>
                                    )}
                                </li>
                            );
                        })}
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
                        {accessories.map((acc) => {
                            const expanded = expandedAccessories.has(acc.id);
                            const canView = !!acc.base_model_url && acc.generation_status === 'ready';
                            return (
                                <li key={acc.id} className="border-b border-border-default last:border-0">
                                    <div className="flex items-center justify-between gap-4 py-2">
                                        <div className="flex items-center gap-3 min-w-0 flex-1">
                                            {acc.preview_url ? (
                                                <img
                                                    src={`/storage/${acc.preview_url}`}
                                                    alt={`Preview ${acc.name}`}
                                                    className="h-12 w-12 rounded border border-border-default bg-bg-elev2 object-cover flex-shrink-0"
                                                />
                                            ) : (
                                                <div className="h-12 w-12 rounded border border-border-default bg-bg-elev2 flex items-center justify-center flex-shrink-0">
                                                    <Wrench className="h-5 w-5 text-text-low" />
                                                </div>
                                            )}
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
                                        </div>
                                        <div className="flex gap-1">
                                            {canView && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => toggleAccessory(acc.id)}
                                                >
                                                    {expanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
                                                    Voir 3D
                                                </Button>
                                            )}
                                            <Button
                                                variant={acc.generation_status === 'ready' ? 'ghost' : 'primary'}
                                                size="sm"
                                                disabled={NON_TERMINAL.includes(acc.generation_status)}
                                                onClick={() => trigger('accessory', acc.slug, acc.generation_status === 'ready')}
                                            >
                                                <RotateCw className="h-3 w-3" />
                                                {acc.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                            </Button>
                                        </div>
                                    </div>
                                    {canView && expanded && (
                                        <div className="pb-3">
                                            <GlbViewer
                                                src={`/storage/${acc.base_model_url}`}
                                                alt={`Aperçu 3D ${acc.name}`}
                                                height={280}
                                            />
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </Card>
        </>
    );
}

OperatorAssets.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
