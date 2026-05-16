import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Pagination from '@ui/Pagination';
import RarityBadge from '@game/RarityBadge';
import GenerationStatusBadge, { type GenerationStatus } from '@game/GenerationStatusBadge';
import { Plus, Pencil, Trash2, RotateCw } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface Skin {
    id: number;
    slug: string;
    name: string;
    rarity: Rarity;
    is_active: boolean;
    is_default: boolean;
    generation_status: GenerationStatus;
    texture_url: string | null;
    preview_url: string | null;
    operator: { id: number; slug: string; name: string; codename: string } | null;
}

interface OperatorLite {
    id: number;
    name: string;
    codename: string;
    rarity: Rarity;
}

interface Props {
    skins: {
        data: Skin[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: { q?: string; operator?: number; rarity?: Rarity; status?: GenerationStatus };
    operators: OperatorLite[];
}

interface PageProps { flash?: { status?: string; error?: string }; [key: string]: unknown }

const NON_TERMINAL: GenerationStatus[] = ['queued', 'generating'];

export default function SkinsIndex({ skins, filters, operators }: Props) {
    const { props } = usePage<PageProps>();
    const f = filters ?? {};
    const [form, setForm] = useState({
        q:        f.q        ?? '',
        operator: f.operator ? String(f.operator) : '',
        rarity:   f.rarity   ?? '',
        status:   f.status   ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = Object.fromEntries(Object.entries(form).filter(([, v]) => v));
        router.get('/admin/skins', params, { preserveState: true });
    };

    const destroy = (s: Skin) => {
        if (!confirm(`Supprimer définitivement le skin ${s.name} ?`)) return;
        router.delete(`/admin/skins/${s.slug}`);
    };

    const generate = (s: Skin) => {
        const force = s.generation_status === 'ready';
        router.post(`/admin/skins/${s.slug}/generate`, { force }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Admin · Skins 3D" />

            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Skins 3D</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{skins.total} entrées · variantes texture/material d'opérateur</p>
                </div>
                <Link href="/admin/skins/create">
                    <Button variant="shard" icon={<Plus size={14} />}>Nouveau skin</Button>
                </Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.flash?.error  && <div className="mb-4"><Alert variant="danger">{props.flash.error}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-5 gap-3">
                <input type="search" placeholder="Recherche nom"
                    value={form.q} onChange={(e) => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 md:col-span-2" />
                <select value={form.operator} onChange={(e) => setForm({ ...form, operator: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous opérateurs</option>
                    {operators.map(op => <option key={op.id} value={op.id}>{op.name} ({op.codename})</option>)}
                </select>
                <select value={form.rarity} onChange={(e) => setForm({ ...form, rarity: e.target.value as Rarity | '' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Toutes raretés</option>
                    <option value="common">Commun</option>
                    <option value="rare">Rare</option>
                    <option value="epic">Épique</option>
                    <option value="legendary">Légendaire</option>
                </select>
                <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value as GenerationStatus | '' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous statuts</option>
                    <option value="pending">Pending</option>
                    <option value="queued">Queued</option>
                    <option value="generating">Generating</option>
                    <option value="ready">Ready</option>
                    <option value="failed">Failed</option>
                </select>
                <div className="md:col-span-5 flex gap-2">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => {
                        setForm({ q: '', operator: '', rarity: '', status: '' });
                        router.get('/admin/skins');
                    }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[800px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left w-14">Preview</th>
                            <th className="px-3 py-2 text-left">Opérateur</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Rareté</th>
                            <th className="px-3 py-2 text-left">Flags</th>
                            <th className="px-3 py-2 text-left">3D</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {skins.data.length === 0 ? (
                            <tr><td colSpan={8} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun skin — crée le premier.</td></tr>
                        ) : skins.data.map(s => (
                            <tr key={s.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                <td className="px-3 py-2 font-mono text-text-low">{s.id}</td>
                                <td className="px-3 py-2">
                                    {s.preview_url ? (
                                        <img
                                            src={`/storage/${s.preview_url}`}
                                            alt={`Preview ${s.name}`}
                                            className="h-10 w-10 rounded border border-border-default bg-bg-elev2 object-cover"
                                        />
                                    ) : (
                                        <div className="h-10 w-10 rounded border border-dashed border-border-default bg-bg-elev2" />
                                    )}
                                </td>
                                <td className="px-3 py-2 text-text-medium">
                                    {s.operator ? (
                                        <Link href={`/admin/operators/${s.operator.slug}/assets`} className="hover:text-shard-400">
                                            {s.operator.codename} <span className="text-text-low">({s.operator.name})</span>
                                        </Link>
                                    ) : <span className="italic text-text-low">orphelin</span>}
                                </td>
                                <td className="px-3 py-2 font-display text-text-high">{s.name}</td>
                                <td className="px-3 py-2"><RarityBadge rarity={s.rarity} /></td>
                                <td className="px-3 py-2 flex gap-1 flex-wrap">
                                    {s.is_default && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-success/15 text-success border border-success/40">Défaut</span>}
                                    {!s.is_active && <span className="font-display text-xs uppercase px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">Inactif</span>}
                                </td>
                                <td className="px-3 py-2"><GenerationStatusBadge status={s.generation_status} /></td>
                                <td className="px-3 py-2 text-right">
                                    <div className="inline-flex gap-1">
                                        <Button
                                            size="sm"
                                            variant={s.generation_status === 'ready' ? 'ghost' : 'primary'}
                                            disabled={NON_TERMINAL.includes(s.generation_status)}
                                            icon={<RotateCw size={12} />}
                                            onClick={() => generate(s)}
                                        >
                                            {s.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                        </Button>
                                        <Link href={`/admin/skins/${s.slug}/edit`}>
                                            <Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button>
                                        </Link>
                                        <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(s)}>Suppr.</Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">{skins.from}–{skins.to} sur {skins.total}</p>
            <Pagination links={skins.links} />
        </>
    );
}

SkinsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
