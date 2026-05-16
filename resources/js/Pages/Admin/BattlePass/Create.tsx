import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SeasonForm from './SeasonForm';
import { ArrowLeft } from 'lucide-react';

export default function BattlePassCreate() {
    const inOneWeek = new Date();
    inOneWeek.setDate(inOneWeek.getDate() + 7);
    const inNineWeeks = new Date();
    inNineWeeks.setDate(inNineWeeks.getDate() + 7 + 56);

    return (
        <>
            <Head title="Admin · Nouvelle saison Battle Pass" />
            <header className="mb-6">
                <Link href="/admin/battle-passes" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvelle saison Battle Pass</h1>
                <p className="font-mono text-xs text-text-low mt-1">
                    50 paliers par défaut seront créés avec des récompenses placeholder, à affiner ensuite.
                </p>
            </header>
            <SeasonForm
                initial={{
                    name: '', season_number: 1,
                    total_tiers: 50,
                    premium_price_shards: 1000,
                    premium_price_tickets: 5,
                    starts_at: inOneWeek.toISOString().slice(0, 16),
                    ends_at:   inNineWeeks.toISOString().slice(0, 16),
                    is_active: false,
                }}
                submitLabel="Créer la saison"
                action={{ method: 'post', url: '/admin/battle-passes' }}
            />
        </>
    );
}

BattlePassCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
