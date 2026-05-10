import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminReferrals() {
    return (
        <>
            <Head title="Admin · Parrainages" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2">Parrainages</h1>
            <p className="font-mono text-xs text-text-low mb-8">Détection de patterns suspects</p>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucun parrainage — Phase 1.
            </div>
        </>
    );
}

AdminReferrals.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
