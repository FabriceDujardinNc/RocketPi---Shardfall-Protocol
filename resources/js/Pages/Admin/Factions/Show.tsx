import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import RarityBadge from '@game/RarityBadge';
import { ArrowLeft, Pencil } from 'lucide-react';

interface Faction {
    slug: string;
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
    banner_image_url: string | null;
}

interface Operator {
    id: number;
    name: string;
    codename: string;
    role: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    is_available: boolean;
    is_rate_up: boolean;
    portrait_url: string | null;
}

interface Props {
    faction: Faction;
    operators: Operator[];
    byRarity: Record<string, Operator[]>;
    stats: { total: number; rate_up: number; unavailable: number };
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const RARITY_ORDER = ['legendary', 'epic', 'rare', 'common'] as const;
const RARITY_LABEL: Record<string, string> = {
    legendary: 'Légendaire',
    epic:      'Épique',
    rare:      'Rare',
    common:    'Commun',
};

export default function FactionShow({ faction, byRarity, stats }: Props) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title={`Admin · ${faction.slug}`} />
            <header className="mb-6">
                <Link href="/admin/factions" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour aux factions
                </Link>
                <div className="flex items-end justify-between gap-3 flex-wrap">
                    <div>
                        <h1 className="font-display font-bold text-3xl uppercase tracking-wide" style={{ color: `oklch(0.75 0.18 ${faction.color_hue})` }}>
                            {faction.name}
                        </h1>
                        {faction.tagline && <p className="text-text-medium text-sm mt-1">{faction.tagline}</p>}
                    </div>
                    <Link href={`/admin/factions/${faction.slug}/edit`}><Button variant="secondary" icon={<Pencil size={14} />}>Éditer la faction</Button></Link>
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <section className="grid md:grid-cols-3 gap-4 mb-6">
                <Stat label="Opérateurs" value={stats.total} />
                <Stat label="En rate-up" value={stats.rate_up} accent="shard" />
                <Stat label="Indisponibles" value={stats.unavailable} accent="warning" />
            </section>

            {faction.lore && (
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5 mb-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-2">Lore</h2>
                    <p className="text-text-medium text-sm whitespace-pre-wrap leading-relaxed">{faction.lore}</p>
                </section>
            )}

            <section>
                <h2 className="font-display font-bold text-lg uppercase tracking-wide mb-4">Collection — opérateurs de la faction</h2>
                {RARITY_ORDER.map(rarity => {
                    const list = byRarity?.[rarity] ?? [];
                    if (list.length === 0) return null;
                    return (
                        <div key={rarity} className="mb-6">
                            <h3 className="font-display text-xs uppercase tracking-wide text-text-low mb-2">{RARITY_LABEL[rarity]} ({list.length})</h3>
                            <div className="grid md:grid-cols-3 lg:grid-cols-4 gap-3">
                                {list.map(op => (
                                    <Link key={op.id} href={`/admin/operators/${op.slug}`}
                                        className="rounded-md bg-bg-elev1 border border-border-default p-3 hover:bg-bg-elev2 transition-colors duration-fast">
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="font-display text-text-high text-sm">{op.name}</span>
                                            <RarityBadge rarity={op.rarity} />
                                        </div>
                                        <p className="font-mono text-xs text-text-low">{op.codename} · {op.role}</p>
                                        <div className="mt-2 flex gap-1 flex-wrap">
                                            {!op.is_available && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">Retiré</span>}
                                            {op.is_rate_up && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-shard-500/15 text-shard-400 border border-shard-500/40">Rate-up</span>}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    );
                })}
                {Object.keys(byRarity ?? {}).length === 0 && (
                    <p className="text-text-low font-mono text-xs italic">Aucun opérateur dans cette faction.</p>
                )}
            </section>
        </>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'shard' | 'warning' }) {
    const color = accent === 'shard' ? 'text-shard-400' : accent === 'warning' ? 'text-warning' : 'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-3xl font-bold mt-2 ${color}`}>{value}</p>
        </div>
    );
}

FactionShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
