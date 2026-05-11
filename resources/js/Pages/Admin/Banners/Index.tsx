import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Pagination from '@ui/Pagination';
import Alert from '@ui/Alert';
import { useState } from 'react';
import { Plus, Pencil, Eye, Archive, RotateCcw, Power, PowerOff } from 'lucide-react';

interface Banner {
    id: number;
    name: string;
    type: 'permanent' | 'event' | 'faction' | 'collab';
    tag: string | null;
    subtitle: string | null;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    deleted_at: string | null;
}

interface Props {
    banners: { data: Banner[]; links: any[]; from: number; to: number; total: number };
    filters: { q?: string; type?: string; active?: '1' | '0'; trashed?: 'with' | 'only' };
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const EMPTY_PAGE = { data: [], links: [], from: 0, to: 0, total: 0 };

export default function BannersIndex({ banners, filters }: Props) {
    const { props } = usePage<PageProps>();
    const page = banners ?? EMPTY_PAGE;
    const rows = page.data ?? [];
    const f = filters ?? {};

    const [form, setForm] = useState({
        q: f.q ?? '', type: f.type ?? '', active: f.active ?? '', trashed: f.trashed ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/banners', Object.fromEntries(Object.entries(form).filter(([_, v]) => v)), { preserveState: true });
    };

    const archive = (b: Banner) => { if (confirm(`Archiver « ${b.name} » ?`)) router.delete(`/admin/banners/${b.id}`); };
    const restore = (b: Banner) => router.post(`/admin/banners/${b.id}/restore`);
    const toggle = (b: Banner) => router.post(`/admin/banners/${b.id}/activate`);

    return (
        <>
            <Head title="Admin · Bannières" />
            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Bannières</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{page.total} entrées</p>
                </div>
                <Link href="/admin/banners/create"><Button variant="shard" icon={<Plus size={14} />}>Nouvelle bannière</Button></Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-5 gap-3">
                <input type="search" placeholder="Recherche nom" value={form.q} onChange={e => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm md:col-span-2" />
                <select value={form.type} onChange={e => setForm({ ...form, type: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous types</option>
                    <option value="permanent">Permanent</option>
                    <option value="event">Événement</option>
                    <option value="faction">Faction</option>
                    <option value="collab">Collab</option>
                </select>
                <select value={form.active} onChange={e => setForm({ ...form, active: e.target.value as '' | '1' | '0' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous états</option>
                    <option value="1">Actives</option>
                    <option value="0">Inactives</option>
                </select>
                <select value={form.trashed} onChange={e => setForm({ ...form, trashed: e.target.value as '' | 'with' | 'only' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Non archivées</option>
                    <option value="with">Inclure archivées</option>
                    <option value="only">Archivées</option>
                </select>
                <div className="md:col-span-5 flex gap-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => { setForm({ q: '', type: '', active: '', trashed: '' }); router.get('/admin/banners'); }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Type</th>
                            <th className="px-3 py-2 text-left">Période</th>
                            <th className="px-3 py-2 text-left">État</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr><td colSpan={6} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucune bannière — crée la première.</td></tr>
                        ) : rows.map(b => (
                            <tr key={b.id} className={'border-t border-border-default hover:bg-bg-elev2/50 ' + (b.deleted_at ? 'opacity-50' : '')}>
                                <td className="px-3 py-2 font-mono text-text-low">{b.id}</td>
                                <td className="px-3 py-2">
                                    <p className="font-display text-text-high">{b.name}</p>
                                    {b.subtitle && <p className="font-mono text-xs text-text-low">{b.subtitle}</p>}
                                </td>
                                <td className="px-3 py-2 text-text-medium">{b.type}</td>
                                <td className="px-3 py-2 font-mono text-xs text-text-low">
                                    {b.starts_at && <p>Du {new Date(b.starts_at).toLocaleDateString('fr-FR')}</p>}
                                    {b.ends_at   && <p>Au {new Date(b.ends_at).toLocaleDateString('fr-FR')}</p>}
                                    {!b.starts_at && !b.ends_at && <span>Permanente</span>}
                                </td>
                                <td className="px-3 py-2">
                                    {b.deleted_at ? (
                                        <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-danger/15 text-danger border border-danger/40">Archivée</span>
                                    ) : b.is_active ? (
                                        <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-success/15 text-success border border-success/40">Active</span>
                                    ) : (
                                        <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">Inactive</span>
                                    )}
                                </td>
                                <td className="px-3 py-2 text-right">
                                    <div className="inline-flex gap-1">
                                        <Link href={`/admin/banners/${b.id}`}><Button size="sm" variant="ghost" icon={<Eye size={12} />}>Voir</Button></Link>
                                        {!b.deleted_at && (
                                            <>
                                                <Link href={`/admin/banners/${b.id}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                                <Button size="sm" variant={b.is_active ? 'danger' : 'shard'} icon={b.is_active ? <PowerOff size={12} /> : <Power size={12} />} onClick={() => toggle(b)}>
                                                    {b.is_active ? 'Désactiver' : 'Activer'}
                                                </Button>
                                                <Button size="sm" variant="danger" icon={<Archive size={12} />} onClick={() => archive(b)}>Archiver</Button>
                                            </>
                                        )}
                                        {b.deleted_at && (
                                            <Button size="sm" variant="secondary" icon={<RotateCcw size={12} />} onClick={() => restore(b)}>Restaurer</Button>
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

BannersIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
