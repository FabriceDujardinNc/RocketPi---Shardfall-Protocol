import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';

export default function AdminOperatorsIndex() {
    return (
        <>
            <Head title="Admin · Opérateurs" />
            <header className="flex items-center justify-between mb-8">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Opérateurs</h1>
                <Link href="/admin/operators/create"><Button size="sm" variant="shard">Nouvel Opérateur</Button></Link>
            </header>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucun Opérateur en BDD — lance le seeder.
            </div>
        </>
    );
}

AdminOperatorsIndex.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
