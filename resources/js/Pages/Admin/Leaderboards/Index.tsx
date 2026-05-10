import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminLeaderboardsIndex() {
    return (
        <>
            <Head title="Admin · Classements" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Classements</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucune saison — Phase 2.
            </div>
        </>
    );
}

AdminLeaderboardsIndex.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
