import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';

const PILLARS = [
    {
        title: 'Hero-shooter web',
        body: 'Joue directement dans le navigateur — Unity 6 WebGL. Pas d\'install, pas de launcher : tu crées un compte, tu joues.',
    },
    {
        title: 'Mode infiltration',
        body: 'Trouve l\'imposteur dans une foule d\'opérateurs civils. Marche pour te cacher, cours pour te trahir.',
    },
    {
        title: 'Idées de la communauté',
        body: 'Propose des features, vote pour les meilleures. Le développement suit ce que les joueurs veulent.',
    },
    {
        title: 'Soutenu par les dons',
        body: 'Pas de pay-to-win, pas de gacha, pas de pub. Si le projet te plaît, tu peux soutenir via PayPal ou crypto.',
    },
];

export default function Landing() {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';

    const jsonLd = [
        {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'RocketPi',
            url: baseUrl,
            logo: `${baseUrl}/favicon.ico`,
        },
        {
            '@context': 'https://schema.org',
            '@type': 'VideoGame',
            name: 'RocketPi: Shardfall Protocol',
            description: 'Hero-shooter web Unity WebGL avec mode infiltration. Open dev, soutenu par la communauté.',
            genre: ['Hero Shooter', 'Multijoueur'],
            gamePlatform: ['Web Browser', 'WebGL'],
            applicationCategory: 'Game',
            operatingSystem: 'Web',
            inLanguage: 'fr',
            publisher: { '@type': 'Organization', name: 'RocketPi' },
            offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
        },
    ];

    return (
        <>
            <SEO
                title={null}
                description="RocketPi: Shardfall Protocol — hero-shooter web avec mode infiltration. Open dev soutenu par la communauté : propose des idées, vote, joue. Sans pay-to-win."
                keywords="hero shooter, jeu web, infiltration, Unity WebGL, open dev"
                type="website"
                jsonLd={jsonLd}
            />

            <main className="min-h-screen bg-bg-base text-text-high">
                {/* Hero */}
                <section className="relative px-4 sm:px-6 py-16 sm:py-24 lg:py-32 max-w-6xl mx-auto">
                    <p className="font-display text-xs sm:text-sm uppercase tracking-mega text-shard-400 mb-4">
                        Shardfall Protocol
                    </p>
                    <h1 className="font-display font-bold text-4xl sm:text-6xl lg:text-7xl tracking-tight uppercase text-text-high leading-[1.05]">
                        ROCKETPI<span className="text-shard-500">.</span>
                    </h1>
                    <p className="font-body text-lg sm:text-xl text-text-medium mt-6 max-w-2xl leading-relaxed">
                        Hero-shooter web avec mode infiltration. Trouve l'imposteur dans la foule —
                        ou sois-le. Aucun launcher, aucune install : ouvre une page, tu joues.
                    </p>
                    <p className="font-body text-base text-text-low mt-4 max-w-2xl leading-relaxed">
                        Projet open dev : les joueurs proposent des idées, votent, et le développement
                        suit la communauté. Pas de pay-to-win, pas de gacha. Si tu veux soutenir le
                        projet, tu peux faire un don.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Créer un compte
                        </Link>
                        <Link
                            href="/login"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-bg-elev2 text-text-high border border-border-default rounded-md hover:bg-bg-elev3 transition-all"
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/idees"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 text-text-medium border border-border-default rounded-md hover:text-text-high transition-all"
                        >
                            Voir les idées
                        </Link>
                    </div>
                </section>

                {/* Piliers */}
                <section className="px-4 sm:px-6 py-12 sm:py-16 max-w-6xl mx-auto">
                    <h2 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mb-8">
                        Le projet en 4 points
                    </h2>
                    <div className="grid sm:grid-cols-2 gap-4">
                        {PILLARS.map((p) => (
                            <article key={p.title} className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                                <h3 className="font-display font-semibold text-base uppercase tracking-wide text-shard-400">
                                    {p.title}
                                </h3>
                                <p className="font-body text-sm text-text-medium mt-2 leading-relaxed">
                                    {p.body}
                                </p>
                            </article>
                        ))}
                    </div>
                </section>

                {/* CTA bas de page */}
                <section className="px-4 sm:px-6 py-16 sm:py-20 max-w-3xl mx-auto text-center">
                    <h2 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide">
                        Prêt à tester ?
                    </h2>
                    <p className="font-body text-text-medium mt-3">
                        Création de compte gratuite, jeu directement dans le navigateur.
                    </p>
                    <div className="flex flex-wrap justify-center gap-3 mt-6">
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Créer un compte
                        </Link>
                        <Link
                            href="/dons"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 bg-bg-elev2 text-text-high border border-border-default rounded-md hover:bg-bg-elev3 transition-all"
                        >
                            Soutenir le projet
                        </Link>
                    </div>
                </section>

                <footer className="border-t border-border-default py-8 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol
                </footer>
            </main>
        </>
    );
}
