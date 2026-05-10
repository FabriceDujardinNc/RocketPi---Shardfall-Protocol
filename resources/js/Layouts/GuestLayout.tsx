import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-bg-base text-text-high flex flex-col">
            <header className="border-b border-border-default">
                <div className="mx-auto max-w-7xl px-6 h-16 flex items-center justify-between">
                    <Link
                        href="/"
                        className="font-display font-bold text-lg uppercase tracking-wide text-text-high"
                    >
                        ROCKETPI<span className="text-shard-500">.</span>
                    </Link>
                    <nav className="flex items-center gap-4 font-display text-sm uppercase tracking-wide">
                        <Link href="/login" className="text-text-medium hover:text-text-high">
                            Connexion
                        </Link>
                        <Link href="/register" className="text-shard-400 hover:text-shard-300">
                            Rejoindre
                        </Link>
                    </nav>
                </div>
            </header>
            <main className="flex-1 flex items-center justify-center px-6 py-12">
                <div className="w-full max-w-md">{children}</div>
            </main>
            <footer className="border-t border-border-default py-6 text-center text-xs text-text-low font-mono">
                Shardfall Protocol — 2087
            </footer>
        </div>
    );
}
