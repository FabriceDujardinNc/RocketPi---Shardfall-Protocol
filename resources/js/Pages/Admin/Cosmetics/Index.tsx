import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Pagination from '@ui/Pagination';
import RarityBadge from '@game/RarityBadge';
import { useState } from 'react';
import { Plus, Pencil, Trash2 } from 'lucide-react';

interface Cosmetic {
    id: number;
    slug: string;
    name: string;
    type: 'skin' | 'title' | 'voiceline' | 'banner' | 'border';
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    operator: { id: number; codename: string; name: string; faction: string } | null;
    preview_url: string | null;
    is_active: boolean;
    unlocked_by_count: number;
}

interface Props {
    cosmetics: { data: Cosmetic[]; links: any[]; from: number; to: number; total: number };
    filters: { q?: string; type?: string; rarity?: string; operator_id?: number };
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const EMPTY_PAGE = { data: [], links: [], from: 0, to: 0, total: 0 };

export default function CosmeticsIndex({ cosmetics, filters }: Props) {
    const { props } = usePage<PageProps>();
    const page = cosmetics ?? EMPTY_PAGE;
    const rows = page.data ?? [];
    const f = filters ?? {};

    const [form, setForm] = useState({ q: f.q ?? '', type: f.type ?? '', rarity: f.rarity ?? '' });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/cosmetics', Object.fromEntries(Object.entries(form).filter(([_, v]) => v)), { preserveState: true });
    };

    const destroy = (c: Cosmetic) => {
        if (!confirm(`Supprimer « ${c.name} » ? Les joueurs qui l'ont débloqué perdront leur déblocage.`)) return;
        router.delete(`/admin/cosmetics/${c.slug}`);
    };

    return (
        <>
            <Head title="Admin · Cosmétiques" />
            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Cosmétiques</h1>
                    <p className="font-mono text-xs text-text-low mt-1">
                        {page.total} entrée(s) · skins · titres · voicelines · bannières · bordures
                    </p>
                </div>
                <Link href="/admin/cosmetics/create"><Button variant="shard" icon={<Plus size={14} />}>Nouveau cosmétique</Button></Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-4 gap-3">
                <input type="search" placeholder="Recherche nom/slug" value={form.q} onChange={e => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm md:col-span-2" />
                <select value={form.type} onChange={e => setForm({ ...form, type: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous types</option>
                    <option value="skin">Skin</option>
                    <option value="title">Titre</option>
                    <option value="voiceline">Voiceline</option>
                    <option value="banner">Banner</option>
                    <option value="border">Bordure</option>
                </select>
                <select value={form.rarity} onChange={e => setForm({ ...form, rarity: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Toutes raretés</option>
                    <option value="common">Commun</option>
                    <option value="rare">Rare</option>
                    <option value="epic">Épique</option>
                    <option value="legendary">Légendaire</option>
                </select>
                <div className="md:col-span-4 flex gap-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => { setForm({ q: '', type: '', rarity: '' }); router.get('/admin/cosmetics'); }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[700px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">Aperçu</th>
                            <th className="px-3 py-2 text-left">Slug</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Type</th>
                            <th className="px-3 py-2 text-left">Rareté</th>
                            <th className="px-3 py-2 text-left">Opérateur</th>
                            <th className="px-3 py-2 text-left">Débloqués</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0
                            ? <tr><td colSpan={8} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun cosmétique — crée le premier.</td></tr>
                            : rows.map(c => (
                                <tr key={c.id} className={'border-t border-border-default hover:bg-bg-elev2/50 ' + (!c.is_active ? 'opacity-60' : '')}>
                                    <td className="px-3 py-2">
                                        {c.preview_url
                                            ? <img src={c.preview_url} alt="" className="w-12 h-12 object-cover rounded border border-border-default" />
                                            : <div className="w-12 h-12 rounded border border-border-default bg-bg-elev2" />}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-low">{c.slug}</td>
                                    <td className="px-3 py-2 font-display text-text-high">{c.name}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{c.type}</td>
                                    <td className="px-3 py-2"><RarityBadge rarity={c.rarity} /></td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-low">{c.operator ? `${c.operator.codename} (${c.operator.faction})` : '—'}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-text-medium">{c.unlocked_by_count}</td>
                                    <td className="px-3 py-2 text-right">
                                        <div className="inline-flex gap-1">
                                            <Link href={`/admin/cosmetics/${c.slug}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                            <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(c)}>Suppr</Button>
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

CosmeticsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
