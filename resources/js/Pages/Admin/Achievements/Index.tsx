import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Pagination from '@ui/Pagination';
import { useState } from 'react';
import { Plus, Pencil, Trash2, EyeOff } from 'lucide-react';

interface Achievement {
    id: number;
    key: string;
    title: string;
    category: string;
    is_hidden: boolean;
    completed_count: number;
    rewards: Array<{ type: string; amount: number }> | null;
}

interface Props {
    achievements: { data: Achievement[]; links: any[]; from: number; to: number; total: number };
    filters: { q?: string; category?: string; hidden?: '1' | '0' };
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const EMPTY_PAGE = { data: [], links: [], from: 0, to: 0, total: 0 };

export default function AchievementsIndex({ achievements, filters }: Props) {
    const { props } = usePage<PageProps>();
    const page = achievements ?? EMPTY_PAGE;
    const rows = page.data ?? [];
    const f = filters ?? {};

    const [form, setForm] = useState({ q: f.q ?? '', category: f.category ?? '', hidden: f.hidden ?? '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/achievements', Object.fromEntries(Object.entries(form).filter(([_, v]) => v)), { preserveState: true });
    };

    const destroy = (a: Achievement) => {
        if (!confirm(`Supprimer l'achievement « ${a.title} » ?`)) return;
        router.delete(`/admin/achievements/${a.key}`);
    };

    return (
        <>
            <Head title="Admin · Achievements" />
            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Achievements</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{page.total} entrées</p>
                </div>
                <Link href="/admin/achievements/create"><Button variant="shard" icon={<Plus size={14} />}>Nouvel achievement</Button></Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-4 gap-3">
                <input type="search" placeholder="Recherche clé/titre" value={form.q} onChange={e => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm md:col-span-2" />
                <select value={form.category} onChange={e => setForm({ ...form, category: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Toutes catégories</option>
                    <option value="collection">collection</option>
                    <option value="combat">combat</option>
                    <option value="social">social</option>
                    <option value="progression">progression</option>
                    <option value="special">special</option>
                </select>
                <select value={form.hidden} onChange={e => setForm({ ...form, hidden: e.target.value as '' | '1' | '0' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous</option>
                    <option value="1">Cachés</option>
                    <option value="0">Visibles</option>
                </select>
                <div className="md:col-span-4 flex gap-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => { setForm({ q: '', category: '', hidden: '' }); router.get('/admin/achievements'); }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[700px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Clé</th>
                            <th className="px-3 py-2 text-left">Titre</th>
                            <th className="px-3 py-2 text-left">Catégorie</th>
                            <th className="px-3 py-2 text-left">Récompenses</th>
                            <th className="px-3 py-2 text-left">Débloqués</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0
                            ? <tr><td colSpan={7} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun achievement — crée le premier.</td></tr>
                            : rows.map(a => (
                                <tr key={a.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                    <td className="px-3 py-2 font-mono text-text-low">{a.id}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{a.key}</td>
                                    <td className="px-3 py-2 font-display text-text-high">
                                        {a.title}
                                        {a.is_hidden && <EyeOff size={12} className="inline ml-2 text-text-low" />}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{a.category}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-low">
                                        {(a.rewards ?? []).map((r, i) => <span key={i} className="block">+{r.amount} {r.type}</span>)}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{a.completed_count}</td>
                                    <td className="px-3 py-2 text-right">
                                        <div className="inline-flex gap-1">
                                            <Link href={`/admin/achievements/${a.key}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                            <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(a)}>Suppr</Button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        }
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">{page.from}–{page.to} sur {page.total}</p>
            <Pagination links={page.links} />
        </>
    );
}

AchievementsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
