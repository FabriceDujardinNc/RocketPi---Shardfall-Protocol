import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import RarityBadge from '@game/RarityBadge';
import Pagination from '@ui/Pagination';
import { useState } from 'react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface Log {
    id: number;
    created_at: string;
    rarity: Rarity;
    cost: number;
    pity_count_before: number;
    was_pity_hit: boolean;
    was_soft_pity: boolean;
    was_rate_up: boolean;
    session_id: string | null;
    ip_address: string | null;
    user: { id: number; name: string; email: string; display_name: string | null } | null;
    banner: { id: number; name: string } | null;
    operator: { id: number; name: string; codename: string; rarity: Rarity; faction: string } | null;
}

interface Props {
    logs: {
        data: Log[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        user?: string;
        banner?: number;
        rarity?: Rarity;
        from?: string;
        to?: string;
        pity?: string;
    };
    banners: Array<{ id: number; name: string }>;
    stats: { total: number; today: number; pity_hits: number };
}

export default function AdminGachaLogs({ logs, filters, banners, stats }: Props) {
    const [form, setForm] = useState({
        user:   filters.user   ?? '',
        banner: filters.banner ?? '',
        rarity: filters.rarity ?? '',
        from:   filters.from   ?? '',
        to:     filters.to     ?? '',
        pity:   filters.pity   ?? 'any',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = Object.fromEntries(Object.entries(form).filter(([_, v]) => v !== '' && v !== 'any'));
        router.get('/admin/gacha-logs', params, { preserveState: true });
    };

    const reset = () => {
        setForm({ user: '', banner: '', rarity: '', from: '', to: '', pity: 'any' });
        router.get('/admin/gacha-logs');
    };

    const exportUrl = '/admin/gacha-logs/export?' + new URLSearchParams(
        Object.entries(form).filter(([_, v]) => v !== '' && v !== 'any') as [string, string][]
    ).toString();

    return (
        <>
            <Head title="Admin · Logs Gacha" />

            <header className="mb-6">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Logs Gacha</h1>
                <p className="font-mono text-xs text-text-low mt-1">
                    Audit légal — table immuable, conservée indéfiniment
                </p>
            </header>

            <section className="grid md:grid-cols-3 gap-4 mb-6">
                <Stat label="Total tirages" value={stats.total} />
                <Stat label="Aujourd'hui" value={stats.today} accent="shard" />
                <Stat label="Pity activés" value={stats.pity_hits} accent="warning" />
            </section>

            {/* Filtres */}
            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-3 gap-3">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Joueur (email/nom)</span>
                    <input
                        type="search"
                        value={form.user}
                        onChange={(e) => setForm({ ...form, user: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Bannière</span>
                    <select
                        value={form.banner}
                        onChange={(e) => setForm({ ...form, banner: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    >
                        <option value="">Toutes</option>
                        {banners.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Rareté</span>
                    <select
                        value={form.rarity}
                        onChange={(e) => setForm({ ...form, rarity: e.target.value as Rarity | '' })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    >
                        <option value="">Toutes</option>
                        <option value="common">Commun</option>
                        <option value="rare">Rare</option>
                        <option value="epic">Épique</option>
                        <option value="legendary">Légendaire</option>
                    </select>
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Du</span>
                    <input
                        type="date"
                        value={form.from}
                        onChange={(e) => setForm({ ...form, from: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Au</span>
                    <input
                        type="date"
                        value={form.to}
                        onChange={(e) => setForm({ ...form, to: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Flag pity</span>
                    <select
                        value={form.pity}
                        onChange={(e) => setForm({ ...form, pity: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    >
                        <option value="any">N'importe</option>
                        <option value="hit">Pity hit</option>
                        <option value="soft">Soft pity</option>
                        <option value="rate_up">Rate-up</option>
                    </select>
                </label>
                <div className="md:col-span-3 flex gap-2 pt-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={reset}>Reset</Button>
                    <a href={exportUrl} download className="ml-auto">
                        <Button type="button" size="sm" variant="secondary">Export CSV</Button>
                    </a>
                </div>
            </form>

            {/* Table */}
            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[700px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Date</th>
                            <th className="px-3 py-2 text-left">Joueur</th>
                            <th className="px-3 py-2 text-left">Bannière</th>
                            <th className="px-3 py-2 text-left">Opérateur</th>
                            <th className="px-3 py-2 text-left">Rareté</th>
                            <th className="px-3 py-2 text-left">Pity</th>
                            <th className="px-3 py-2 text-left">Flags</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.length === 0 ? (
                            <tr>
                                <td colSpan={8} className="px-3 py-12 text-center text-text-medium font-mono text-sm">
                                    Aucun log — ajuste les filtres ou attends les premiers tirages.
                                </td>
                            </tr>
                        ) : logs.data.map(log => (
                            <tr key={log.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                <td className="px-3 py-2 font-mono text-text-low">{log.id}</td>
                                <td className="px-3 py-2 font-mono text-xs text-text-medium">{new Date(log.created_at).toLocaleString('fr-FR')}</td>
                                <td className="px-3 py-2">
                                    {log.user ? (
                                        <Link href={`/admin/players/${log.user.id}`} className="text-shard-400 hover:text-shard-300">
                                            {log.user.display_name ?? log.user.name}
                                        </Link>
                                    ) : '—'}
                                </td>
                                <td className="px-3 py-2 text-text-medium">{log.banner?.name ?? '—'}</td>
                                <td className="px-3 py-2">
                                    <span className="font-display text-text-high">{log.operator?.name}</span>
                                    <span className="font-mono text-xs text-text-low ml-1">{log.operator?.codename}</span>
                                </td>
                                <td className="px-3 py-2">
                                    <RarityBadge rarity={log.rarity} />
                                </td>
                                <td className="px-3 py-2 font-mono text-xs text-text-medium">{log.pity_count_before}</td>
                                <td className="px-3 py-2 flex gap-1 flex-wrap">
                                    {log.was_pity_hit && <span className="font-display text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">Pity</span>}
                                    {log.was_soft_pity && <span className="font-display text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-info/15 text-info border border-info/40">Soft</span>}
                                    {log.was_rate_up && <span className="font-display text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-shard-500/15 text-shard-400 border border-shard-500/40">Rate-up</span>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">
                {logs.from}–{logs.to} sur {logs.total}
            </p>

            <Pagination links={logs.links} />
        </>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'shard' | 'warning' }) {
    const color = accent === 'shard' ? 'text-shard-400' : accent === 'warning' ? 'text-warning' : 'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-2xl font-bold mt-2 ${color}`}>{value.toLocaleString('fr-FR')}</p>
        </div>
    );
}

AdminGachaLogs.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
