import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const SECTIONS = [
    { href: '/admin',              label: 'Tableau de bord' },
    { href: '/admin/operators',    label: 'Opérateurs' },
    { href: '/admin/factions',     label: 'Factions' },
    { href: '/admin/banners',      label: 'Bannières' },
    { href: '/admin/players',      label: 'Joueurs' },
    { href: '/admin/gacha-logs',   label: 'Logs Gacha' },
    { href: '/admin/referrals',    label: 'Parrainages' },
    { href: '/admin/leaderboards', label: 'Classements' },
    { href: '/admin/missions',             label: 'Missions' },
    { href: '/admin/battle-passes',        label: 'Battle Pass' },
    { href: '/admin/achievements',         label: 'Achievements' },
    { href: '/admin/events',               label: 'Événements' },
    { href: '/admin/cosmetics',            label: 'Cosmétiques' },
    { href: '/admin/daily-login-rewards',  label: 'Daily login' },
    { href: '/admin/settings',             label: 'Paramètres' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url } = usePage();

    return (
        <div className="min-h-screen bg-bg-base text-text-high grid md:grid-cols-[16rem_1fr]">
            <aside className="border-r border-border-default bg-bg-elev1 p-4">
                <Link
                    href="/admin"
                    className="block font-display font-bold uppercase tracking-wide text-base mb-6"
                >
                    ROCKETPI<span className="text-danger">.</span>ADMIN
                </Link>
                <nav className="flex flex-col gap-1 font-display text-sm uppercase tracking-wide">
                    {SECTIONS.map(s => (
                        <Link
                            key={s.href}
                            href={s.href}
                            className={
                                'px-3 py-2 rounded-md transition-all ' +
                                (url === s.href || (s.href !== '/admin' && url.startsWith(s.href))
                                    ? 'text-shard-400 bg-bg-elev2'
                                    : 'text-text-medium hover:text-text-high hover:bg-bg-elev2')
                            }
                        >
                            {s.label}
                        </Link>
                    ))}
                </nav>
                <div className="mt-8 pt-4 border-t border-border-default">
                    <Link
                        href="/dashboard"
                        className="text-xs font-display uppercase tracking-wide text-text-low hover:text-text-medium"
                    >
                        ← Retour côté joueur
                    </Link>
                </div>
            </aside>
            <main className="p-8 max-w-6xl">{children}</main>
        </div>
    );
}
