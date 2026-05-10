import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function Missions() {
    return (
        <>
            <Head title="Missions" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Missions</h1>
            <section className="grid md:grid-cols-2 gap-6">
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Journalières</h2>
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-text-medium">
                        Aucune mission — Phase 2.
                    </div>
                </div>
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Hebdomadaires</h2>
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-text-medium">
                        Aucune mission — Phase 2.
                    </div>
                </div>
            </section>
        </>
    );
}

Missions.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
