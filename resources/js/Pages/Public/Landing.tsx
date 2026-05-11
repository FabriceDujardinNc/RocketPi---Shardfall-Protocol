import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';

const FACTIONS = [
    {
        slug: 'ORBIT', accent: 'text-orbit', border: 'border-orbit/40',
        tagline: 'Ingénieurs orbitaux, précision et soutien',
        lore: "Les survivants de la station mère. Techniciens, médecins, navigateurs. Observent, calculent, frappent juste.",
    },
    {
        slug: 'FERRO', accent: 'text-ferro', border: 'border-ferro/40',
        tagline: 'Mineurs reconvertis en milice industrielle',
        lore: "Métal et impact direct. Tanks, démolisseurs, assauts polyvalents. La force vient du terrain.",
    },
    {
        slug: 'VEIL', accent: 'text-veil', border: 'border-veil/40',
        tagline: 'Réseau clandestin, infiltrateurs et hackeurs',
        lore: "Officiellement, VEIL n'existe pas. Officieusement, c'est la faction la mieux informée du conflit.",
    },
];

const PILLARS = [
    {
        title: 'Recrutement Gacha',
        body: 'Système de tirage premium avec pity garanti à 80, soft pity à partir de 60, et fragments doublons reconvertibles en opérateurs ciblés.',
    },
    {
        title: 'Affinité progressive',
        body: 'Chaque opérateur a 10 niveaux d\'affinité. Débloque skins, voicelines et lore narratif au fil de tes tirages et de tes parties.',
    },
    {
        title: 'Classements compétitifs',
        body: 'Saisons hebdo, mensuelles et annuelles. Rewards aux paliers (top 1, top 10, top 100, top 1%). Hall of Fame pour les meilleurs.',
    },
    {
        title: 'Battle Pass + Missions',
        body: '50 paliers par saison, missions journalières et hebdomadaires, achievements de collection et de progression.',
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
            sameAs: [],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: 'RocketPi: Shardfall Protocol',
            url: baseUrl,
            potentialAction: {
                '@type': 'SearchAction',
                target: `${baseUrl}/lore?q={search_term_string}`,
                'query-input': 'required name=search_term_string',
            },
        },
        {
            '@context': 'https://schema.org',
            '@type': 'VideoGame',
            name: 'RocketPi: Shardfall Protocol',
            description: 'Hero-shooter compétitif avec recrutement gacha, trois factions, classements saisonniers.',
            genre: ['Hero Shooter', 'Gacha', 'Multijoueur compétitif'],
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
                description="RocketPi: Shardfall Protocol — hero-shooter avec recrutement gacha. Trois factions (ORBIT, FERRO, VEIL), classements compétitifs, battle pass, missions. En 2087, la station RocketPi s'est désintégrée. Les Shards ont tout changé."
                keywords="hero shooter, gacha, RocketPi, Shardfall, factions, ORBIT, FERRO, VEIL, jeu web, multijoueur"
                type="website"
                jsonLd={jsonLd}
            />

            <main className="min-h-screen bg-bg-base text-text-high">
                {/* Hero */}
                <section className="relative px-4 sm:px-6 py-16 sm:py-24 lg:py-32 max-w-6xl mx-auto">
                    <p className="font-display text-xs sm:text-sm uppercase tracking-mega text-shard-400 mb-4">
                        Shardfall Protocol · 2087
                    </p>
                    <h1 className="font-display font-bold text-4xl sm:text-6xl lg:text-7xl tracking-tight uppercase text-text-high leading-[1.05]">
                        ROCKETPI<span className="text-shard-500">.</span>
                    </h1>
                    <p className="font-body text-lg sm:text-xl text-text-medium mt-6 max-w-2xl leading-relaxed">
                        Hero-shooter avec recrutement gacha et classements compétitifs.
                        Choisis ta faction, recrute tes Opérateurs, monte ton affinité,
                        gagne ta place dans le Hall of Fame.
                    </p>
                    <p className="font-body text-base text-text-low mt-4 max-w-2xl leading-relaxed">
                        En 2087, la station RocketPi s'est désintégrée. Les Shards — fragments
                        cristallins du cœur de la station — ont conféré à certains humains
                        des capacités hors normes. Trois factions s'affrontent pour les contrôler.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Rejoindre le protocole
                        </Link>
                        <Link
                            href="/login"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-bg-elev2 text-text-high border border-border-default rounded-md hover:bg-bg-elev3 transition-all"
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/lore"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 text-text-medium border border-border-default rounded-md hover:text-text-high transition-all"
                        >
                            Découvrir l'univers
                        </Link>
                    </div>
                </section>

                {/* Factions */}
                <section className="px-4 sm:px-6 py-12 sm:py-16 max-w-6xl mx-auto">
                    <h2 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mb-3">
                        Trois factions. Une seule allégeance.
                    </h2>
                    <p className="font-body text-text-medium max-w-2xl mb-8">
                        Ton choix de faction à l'inscription est <strong className="text-text-high">définitif</strong>.
                        Chaque faction a ses opérateurs, sa doctrine et ses récompenses saisonnières.
                    </p>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {FACTIONS.map((f) => (
                            <article
                                key={f.slug}
                                className={`rounded-lg bg-bg-elev1 border-2 p-5 ${f.border}`}
                            >
                                <h3 className={`font-display font-bold text-lg uppercase tracking-mega ${f.accent}`}>
                                    {f.slug}
                                </h3>
                                <p className="font-mono text-xs text-text-medium mt-1">{f.tagline}</p>
                                <p className="font-body text-sm text-text-medium mt-3 leading-relaxed">
                                    {f.lore}
                                </p>
                                <Link
                                    href={`/lore/factions/${f.slug}`}
                                    className={`inline-block mt-3 font-display text-xs uppercase tracking-wide ${f.accent} hover:underline`}
                                >
                                    Lore complet →
                                </Link>
                            </article>
                        ))}
                    </div>
                </section>

                {/* Piliers gameplay */}
                <section className="px-4 sm:px-6 py-12 sm:py-16 max-w-6xl mx-auto">
                    <h2 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mb-8">
                        Le Protocole en 4 mécaniques
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
                        Prêt à choisir ton camp ?
                    </h2>
                    <p className="font-body text-text-medium mt-3">
                        Création de compte gratuite. Pas de pay-to-win — F2P intégral, monétisation 100% cosmétique.
                    </p>
                    <Link
                        href="/register"
                        className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 mt-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                    >
                        Rejoindre maintenant
                    </Link>
                </section>

                <footer className="border-t border-border-default py-8 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol · 2087 · Tous droits réservés
                </footer>
            </main>
        </>
    );
}
