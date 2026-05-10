import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

export default function Collection() {
    return (
        <>
            <Head title="Collection" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-2">Collection</h1>
            <p className="font-mono text-sm text-text-medium mb-8">Tes Opérateurs recrutés</p>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Aucun Opérateur — fais ton premier Recrutement par Signal Shard.
            </div>
        </>
    );
}

Collection.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
