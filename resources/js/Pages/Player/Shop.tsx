import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function Shop() {
    return (
        <>
            <Head title="Boutique" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Boutique</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucun pack disponible — Phase 3.
            </div>
        </>
    );
}

Shop.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
