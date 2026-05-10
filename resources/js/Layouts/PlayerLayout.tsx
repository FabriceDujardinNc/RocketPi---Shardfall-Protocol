import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const NAV = [
    { href: '/dashboard',    label: 'Dashboard' },
    { href: '/collection',   label: 'Collection' },
    { href: '/gacha',        label: 'Gacha' },
    { href: '/missions',     label: 'Missions' },
    { href: '/battlepass',   label: 'Battle Pass' },
    { href: '/achievements', label: 'Honneurs' },
    { href: '/leaderboard',  label: 'Classement' },
    { href: '/referral',     label: 'Parrainage' },
    { href: '/shop',         label: 'Shop' },
    { href: '/play',         label: 'Jouer' },
];

export default function PlayerLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{ auth?: { user?: { display_name?: string; name?: string } } }>();
    const user = props.auth?.user;

    return (
        <div className="min-h-screen bg-bg-base text-text-high">
            <header className="sticky top-0 z-overlay border-b border-border-default bg-bg-elev1/80 backdrop-blur">
                <div className="mx-auto max-w-7xl px-6 h-16 flex items-center justify-between">
                    <Link
                        href="/dashboard"
                        className="font-display font-bold text-lg uppercase tracking-wide"
                    >
                        ROCKETPI<span className="text-shard-500">.</span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-1 font-display text-sm uppercase tracking-wide">
                        {NAV.map(item => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={
                                    'px-3 py-2 rounded-md transition-all ' +
                                    (url.startsWith(item.href)
                                        ? 'text-shard-400 bg-bg-elev2'
                                        : 'text-text-medium hover:text-text-high hover:bg-bg-elev1')
                                }
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-3">
                        <Link
                            href="/profile"
                            className="text-sm font-display tracking-wide text-text-medium hover:text-text-high"
                        >
                            {user?.display_name ?? user?.name ?? 'Joueur'}
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-xs font-display uppercase tracking-wide text-text-low hover:text-danger"
                        >
                            Déconnexion
                        </Link>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-6 py-8">{children}</main>
        </div>
    );
}
