import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Plus, Pencil, Eye, Trash2 } from 'lucide-react';

interface BattlePass {
    id: number;
    name: string;
    season_number: number;
    total_tiers: number;
    tiers_count: number;
    premium_price_shards: number;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    phase: 'scheduled' | 'current' | 'expired';
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const PHASE_LABEL: Record<BattlePass['phase'], { text: string; cls: string }> = {
    current:   { text: 'En cours',  cls: 'bg-success/15 text-success border-success/40' },
    scheduled: { text: 'À venir',   cls: 'bg-info/15 text-info border-info/40' },
    expired:   { text: 'Expirée',   cls: 'bg-text-low/15 text-text-low border-text-low/40' },
};

export default function BattlePassIndex({ battlePasses }: { battlePasses: BattlePass[] }) {
    const { props } = usePage<PageProps>();
    const rows = battlePasses ?? [];

    const destroy = (bp: BattlePass) => {
        if (!confirm(`Supprimer définitivement la saison « ${bp.name} » ? Les progressions joueurs seront aussi supprimées.`)) return;
        router.delete(`/admin/battle-passes/${bp.id}`);
    };

    return (
        <>
            <Head title="Admin · Battle Pass" />
            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Battle Pass</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{rows.length} saison(s) configurée(s)</p>
                </div>
                <Link href="/admin/battle-passes/create"><Button variant="shard" icon={<Plus size={14} />}>Nouvelle saison</Button></Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <p className="font-mono text-xs text-text-low mb-4">
                Règle : <strong className="text-text-medium">deux saisons ne peuvent pas se chevaucher temporellement</strong>.
                Une saison « En cours » est la seule visible par le joueur.
            </p>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Saison</th>
                            <th className="px-3 py-2 text-left">Période</th>
                            <th className="px-3 py-2 text-left">Phase</th>
                            <th className="px-3 py-2 text-left">Paliers</th>
                            <th className="px-3 py-2 text-left">Prix premium</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr><td colSpan={7} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucune saison Battle Pass — crée la première.</td></tr>
                        ) : rows.map(bp => {
                            const phase = PHASE_LABEL[bp.phase];
                            return (
                                <tr key={bp.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                    <td className="px-3 py-2 font-mono text-text-low">{bp.id}</td>
                                    <td className="px-3 py-2">
                                        <p className="font-display text-text-high">{bp.name}</p>
                                        <p className="font-mono text-xs text-text-low">Saison {bp.season_number}</p>
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">
                                        <p>{new Date(bp.starts_at).toLocaleDateString('fr-FR')}</p>
                                        <p>→ {new Date(bp.ends_at).toLocaleDateString('fr-FR')}</p>
                                    </td>
                                    <td className="px-3 py-2">
                                        <span className={`font-display text-xs uppercase px-1.5 py-0.5 rounded border ${phase.cls}`}>{phase.text}</span>
                                        {bp.is_active && <span className="ml-1 font-display text-xs uppercase px-1.5 py-0.5 rounded bg-shard-500/15 text-shard-400 border border-shard-500/40">Active</span>}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{bp.tiers_count} / {bp.total_tiers}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{bp.premium_price_shards.toLocaleString('fr-FR')} shards</td>
                                    <td className="px-3 py-2 text-right">
                                        <div className="inline-flex gap-1">
                                            <Link href={`/admin/battle-passes/${bp.id}`}><Button size="sm" variant="ghost" icon={<Eye size={12} />}>Voir</Button></Link>
                                            <Link href={`/admin/battle-passes/${bp.id}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                            <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(bp)}>Suppr</Button>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </section>
        </>
    );
}

BattlePassIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
