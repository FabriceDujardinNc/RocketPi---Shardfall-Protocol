import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';

export default function AdminBannersIndex() {
    return (
        <>
            <Head title="Admin · Bannières" />
            <header className="flex items-center justify-between mb-8">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Bannières</h1>
                <Link href="/admin/banners/create"><Button size="sm" variant="shard">Nouvelle bannière</Button></Link>
            </header>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">Aucune bannière.</div>
        </>
    );
}

AdminBannersIndex.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
