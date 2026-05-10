import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminOperatorShow({ id }: { id: number }) {
    return (
        <>
            <Head title={`Admin · Opérateur ${id}`} />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Opérateur #{id}</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-text-medium">Détail — à implémenter.</div>
        </>
    );
}

AdminOperatorShow.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
