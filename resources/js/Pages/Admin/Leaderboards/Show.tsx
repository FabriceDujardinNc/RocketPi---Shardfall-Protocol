import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminLeaderboardShow({ seasonId }: { seasonId: number }) {
    return (
        <>
            <Head title={`Admin · Saison ${seasonId}`} />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Saison #{seasonId}</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-text-medium">Détail — à implémenter.</div>
        </>
    );
}

AdminLeaderboardShow.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
