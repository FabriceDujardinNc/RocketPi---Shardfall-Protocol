import { Link, usePage } from '@inertiajs/react';
import SEO from '@/Components/SEO';
import Button from '@ui/Button';
import { useState } from 'react';

interface Props {
    paypalUrl: string;
    cryptoWalletAddress: string;
    cryptoNetwork: string;
    thankYouMessage: string;
}

export default function Dons({ paypalUrl, cryptoWalletAddress, cryptoNetwork, thankYouMessage }: Props) {
    const { props } = usePage<{ auth?: { user?: { id: number } } }>();
    const isAuth = !!props.auth?.user;
    const [copied, setCopied] = useState(false);

    const copyWallet = async () => {
        if (!cryptoWalletAddress) return;
        await navigator.clipboard.writeText(cryptoWalletAddress);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <>
            <SEO
                title="Soutenir le projet"
                description="Soutiens le développement de RocketPi: Shardfall Protocol — PayPal ou crypto. Chaque don finance directement l'hébergement et le dev."
            />

            <div className="min-h-screen bg-bg-base text-text-high flex flex-col">
                <header className="border-b border-border-default">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between">
                        <Link href="/" className="font-display font-bold text-lg uppercase tracking-wide">
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                        <nav className="flex items-center gap-3 font-display text-sm uppercase tracking-wide">
                            <Link href="/idees" className="text-text-medium hover:text-text-high">Idées</Link>
                            {isAuth ? (
                                <Link href="/play" className="text-shard-400 hover:text-shard-300">Jouer</Link>
                            ) : (
                                <>
                                    <Link href="/login" className="text-text-medium hover:text-text-high">Connexion</Link>
                                    <Link href="/register" className="text-shard-400 hover:text-shard-300">Rejoindre</Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1 mx-auto max-w-3xl w-full px-4 sm:px-6 py-12">
                    <header className="mb-10 text-center">
                        <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-2">Soutenir</p>
                        <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wide">
                            Dons
                        </h1>
                        <p className="font-body text-text-medium mt-4 max-w-xl mx-auto leading-relaxed">
                            {thankYouMessage}
                        </p>
                    </header>

                    <section className="grid gap-4">
                        <article className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                            <div className="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h2 className="font-display font-semibold text-lg uppercase tracking-wide">
                                    PayPal
                                </h2>
                                <span className="font-mono text-xs text-text-low">Don ponctuel ou récurrent</span>
                            </div>
                            {paypalUrl ? (
                                <a
                                    href={paypalUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-11 px-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                                >
                                    Donner via PayPal →
                                </a>
                            ) : (
                                <p className="font-mono text-sm text-text-low">
                                    Lien PayPal non configuré pour le moment.
                                </p>
                            )}
                        </article>

                        <article className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                            <div className="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h2 className="font-display font-semibold text-lg uppercase tracking-wide">
                                    Crypto
                                </h2>
                                {cryptoNetwork && (
                                    <span className="font-mono text-xs text-text-low uppercase">
                                        Réseau : {cryptoNetwork}
                                    </span>
                                )}
                            </div>
                            {cryptoWalletAddress ? (
                                <>
                                    <p className="font-display text-xs uppercase tracking-mega text-text-low mb-2">
                                        Adresse du portefeuille
                                    </p>
                                    <div className="rounded-md bg-bg-elev2 border border-border-default p-3 font-mono text-xs sm:text-sm text-text-high break-all">
                                        {cryptoWalletAddress}
                                    </div>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        <Button onClick={copyWallet} variant="secondary" size="sm">
                                            {copied ? 'Adresse copiée !' : 'Copier l\'adresse'}
                                        </Button>
                                    </div>
                                    <p className="font-mono text-[11px] text-text-low mt-3">
                                        Vérifie le réseau avant d'envoyer — les transferts entre chaînes
                                        incompatibles sont perdus.
                                    </p>
                                </>
                            ) : (
                                <p className="font-mono text-sm text-text-low">
                                    Adresse crypto non configurée pour le moment.
                                </p>
                            )}
                        </article>

                        <article className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6">
                            <h2 className="font-display font-semibold text-sm uppercase tracking-wide text-shard-400 mb-2">
                                À quoi servent les dons ?
                            </h2>
                            <ul className="space-y-1.5 font-body text-sm text-text-medium leading-relaxed">
                                <li>• Hébergement serveur (VPS, bande passante)</li>
                                <li>• Domaines, certificats SSL, services tiers</li>
                                <li>• Assets graphiques (modèles 3D, animations)</li>
                                <li>• Temps de développement</li>
                            </ul>
                            <p className="font-mono text-[11px] text-text-low mt-4">
                                Pas de pay-to-win : aucun donateur ne reçoit d'avantage en jeu.
                                Le projet reste 100 % gratuit et équitable.
                            </p>
                        </article>
                    </section>
                </main>

                <footer className="border-t border-border-default py-6 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol
                </footer>
            </div>
        </>
    );
}
