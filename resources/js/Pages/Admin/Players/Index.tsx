import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';

interface Player { id: number; name: string; email: string; is_banned: boolean }
interface Props {
    players: { data: Player[]; links: { url: string | null; label: string; active: boolean }[] };
    q: string;
}

export default function AdminPlayersIndex({ players, q }: Props) {
    const [search, setSearch] = useState(q);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/players', { q: search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Admin · Joueurs" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Joueurs</h1>

            <form onSubmit={submit} className="mb-6">
                <input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Email ou pseudo…"
                    className="w-full max-w-md h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                />
            </form>

            <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[700px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-4 py-3 text-left">ID</th>
                            <th className="px-4 py-3 text-left">Pseudo</th>
                            <th className="px-4 py-3 text-left">Email</th>
                            <th className="px-4 py-3 text-left">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        {players.data.length === 0 ? (
                            <tr><td colSpan={4} className="px-4 py-12 text-center text-text-medium">Aucun joueur.</td></tr>
                        ) : players.data.map(p => (
                            <tr key={p.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                <td className="px-4 py-3 font-mono text-text-low">{p.id}</td>
                                <td className="px-4 py-3">
                                    <Link href={`/admin/players/${p.id}`} className="text-shard-400 hover:text-shard-300">
                                        {p.name}
                                    </Link>
                                </td>
                                <td className="px-4 py-3 font-mono text-text-medium">{p.email}</td>
                                <td className="px-4 py-3">
                                    {p.is_banned
                                        ? <span className="text-danger font-display text-xs uppercase tracking-wide">Banni</span>
                                        : <span className="text-success font-display text-xs uppercase tracking-wide">Actif</span>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

AdminPlayersIndex.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
