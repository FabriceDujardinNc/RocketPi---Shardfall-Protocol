import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function Gacha() {
    return (
        <>
            <Head title="Recrutement" />
            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Signal Shard</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Recrutement</h1>
            </header>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucune bannière active — Phase 2.
            </div>
        </>
    );
}

Gacha.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
