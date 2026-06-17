import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface Props {
    stats: { totalUsers: number; bannedUsers: number; last24h: number };
}

export default function AdminDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Admin · Tableau de bord" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Tableau de bord</h1>
            <section className="grid md:grid-cols-3 gap-4">
                <Stat label="Joueurs total" value={stats.totalUsers} />
                <Stat label="Bannis" value={stats.bannedUsers} accent="danger" />
                <Stat label="Inscriptions 24h" value={stats.last24h} accent="shard" />
            </section>
        </>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'shard' | 'danger' }) {
    const colorClass =
        accent === 'shard' ? 'text-shard-400' :
        accent === 'danger' ? 'text-danger' :
        'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-3xl font-bold mt-2 ${colorClass}`}>{value}</p>
        </div>
    );
}

AdminDashboard.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
