import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function Leaderboard() {
    return (
        <>
            <Head title="Classement" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Classement</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucune saison active — Phase 2.
            </div>
        </>
    );
}

Leaderboard.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
