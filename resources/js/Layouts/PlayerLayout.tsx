import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import ThemeToggle from '@ui/ThemeToggle';

interface NavLeaf { href: string; label: string }

// Site simplifié : navigation minimaliste. « Profil » n'apparaît que pour les
// utilisateurs connectés (la page exige une auth) ; le reste est public.
const NAV_PUBLIC: NavLeaf[] = [
    { href: '/play',  label: 'Jouer' },
    { href: '/idees', label: 'Idées' },
    { href: '/dons',  label: 'Dons'  },
];
const NAV_PROFILE: NavLeaf = { href: '/profile', label: 'Profil' };

export default function PlayerLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{ auth?: { user?: { display_name?: string; name?: string } } }>();
    const user = props.auth?.user;
    const NAV = user ? [...NAV_PUBLIC, NAV_PROFILE] : NAV_PUBLIC;
    const [open, setOpen] = useState(false);

    useEffect(() => { setOpen(false); }, [url]);
    useEffect(() => {
        if (!open) return;
        document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = ''; };
    }, [open]);

    const leafClass = (href: string) =>
        'px-3 py-2 rounded-md transition-all font-display text-sm uppercase tracking-wide ' +
        (url.startsWith(href)
            ? 'text-shard-400 bg-bg-elev2'
            : 'text-text-medium hover:text-text-high hover:bg-bg-elev1');

    return (
        <div className="min-h-screen bg-bg-base text-text-high">
            <header className="sticky top-0 z-overlay border-b border-border-default bg-bg-elev1/80 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setOpen(true)}
                            className="md:hidden inline-flex items-center justify-center size-10 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2"
                            aria-label="Ouvrir le menu"
                        >
                            <Menu size={20} />
                        </button>

                        <Link
                            href="/play"
                            className="font-display font-bold text-lg uppercase tracking-wide"
                        >
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                    </div>

                    <nav className="hidden md:flex items-center gap-1">
                        {NAV.map(entry => (
                            <Link key={entry.href} href={entry.href} className={leafClass(entry.href)}>
                                {entry.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2 sm:gap-3 min-w-0">
                        <ThemeToggle />
                        {user ? (
                            <>
                                <Link
                                    href="/profile"
                                    className="text-sm font-display tracking-wide text-text-medium hover:text-text-high flex items-center gap-2 min-w-0"
                                >
                                    <span className="truncate max-w-[120px] sm:max-w-none">
                                        {user.display_name ?? user.name ?? 'Joueur'}
                                    </span>
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="hidden sm:inline text-xs font-display uppercase tracking-wide text-text-low hover:text-danger"
                                >
                                    Déconnexion
                                </Link>
                            </>
                        ) : (
                            <Link
                                href="/login"
                                className="text-sm font-display uppercase tracking-wide text-shard-400 hover:text-shard-300"
                            >
                                Connexion
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            {open && (
                <div className="md:hidden fixed inset-0 z-modal" role="dialog" aria-modal="true">
                    <div
                        className="absolute inset-0 bg-bg-base/80 backdrop-blur-sm"
                        onClick={() => setOpen(false)}
                        aria-hidden="true"
                    />
                    <aside className="absolute top-0 left-0 h-full w-72 max-w-[85vw] bg-bg-elev1 border-r border-border-default shadow-el3 p-4 overflow-y-auto">
                        <div className="flex items-center justify-between mb-6">
                            <Link href="/play" className="font-display font-bold text-lg uppercase tracking-wide">
                                ROCKETPI<span className="text-shard-500">.</span>
                            </Link>
                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className="inline-flex items-center justify-center size-9 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2"
                                aria-label="Fermer le menu"
                            >
                                <X size={18} />
                            </button>
                        </div>

                        <nav className="flex flex-col gap-1">
                            {NAV.map(entry => (
                                <Link key={entry.href} href={entry.href} className={leafClass(entry.href)}>
                                    {entry.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="mt-6 pt-4 border-t border-border-default">
                            {user ? (
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="text-left font-display text-sm uppercase tracking-wide text-danger hover:text-danger/80"
                                >
                                    Déconnexion
                                </Link>
                            ) : (
                                <Link
                                    href="/login"
                                    className="text-left font-display text-sm uppercase tracking-wide text-shard-400 hover:text-shard-300"
                                >
                                    Connexion
                                </Link>
                            )}
                        </div>
                    </aside>
                </div>
            )}

            <main className="mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8">{children}</main>
        </div>
    );
}
