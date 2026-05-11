import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Plus, Pencil, Trash2 } from 'lucide-react';

interface EventRow {
    id: number;
    name: string;
    type: string;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    phase: 'scheduled' | 'current' | 'expired';
    banner?: { id: number; name: string } | null;
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const PHASE: Record<EventRow['phase'], { text: string; cls: string }> = {
    current:   { text: 'En cours',  cls: 'bg-success/15 text-success border-success/40' },
    scheduled: { text: 'À venir',   cls: 'bg-info/15 text-info border-info/40' },
    expired:   { text: 'Expirée',   cls: 'bg-text-low/15 text-text-low border-text-low/40' },
};

export default function EventsIndex({ events }: { events: EventRow[] }) {
    const { props } = usePage<PageProps>();
    const rows = events ?? [];

    const destroy = (e: EventRow) => {
        if (!confirm(`Supprimer l'événement « ${e.name} » ?`)) return;
        router.delete(`/admin/events/${e.slug}`);
    };

    return (
        <>
            <Head title="Admin · Événements" />
            <header className="flex justify-between items-end mb-6 flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Événements</h1>
                    <p className="font-mono text-xs text-text-low mt-1">{rows.length} événement(s) configuré(s)</p>
                </div>
                <Link href="/admin/events/create"><Button variant="shard" icon={<Plus size={14} />}>Nouvel événement</Button></Link>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Nom</th>
                            <th className="px-3 py-2 text-left">Type</th>
                            <th className="px-3 py-2 text-left">Bannière</th>
                            <th className="px-3 py-2 text-left">Période</th>
                            <th className="px-3 py-2 text-left">Phase</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0
                            ? <tr><td colSpan={7} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun événement — crée le premier.</td></tr>
                            : rows.map(e => {
                                const ph = PHASE[e.phase];
                                return (
                                    <tr key={e.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                        <td className="px-3 py-2 font-mono text-text-low">{e.id}</td>
                                        <td className="px-3 py-2 font-display text-text-high">{e.name}</td>
                                        <td className="px-3 py-2 font-mono text-xs text-text-medium">{e.type}</td>
                                        <td className="px-3 py-2 font-mono text-xs text-text-low">{e.banner?.name ?? '—'}</td>
                                        <td className="px-3 py-2 font-mono text-xs text-text-low">
                                            <p>{new Date(e.starts_at).toLocaleDateString('fr-FR')}</p>
                                            <p>→ {new Date(e.ends_at).toLocaleDateString('fr-FR')}</p>
                                        </td>
                                        <td className="px-3 py-2">
                                            <span className={`font-display text-xs uppercase px-1.5 py-0.5 rounded border ${ph.cls}`}>{ph.text}</span>
                                            {e.is_active && <span className="ml-1 font-display text-xs uppercase px-1.5 py-0.5 rounded bg-shard-500/15 text-shard-400 border border-shard-500/40">Active</span>}
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <div className="inline-flex gap-1">
                                                <Link href={`/admin/events/${e.slug}/edit`}><Button size="sm" variant="secondary" icon={<Pencil size={12} />}>Éditer</Button></Link>
                                                <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => destroy(e)}>Suppr</Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })
                        }
                    </tbody>
                </table>
            </section>
        </>
    );
}

EventsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
