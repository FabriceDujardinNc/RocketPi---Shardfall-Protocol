import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';

const NAV = [
    { href: '/dashboard',    label: 'Dashboard' },
    { href: '/factions',     label: 'Factions' },
    { href: '/gacha',        label: 'Gacha' },
    { href: '/missions',     label: 'Missions' },
    { href: '/battlepass',   label: 'Battle Pass' },
    { href: '/achievements', label: 'Honneurs' },
    { href: '/leaderboard',  label: 'Classement' },
    { href: '/referral',     label: 'Parrainage' },
    { href: '/shop',         label: 'Shop' },
    { href: '/cosmetics',    label: 'Vestiaire' },
    { href: '/play',         label: 'Jouer' },
];

const FACTION_COLOR: Record<string, string> = {
    ORBIT: 'text-orbit',
    FERRO: 'text-ferro',
    VEIL:  'text-veil',
};

export default function PlayerLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{ auth?: { user?: { display_name?: string; name?: string; faction?: string | null } } }>();
    const user = props.auth?.user;
    const factionColor = user?.faction ? FACTION_COLOR[user.faction] ?? '' : '';
    const [open, setOpen] = useState(false);

    // Ferme automatiquement le drawer mobile à chaque navigation pour éviter
    // qu'il reste ouvert en arrière-plan après un click.
    useEffect(() => { setOpen(false); }, [url]);

    // Bloque le scroll body quand le drawer est ouvert (UX mobile standard).
    useEffect(() => {
        if (!open) return;
        document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = ''; };
    }, [open]);

    const navClassName = (href: string) =>
        'px-3 py-2 rounded-md transition-all ' +
        (url.startsWith(href)
            ? 'text-shard-400 bg-bg-elev2'
            : 'text-text-medium hover:text-text-high hover:bg-bg-elev1');

    return (
        <div className="min-h-screen bg-bg-base text-text-high">
            <header className="sticky top-0 z-overlay border-b border-border-default bg-bg-elev1/80 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        {/* Hamburger — visible <md uniquement */}
                        <button
                            type="button"
                            onClick={() => setOpen(true)}
                            className="md:hidden inline-flex items-center justify-center size-10 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2"
                            aria-label="Ouvrir le menu"
                        >
                            <Menu size={20} />
                        </button>

                        <Link
                            href="/dashboard"
                            className="font-display font-bold text-lg uppercase tracking-wide"
                        >
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                    </div>

                    {/* Nav desktop */}
                    <nav className="hidden md:flex items-center gap-1 font-display text-sm uppercase tracking-wide overflow-x-auto">
                        {NAV.map(item => (
                            <Link key={item.href} href={item.href} className={navClassName(item.href)}>
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2 sm:gap-3 min-w-0">
                        <Link
                            href="/profile"
                            className="text-sm font-display tracking-wide text-text-medium hover:text-text-high flex items-center gap-2 min-w-0"
                        >
                            <span className="truncate max-w-[120px] sm:max-w-none">
                                {user?.display_name ?? user?.name ?? 'Joueur'}
                            </span>
                            {user?.faction && (
                                <span className={`hidden sm:inline font-display text-[10px] uppercase tracking-mega ${factionColor}`}>
                                    {user.faction}
                                </span>
                            )}
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="hidden sm:inline text-xs font-display uppercase tracking-wide text-text-low hover:text-danger"
                        >
                            Déconnexion
                        </Link>
                    </div>
                </div>
            </header>

            {/* Drawer mobile */}
            {open && (
                <div className="md:hidden fixed inset-0 z-modal" role="dialog" aria-modal="true">
                    <div
                        className="absolute inset-0 bg-bg-base/80 backdrop-blur-sm"
                        onClick={() => setOpen(false)}
                        aria-hidden="true"
                    />
                    <aside className="absolute top-0 left-0 h-full w-72 max-w-[85vw] bg-bg-elev1 border-r border-border-default shadow-el3 p-4 overflow-y-auto">
                        <div className="flex items-center justify-between mb-6">
                            <Link href="/dashboard" className="font-display font-bold text-lg uppercase tracking-wide">
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

                        {user && (
                            <div className="mb-4 pb-4 border-b border-border-default">
                                <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Compte</p>
                                <p className="font-display text-sm uppercase tracking-wide text-text-high mt-1 flex items-center gap-2">
                                    {user.display_name ?? user.name}
                                    {user.faction && (
                                        <span className={`font-display text-[10px] uppercase tracking-mega ${factionColor}`}>
                                            {user.faction}
                                        </span>
                                    )}
                                </p>
                            </div>
                        )}

                        <nav className="flex flex-col gap-1 font-display text-sm uppercase tracking-wide">
                            {NAV.map(item => (
                                <Link key={item.href} href={item.href} className={navClassName(item.href)}>
                                    {item.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="mt-6 pt-4 border-t border-border-default flex flex-col gap-2">
                            <Link href="/profile" className="font-display text-sm uppercase tracking-wide text-text-medium hover:text-text-high">
                                Mon profil
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="text-left font-display text-sm uppercase tracking-wide text-danger hover:text-danger/80"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </aside>
                </div>
            )}

            <main className="mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8">{children}</main>
        </div>
    );
}
