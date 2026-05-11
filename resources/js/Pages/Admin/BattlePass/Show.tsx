import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { ArrowLeft, Pencil, Star } from 'lucide-react';

interface RewardLine { type: string; amount: number }
interface Tier {
    id: number;
    tier_number: number;
    xp_required: number;
    free_reward: RewardLine[] | null;
    premium_reward: RewardLine[] | null;
    is_milestone: boolean;
}
interface BattlePass {
    id: number;
    name: string;
    season_number: number;
    total_tiers: number;
    premium_price_shards: number;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    tiers: Tier[];
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function BattlePassShow({ battlePass }: { battlePass: BattlePass }) {
    const { props } = usePage<PageProps>();
    const tiers = battlePass.tiers ?? [];
    const milestones = tiers.filter(t => t.is_milestone);

    return (
        <>
            <Head title={`Admin · ${battlePass.name}`} />
            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <Link href="/admin/battle-passes" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                        <ArrowLeft size={12} /> Retour aux saisons
                    </Link>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{battlePass.name}</h1>
                    <p className="font-mono text-xs text-text-low mt-1">Saison {battlePass.season_number} · #{battlePass.id}</p>
                </div>
                <Link href={`/admin/battle-passes/${battlePass.id}/edit`}>
                    <Button variant="secondary" icon={<Pencil size={14} />}>Éditer</Button>
                </Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <div className="grid lg:grid-cols-2 gap-6 mb-8">
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Métadonnées</h2>
                    <Row k="Période"        v={`${new Date(battlePass.starts_at).toLocaleDateString('fr-FR')} → ${new Date(battlePass.ends_at).toLocaleDateString('fr-FR')}`} />
                    <Row k="Active"         v={battlePass.is_active ? '✓ oui' : '✗ non'} />
                    <Row k="Paliers total"  v={String(battlePass.total_tiers)} />
                    <Row k="Prix premium"   v={`${battlePass.premium_price_shards.toLocaleString('fr-FR')} shards`} />
                </section>

                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Milestones</h2>
                    {milestones.length === 0
                        ? <p className="font-mono text-xs text-text-low italic">Aucun palier marqué milestone.</p>
                        : milestones.map(t => (
                            <div key={t.id} className="flex justify-between font-mono text-sm py-1 border-b border-border-default/40 last:border-0">
                                <span className="text-text-medium">⭐ Palier {t.tier_number}</span>
                                <span className="text-text-low">{t.xp_required.toLocaleString('fr-FR')} XP</span>
                            </div>
                        ))
                    }
                </section>
            </div>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">Palier</th>
                            <th className="px-3 py-2 text-left">XP requis</th>
                            <th className="px-3 py-2 text-left">Free</th>
                            <th className="px-3 py-2 text-left">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tiers.map(t => (
                            <tr key={t.id} className="border-t border-border-default">
                                <td className="px-3 py-2 font-mono text-text-medium">
                                    {t.is_milestone && <Star size={10} className="inline mr-1 text-rarity-legendary" />}
                                    {t.tier_number}
                                </td>
                                <td className="px-3 py-2 font-mono text-xs text-text-low">{t.xp_required.toLocaleString('fr-FR')}</td>
                                <td className="px-3 py-2 font-mono text-xs">
                                    {(t.free_reward ?? []).map((r, i) => <span key={i} className="block text-text-medium">+{r.amount} {r.type}</span>)}
                                </td>
                                <td className="px-3 py-2 font-mono text-xs">
                                    {(t.premium_reward ?? []).map((r, i) => <span key={i} className="block text-rarity-legendary">+{r.amount} {r.type}</span>)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>
        </>
    );
}

function Row({ k, v }: { k: string; v: string }) {
    return (
        <div className="flex justify-between font-mono text-sm py-1 border-b border-border-default/40 last:border-0">
            <span className="text-text-low uppercase tracking-wide text-xs">{k}</span>
            <span className="text-text-high">{v}</span>
        </div>
    );
}

BattlePassShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
