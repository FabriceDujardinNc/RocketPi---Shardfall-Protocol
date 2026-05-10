import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminGachaLogs() {
    return (
        <>
            <Head title="Admin · Logs Gacha" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2">Logs Gacha</h1>
            <p className="font-mono text-xs text-text-low mb-8">Audit légal — immutable</p>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucun log — Phase 2.
            </div>
        </>
    );
}

AdminGachaLogs.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
