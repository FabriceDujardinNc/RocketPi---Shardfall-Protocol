import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface Props {
    sessionToken: string | null;
    photonAppId: string | null;
}

export default function Play({ sessionToken, photonAppId }: Props) {
    return (
        <>
            <Head title="Jouer" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Champ de bataille</h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Build Unity 6 WebGL — Phase 4.
                {!sessionToken && <p className="text-text-low text-xs mt-2 font-mono">Pas de token de session.</p>}
                {!photonAppId && <p className="text-text-low text-xs mt-1 font-mono">Photon non configuré.</p>}
            </div>
        </>
    );
}

Play.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
