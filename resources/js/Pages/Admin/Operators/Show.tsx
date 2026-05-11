import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import RarityBadge from '@game/RarityBadge';
import FactionBadge from '@game/FactionBadge';
import Alert from '@ui/Alert';
import { ArrowLeft, Pencil, Archive, RotateCcw } from 'lucide-react';

interface Ability { name: string; type: 'active' | 'passive' | 'ultimate'; description: string }
interface LoreUnlock { level: number; title: string; snippet: string | null }

interface Operator {
    id: number;
    name: string;
    codename: string;
    faction: 'ORBIT' | 'FERRO' | 'VEIL';
    role: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    lore: string | null;
    portrait_url: string | null;
    stat_hp: number;
    stat_damage: number;
    stat_mobility: number;
    weapon_name: string | null;
    weapon_description: string | null;
    abilities: Ability[] | null;
    lore_unlocks: LoreUnlock[] | null;
    is_available: boolean;
    is_rate_up: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function OperatorShow({ operator }: { operator: Operator }) {
    const { props } = usePage<PageProps>();
    const archive = () => {
        if (!confirm(`Archiver ${operator.name} ?`)) return;
        router.delete(`/admin/operators/${operator.id}`);
    };
    const restore = () => router.post(`/admin/operators/${operator.id}/restore`);

    return (
        <>
            <Head title={`Admin · ${operator.name}`} />
            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <Link href="/admin/operators" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                        <ArrowLeft size={12} /> Retour à la liste
                    </Link>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{operator.name}</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{operator.codename} · ID #{operator.id}</p>
                </div>
                <div className="flex gap-2">
                    {!operator.deleted_at ? (
                        <>
                            <Link href={`/admin/operators/${operator.id}/edit`}><Button variant="secondary" icon={<Pencil size={14} />}>Éditer</Button></Link>
                            <Button variant="danger" icon={<Archive size={14} />} onClick={archive}>Archiver</Button>
                        </>
                    ) : (
                        <Button variant="shard" icon={<RotateCcw size={14} />} onClick={restore}>Restaurer</Button>
                    )}
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {operator.deleted_at && <div className="mb-4"><Alert variant="warning">Cet opérateur est archivé depuis le {new Date(operator.deleted_at).toLocaleDateString('fr-FR')}.</Alert></div>}

            <div className="grid lg:grid-cols-[1fr_2fr] gap-6">
                <aside className="space-y-4">
                    {operator.portrait_url ? (
                        <img src={operator.portrait_url} alt={operator.name} className="w-full aspect-[3/4] object-cover rounded-lg border border-border-default" />
                    ) : (
                        <div className="w-full aspect-[3/4] rounded-lg border border-border-default bg-bg-elev1 flex items-center justify-center font-display text-4xl uppercase text-text-low">
                            {operator.name.slice(0, 2)}
                        </div>
                    )}
                    <div className="flex gap-2 flex-wrap">
                        <FactionBadge faction={operator.faction} />
                        <RarityBadge rarity={operator.rarity} />
                        <span className="font-display text-xs uppercase tracking-wide px-2 py-1 rounded bg-bg-elev2 border border-border-default text-text-medium">{operator.role}</span>
                    </div>
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 space-y-2">
                        <StatRow label="HP" value={operator.stat_hp} />
                        <StatRow label="Dégâts" value={operator.stat_damage} />
                        <StatRow label="Mobilité" value={operator.stat_mobility} />
                    </div>
                    <div className="font-mono text-xs text-text-low space-y-1">
                        <p>Disponibilité : {operator.is_available ? '✓ Actif' : '✗ Retiré'}</p>
                        <p>Rate-up : {operator.is_rate_up ? '✓ Boosté' : 'Standard'}</p>
                        <p>Ordre tri : {operator.sort_order}</p>
                        <p>Créé : {new Date(operator.created_at).toLocaleDateString('fr-FR')}</p>
                        <p>MAJ : {new Date(operator.updated_at).toLocaleDateString('fr-FR')}</p>
                    </div>
                </aside>

                <main className="space-y-6">
                    {(operator.weapon_name || operator.weapon_description) && (
                        <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-2">Arme signature</h2>
                            {operator.weapon_name && <p className="font-display text-text-high">{operator.weapon_name}</p>}
                            {operator.weapon_description && <p className="text-text-medium text-sm mt-2 whitespace-pre-wrap">{operator.weapon_description}</p>}
                        </section>
                    )}

                    {(operator.abilities?.length ?? 0) > 0 && (
                        <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Capacités</h2>
                            <div className="space-y-3">
                                {operator.abilities!.map((ab, i) => (
                                    <div key={i} className="border-l-2 border-shard-500/50 pl-3">
                                        <p className="font-display text-text-high">{ab.name} <span className="font-mono text-xs text-text-low ml-2 uppercase">[{ab.type}]</span></p>
                                        <p className="text-text-medium text-sm mt-1 whitespace-pre-wrap">{ab.description}</p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {operator.lore && (
                        <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-2">Lore (présentation)</h2>
                            <p className="text-text-medium text-sm whitespace-pre-wrap leading-relaxed">{operator.lore}</p>
                        </section>
                    )}

                    {(operator.lore_unlocks?.length ?? 0) > 0 && (
                        <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Lore progressif (paliers d'affinité)</h2>
                            <div className="space-y-4">
                                {operator.lore_unlocks!.map(u => (
                                    <div key={u.level} className="border-l-2 border-shard-500/50 pl-3">
                                        <p className="font-display text-text-high">{u.title} <span className="font-mono text-xs text-text-low ml-2">Niv. {u.level}</span></p>
                                        {u.snippet
                                            ? <p className="text-text-medium text-sm mt-1 whitespace-pre-wrap leading-relaxed">{u.snippet}</p>
                                            : <p className="text-text-low text-xs font-mono mt-1 italic">(vide — à compléter)</p>}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}
                </main>
            </div>
        </>
    );
}

function StatRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex justify-between font-mono text-sm">
            <span className="text-text-low uppercase tracking-wide">{label}</span>
            <span className="text-text-high font-display">{value}</span>
        </div>
    );
}

OperatorShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
