import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function BattlePass() {
    return (
        <>
            <Head title="Battle Pass" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Battle Pass</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucune saison active — Phase 3.
            </div>
        </>
    );
}

BattlePass.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
