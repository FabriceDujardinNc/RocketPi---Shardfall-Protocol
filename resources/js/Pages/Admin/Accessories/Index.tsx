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
type Slot = 'head' | 'face' | 'back' | 'hands' | 'legs';

interface Accessory {
    id: number;
    slug: string;
    name: string;
    slot: Slot;
    rarity: Rarity;
    is_active: boolean;
    generation_status: GenerationStatus;
    socket_name: string;
    base_model_url: string | null;
    preview_url: string | null;
    operators: Array<{ id: number; slug: string; name: string; codename: string }>;
}

interface Props {
    accessories: {
        data: Accessory[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: { q?: string; slot?: Slot; rarity?: Rarity; status?: GenerationStatus };
}

interface PageProps { flash?: { status?: string; error?: string }; [key: string]: unknown }

const NON_TERMINAL: GenerationStatus[] = ['queued', 'generating'];

export default function AccessoriesIndex({ accessories, filters }: Props) {
    const { props } = usePage<PageProps>();
    const f = filters ?? {};
    const [form, setForm] = useState({
        q:      f.q      ?? '',
        slot:   f.slot   ?? '',
        rarity: f.rarity ?? '',
        status: f.status ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = Object.fromEntries(Object.entries(form).filter(([, v]) => v));
        router.get('/admin/accessories', params, { preserveState: true });
    };

    const destroy = (a: Accessory) => {
        if (!confirm(`Supprimer l'accessoire ${a.name} ?`)) return;
        router.delete(`/admin/accessories/${a.slug}`);
    };

    const generate = (a: Accessory) => {
        const force = a.generation_status === 'ready';
        router.post(`/admin/accessories/${a.slug}/generate`, { force }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Admin · Accessoires 3D" />

            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Accessoires 3D</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{accessories.total} entrées · meshes attachés via socket</p>
                </div>
                <Link href="/admin/accessories/create">
                    <Button variant="shard" icon={<Plus size={14} />}>Nouvel accessoire</Button>
                </Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.flash?.error  && <div className="mb-4"><Alert variant="danger">{props.flash.error}</Alert></div>}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-5 gap-3">
                <input type="search" placeholder="Recherche nom"
                    value={form.q} onChange={(e) => setForm({ ...form, q: e.target.value })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 md:col-span-2" />
                <select value={form.slot} onChange={(e) => setForm({ ...form, slot: e.target.value as Slot | '' })}
                    className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm">
                    <option value="">Tous slots</option>
                    <option value="head">Head</option>
                    <option value="face">Face</option>
                    <option value="back">Back</option>
                    <option value="hands">Hands</option>
                    <option value="legs">Legs</option>
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
                        setForm({ q: '', slot: '', rarity: '', status: '' });
                        router.get('/admin/accessories');
                    }}>Reset</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[800px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left w-14">Preview</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Slot / Socket</th>
                            <th className="px-3 py-2 text-left">Rareté</th>
                            <th className="px-3 py-2 text-left">Opérateurs</th>
                            <th className="px-3 py-2 text-left">3D</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {accessories.data.length === 0 ? (
                            <tr><td colSpan={8} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun accessoire — crée le premier.</td></tr>
                        ) : accessories.data.map(a => (
                            <tr key={a.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                <td className="px-3 py-2 font-mono text-text-low">{a.id}</td>
                                <td className="px-3 py-2">
                                    {a.preview_url ? (
                                        <img
                                            src={`/storage/${a.preview_url}`}
                                            alt={`Preview ${a.name}`}
                                            className="h-10 w-10 rounded border border-border-default bg-bg-elev2 object-cover"
                                        />
                                    ) : (
                                        <div className="h-10 w-10 rounded border border-dashed border-border-default bg-bg-elev2" />
                                    )}
                                </td>
                                <td className="px-3 py-2 font-display text-text-high">{a.name}</td>
                                <td className="px-3 py-2 text-text-medium uppercase text-xs">
                                    {a.slot} <span className="text-text-low font-mono normal-case">({a.socket_name})</span>
                                </td>
                                <td className="px-3 py-2"><RarityBadge rarity={a.rarity} /></td>
                                <td className="px-3 py-2 text-xs text-text-low">
                                    {a.operators.length === 0
                                        ? <span className="italic">aucun</span>
                                        : a.operators.map(o => o.codename).join(', ')}
                                </td>
                                <td className="px-3 py-2"><GenerationStatusBadge status={a.generation_status} /></td>
                                <td className="px-3 py-2 text-right">
                                    <div className="inline-flex gap-1">
                                        <Button
                                            size="sm"
                                            variant={a.generation_status === 'ready' ? 'ghost' : 'primary'}
                                            disabled={NON_TERMINAL.includes(a.generation_status)}
                                            icon={<RotateCw size={12} />}
                                            onClick={() => generate(a)}
                                        >
                                            {a.generation_status === 'ready' ? 'Regénérer' : 'Générer'}
                                        </Button>
                                        <Link href={`/admin/accessories/${a.slug}/edit`}>
                                            <Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button>
                                        </Link>
                                        <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(a)}>Suppr.</Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">{accessories.from}–{accessories.to} sur {accessories.total}</p>
            <Pagination links={accessories.links} />
        </>
    );
}

AccessoriesIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
