import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface Props {
    user: { id: number; name: string; display_name?: string; account_level: number; account_xp: number };
}

export default function Dashboard({ user }: Props) {
    return (
        <>
            <Head title="Tableau de bord" />
            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Cellule active</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">
                    Bienvenue, {user.display_name ?? user.name}
                </h1>
                <p className="font-mono text-sm text-text-medium mt-1">
                    Niveau {user.account_level} · {user.account_xp} XP
                </p>
            </header>

            <section className="grid md:grid-cols-3 gap-4">
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Tirages disponibles</p>
                    <p className="font-display text-3xl font-bold text-shard-400 mt-2">—</p>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Missions du jour</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">—</p>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Battle Pass</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">—</p>
                </div>
            </section>
        </>
    );
}

Dashboard.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
