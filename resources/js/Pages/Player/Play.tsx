import { Head, router } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import UnityCanvas from '@game/UnityCanvas';

interface UnityConfig {
    api_base_url: string;
    api_token: string;
    user_id: number;
    locale: string;
}

interface Props {
    unityConfig: UnityConfig | null;
}

// ── Contrôles du jeu ────────────────────────────────────────────────────────
// ⚠️ MAINTENIR À JOUR : chaque fois qu'une nouvelle touche/commande est ajoutée
// côté Unity (PlayerController, etc.), l'ajouter ici.
const CONTROLS: { key: string; action: string }[] = [
    { key: 'W A S D',          action: 'Se déplacer' },
    { key: 'Maj. gauche',      action: 'Courir (en avançant)' },
    { key: 'Espace',           action: 'Sauter' },
    { key: 'Souris',           action: 'Tourner la caméra / viser' },
    { key: 'Molette',          action: 'Zoom caméra' },
    { key: 'Clic gauche',      action: 'Tirer / Frapper' },
    { key: 'Clic droit',       action: 'Tir secondaire' },
    { key: 'F',                action: 'Capacité de classe' },
    { key: 'R',                action: 'Ultime de l\'opérateur' },
    { key: 'V',                action: 'Vue 1ʳᵉ / 3ᵉ personne' },
    { key: 'Échap',            action: 'Libérer la souris' },
    { key: 'Marcher dessus',   action: 'Ramasser un bonus' },
];

export default function Play({ unityConfig }: Props) {
    return (
        <>
            <Head title="Jouer" />

            <header className="mb-6">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Champ de bataille</p>
                <h1 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mt-1">Jouer</h1>
            </header>

            <section className="mb-6">
                {unityConfig ? (
                    <UnityCanvas
                        apiBaseUrl={unityConfig.api_base_url}
                        apiToken={unityConfig.api_token}
                        userId={unityConfig.user_id}
                        locale={unityConfig.locale}
                        photonAppId={null}
                        onMatchFinished={() => router.reload()}
                        onRequestReload={() => router.reload()}
                    />
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                        <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-2">Unity 6 WebGL</p>
                        <p className="font-body text-sm">Configuration Unity indisponible.</p>
                    </div>
                )}
            </section>

            <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <h2 className="font-display font-semibold text-sm uppercase tracking-mega text-shard-400 mb-3">
                    Contrôles
                </h2>
                <ul className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-2">
                    {CONTROLS.map((c) => (
                        <li key={c.key} className="flex items-center justify-between gap-2 text-sm">
                            <span className="font-body text-text-medium">{c.action}</span>
                            <kbd className="font-mono text-[11px] uppercase tracking-wide text-text-high bg-bg-elev2 border border-border-default rounded px-2 py-0.5 whitespace-nowrap">
                                {c.key}
                            </kbd>
                        </li>
                    ))}
                </ul>
            </section>
        </>
    );
}

Play.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
