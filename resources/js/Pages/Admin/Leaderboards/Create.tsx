import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SeasonForm from './SeasonForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    enums: { types: string[]; factions: string[] };
}

export default function LeaderboardCreate({ enums }: Props) {
    const inOneWeek = new Date();
    inOneWeek.setDate(inOneWeek.getDate() + 7);

    return (
        <>
            <Head title="Admin · Nouvelle saison classement" />
            <header className="mb-6">
                <Link href="/admin/leaderboards" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour aux saisons
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvelle saison de classement</h1>
            </header>
            <SeasonForm
                initial={{
                    name: '', type: '', faction: null, season_number: 1,
                    starts_at: new Date().toISOString().slice(0, 16),
                    ends_at:   inOneWeek.toISOString().slice(0, 16),
                    is_active: false,
                }}
                enums={enums}
                submitLabel="Créer la saison"
                action={{ method: 'post', url: '/admin/leaderboards' }}
            />
        </>
    );
}

LeaderboardCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
