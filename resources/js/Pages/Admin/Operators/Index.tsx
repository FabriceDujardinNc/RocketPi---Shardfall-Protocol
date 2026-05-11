import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import RarityBadge from '@game/RarityBadge';
import FactionBadge from '@game/FactionBadge';
import Pagination from '@ui/Pagination';
import Alert from '@ui/Alert';
import { useState } from 'react';
import { Plus, Pencil, Eye, Archive, RotateCcw } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';
type Faction = 'ORBIT' | 'FERRO' | 'VEIL';

interface Operator {
    id: number;
    name: string;
    codename: string;
    faction: Faction;
    role: string;
    rarity: Rarity;
    sort_order: number;
    is_available: boolean;
    is_rate_up: boolean;
    deleted_at: string | null;
}

interface Props {
    operators: {
        data: Operator[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: { q?: string; faction?: Faction; rarity?: Rarity; role?: string; trashed?: 'with' | 'only' };
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const EMPTY_PAGE = { data: [], links: [], from: 0, to: 0, total: 0 };

export default function OperatorsIndex({ operators, filters }: Props) {
    const { props } = usePage<PageProps>();
    const page = operators ?? EMPTY_PAGE;
    const rows = page.data ?? [];
    const f = filters ?? {};
    const [form, setForm] = useState({
        q:       f.q       ?? '',
        faction: f.faction ?? '',
        rarity:  f.rarity  ?? '',
        role:    f.role    ?? '',
        trashed: f.trashed ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = Object.fromEntries(Object.entries(form).filter(([_, v]) => v));
        router.get('/admin/operators', params, { preserveState: true });
    };

    const archive = (op: Operator) => {
        if (!confirm(`Archiver ${op.name} ?`)) return;
        router.delete(`/admin/operators/${op.id}`);
    };

    const restore = (op: Operator) => {
        router.post(`/admin/operators/${op.id}/restore`);
    };

    return (
        <>
            <Head title="Admin · Opérateurs" />

            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Opérateurs</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{page.total} entrées</p>
                </div>
                <Link href="/admin/operators/create">
                    <Button variant="shard" icon={<Plus size={14} />}>Nouvel opérateur</Button>
                </Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-5 gap-3">
                <input type="search" placeholder="Recherche nom/codename"
                    value={form.q} onChange={(e) => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 md:col-span-2" />
                <select value={form.faction} onChange={(e) => setForm({ ...form, faction: e.target.value as Faction | '' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Toutes factions</option>
                    <option value="ORBIT">ORBIT</option>
                    <option value="FERRO">FERRO</option>
                    <option value="VEIL">VEIL</option>
                </select>
                <select value={form.rarity} onChange={(e) => setForm({ ...form, rarity: e.target.value as Rarity | '' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Toutes raretés</option>
                    <option value="common">Commun</option>
                    <option value="rare">Rare</option>
                    <option value="epic">Épique</option>
                    <option value="legendary">Légendaire</option>
                </select>
                <select value={form.trashed} onChange={(e) => setForm({ ...form, trashed: e.target.value as '' | 'with' | 'only' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Actifs uniquement</option>
                    <option value="with">Inclure archivés</option>
                    <option value="only">Archivés seulement</option>
                </select>
                <div className="md:col-span-5 flex gap-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => {
                        setForm({ q: '', faction: '', rarity: '', role: '', trashed: '' });
                        router.get('/admin/operators');
                    }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Codename</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Faction</th>
                            <th className="px-3 py-2 text-left">Rareté</th>
                            <th className="px-3 py-2 text-left">Rôle</th>
                            <th className="px-3 py-2 text-left">Flags</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr><td colSpan={8} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun opérateur — ajuste les filtres ou crée le premier.</td></tr>
                        ) : rows.map(op => (
                            <tr key={op.id} className={'border-t border-border-default hover:bg-bg-elev2/50 ' + (op.deleted_at ? 'opacity-50' : '')}>
                                <td className="px-3 py-2 font-mono text-text-low">{op.id}</td>
                                <td className="px-3 py-2 font-mono text-text-medium">{op.codename}</td>
                                <td className="px-3 py-2 font-display text-text-high">{op.name}</td>
                                <td className="px-3 py-2"><FactionBadge faction={op.faction} /></td>
                                <td className="px-3 py-2"><RarityBadge rarity={op.rarity} /></td>
                                <td className="px-3 py-2 text-text-medium">{op.role}</td>
                                <td className="px-3 py-2 flex gap-1 flex-wrap">
                                    {!op.is_available && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">Retiré</span>}
                                    {op.is_rate_up && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-shard-500/15 text-shard-400 border border-shard-500/40">Rate-up</span>}
                                    {op.deleted_at && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-danger/15 text-danger border border-danger/40">Archivé</span>}
                                </td>
                                <td className="px-3 py-2 text-right">
                                    <div className="inline-flex gap-1">
                                        <Link href={`/admin/operators/${op.id}`}><Button size="sm" variant="ghost" icon={<Eye size={12} />}>Voir</Button></Link>
                                        {!op.deleted_at && (
                                            <>
                                                <Link href={`/admin/operators/${op.id}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                                <Button size="sm" variant="danger" icon={<Archive size={12} />} onClick={() => archive(op)}>Archiver</Button>
                                            </>
                                        )}
                                        {op.deleted_at && (
                                            <Button size="sm" variant="secondary" icon={<RotateCcw size={12} />} onClick={() => restore(op)}>Restaurer</Button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">{page.from}–{page.to} sur {page.total}</p>
            <Pagination links={page.links} />
        </>
    );
}

OperatorsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
