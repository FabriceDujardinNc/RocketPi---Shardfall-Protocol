import { Head, Link } from '@inertiajs/react';

export default function Landing() {
    return (
        <>
            <Head title="Accueil" />
            <main className="min-h-screen bg-bg-base flex items-center justify-center">
                <div className="text-center">
                    <h1 className="font-display font-bold text-display tracking-tight uppercase text-text-high">
                        ROCKETPI
                    </h1>
                    <p className="font-display text-xl text-shard-400 tracking-mega uppercase mt-2">
                        Shardfall Protocol
                    </p>
                    <p className="font-body text-base text-text-medium mt-6 max-w-md mx-auto leading-relaxed">
                        En 2087, la station RocketPi s'est désintégrée. Les Shards ont tout changé.
                    </p>
                    <div className="mt-8 flex gap-4 justify-center">
                        <Link
                            href="/register"
                            className="inline-flex items-center gap-2 font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Rejoindre le protocole
                        </Link>
                        <Link
                            href="/login"
                            className="inline-flex items-center gap-2 font-display font-semibold text-sm tracking-wide uppercase h-12 px-6 bg-bg-elev2 text-text-high border border-border-default rounded-md hover:bg-bg-elev3 transition-all"
                        >
                            Connexion
                        </Link>
                    </div>
                </div>
            </main>
        </>
    );
}
