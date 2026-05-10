import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminOperatorCreate() {
    return (
        <>
            <Head title="Admin · Nouvel Opérateur" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Nouvel Opérateur</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-text-medium">
                Formulaire — à implémenter.
            </div>
        </>
    );
}

AdminOperatorCreate.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
