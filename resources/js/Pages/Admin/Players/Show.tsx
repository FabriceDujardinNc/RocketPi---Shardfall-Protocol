import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        is_banned: boolean;
        ban_reason: string | null;
        banned_at: string | null;
        created_at: string;
        last_active_at: string | null;
    };
}

export default function AdminPlayerShow({ user }: Props) {
    return (
        <>
            <Head title={`Admin · ${user.name}`} />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">{user.name}</h1>

            <section className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                <dl className="grid grid-cols-2 gap-4 font-mono text-sm">
                    <dt className="text-text-low">ID</dt><dd className="text-text-high">{user.id}</dd>
                    <dt className="text-text-low">Email</dt><dd className="text-text-high">{user.email}</dd>
                    <dt className="text-text-low">Rôle</dt><dd className="text-text-high">{user.role}</dd>
                    <dt className="text-text-low">Inscrit le</dt><dd className="text-text-high">{user.created_at}</dd>
                    <dt className="text-text-low">Dernière activité</dt><dd className="text-text-high">{user.last_active_at ?? '—'}</dd>
                    <dt className="text-text-low">Statut</dt>
                    <dd>
                        {user.is_banned
                            ? <span className="text-danger">Banni — {user.ban_reason}</span>
                            : <span className="text-success">Actif</span>}
                    </dd>
                </dl>
            </section>
        </>
    );
}

AdminPlayerShow.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
