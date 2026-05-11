import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { ArrowLeft, Pencil, Archive, RotateCcw } from 'lucide-react';

interface Reward { type: string; amount: number }

interface Mission {
    id: number;
    title: string;
    description: string | null;
    type: string;
    objective_type: string;
    objective_target: number;
    rewards: Reward[];
    xp_reward: number;
    is_active: boolean;
    available_from: string | null;
    available_until: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function MissionShow({ mission }: { mission: Mission }) {
    const { props } = usePage<PageProps>();
    const archive = () => { if (confirm(`Archiver « ${mission.title} » ?`)) router.delete(`/admin/missions/${mission.id}`); };
    const restore = () => router.post(`/admin/missions/${mission.id}/restore`);

    return (
        <>
            <Head title={`Admin · ${mission.title}`} />
            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <Link href="/admin/missions" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                        <ArrowLeft size={12} /> Retour à la liste
                    </Link>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{mission.title}</h1>
                    <p className="font-mono text-xs text-text-low mt-1">ID #{mission.id} · {mission.type}</p>
                </div>
                <div className="flex gap-2">
                    {!mission.deleted_at ? (
                        <>
                            <Link href={`/admin/missions/${mission.id}/edit`}><Button variant="secondary" icon={<Pencil size={14} />}>Éditer</Button></Link>
                            <Button variant="danger" icon={<Archive size={14} />} onClick={archive}>Archiver</Button>
                        </>
                    ) : (
                        <Button variant="shard" icon={<RotateCcw size={14} />} onClick={restore}>Restaurer</Button>
                    )}
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {mission.deleted_at && <div className="mb-4"><Alert variant="warning">Mission archivée le {new Date(mission.deleted_at).toLocaleDateString('fr-FR')}.</Alert></div>}

            <div className="grid lg:grid-cols-2 gap-6">
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Description</h2>
                    {mission.description
                        ? <p className="text-text-medium text-sm whitespace-pre-wrap">{mission.description}</p>
                        : <p className="text-text-low text-xs font-mono italic">Pas de description.</p>}
                </section>

                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Objectif</h2>
                    <Row k="Type d'objectif" v={mission.objective_type} />
                    <Row k="Valeur cible"    v={String(mission.objective_target)} />
                    <Row k="État"            v={mission.is_active ? '✓ Active' : '✗ Inactive'} />
                    <Row k="Période"         v={`${mission.available_from ? new Date(mission.available_from).toLocaleDateString('fr-FR') : '—'} → ${mission.available_until ? new Date(mission.available_until).toLocaleDateString('fr-FR') : '∞'}`} />
                </section>

                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5 lg:col-span-2">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Récompenses</h2>
                    <div className="flex flex-wrap gap-3">
                        {mission.rewards?.map((r, i) => (
                            <span key={i} className="font-mono text-sm px-3 py-2 rounded bg-bg-elev2 border border-border-default">
                                <span className="text-shard-400">+{r.amount}</span> <span className="text-text-medium">{r.type}</span>
                            </span>
                        ))}
                        {mission.xp_reward > 0 && (
                            <span className="font-mono text-sm px-3 py-2 rounded bg-shard-500/10 border border-shard-500/40">
                                <span className="text-shard-400">+{mission.xp_reward}</span> <span className="text-text-medium">XP de compte</span>
                            </span>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

function Row({ k, v }: { k: string; v: string }) {
    return (
        <div className="flex justify-between font-mono text-sm py-1 border-b border-border-default/40 last:border-0">
            <span className="text-text-low uppercase tracking-wide text-xs">{k}</span>
            <span className="text-text-high">{v}</span>
        </div>
    );
}

MissionShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
