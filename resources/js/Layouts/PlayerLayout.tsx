import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import * as Dropdown from '@radix-ui/react-dropdown-menu';
import { Menu, X, ChevronDown } from 'lucide-react';
import ThemeToggle from '@ui/ThemeToggle';

interface NavLeaf  { href: string; label: string }
interface NavGroup { label: string; items: NavLeaf[]; prefixes: string[] }
type NavEntry = NavLeaf | NavGroup;

const isGroup = (n: NavEntry): n is NavGroup => 'items' in n;

/**
 * Navigation joueur — regroupée en 5 entrées + CTA "Jouer".
 * Avant : 11 entrées plates → débordement horizontal en md.
 *
 * Groupement métier :
 *  - Recrutement : acquisition d'opérateurs (Gacha, Boutique, Factions)
 *  - Progression : ce qui fait monter le compte (Missions, BP, Honneurs)
 *  - Compétition : tout ce qui touche au classement
 *  - Profil    : identité + inventaire perso (Collection, Vestiaire, etc.)
 */
const NAV: NavEntry[] = [
    { href: '/dashboard', label: 'Dashboard' },
    {
        label: 'Recrutement',
        prefixes: ['/gacha', '/shop', '/factions'],
        items: [
            { href: '/gacha',    label: 'Gacha' },
            { href: '/shop',     label: 'Boutique' },
            { href: '/factions', label: 'Factions' },
        ],
    },
    {
        label: 'Progression',
        prefixes: ['/missions', '/battlepass', '/achievements'],
        items: [
            { href: '/missions',     label: 'Missions' },
            { href: '/battlepass',   label: 'Battle Pass' },
            { href: '/achievements', label: 'Honneurs' },
        ],
    },
    {
        label: 'Compétition',
        prefixes: ['/leaderboard', '/hall-of-fame'],
        items: [
            { href: '/leaderboard',         label: 'Classement actuel' },
            { href: '/hall-of-fame',        label: 'Hall of Fame' },
            { href: '/leaderboard/history', label: 'Historique' },
        ],
    },
    {
        label: 'Profil',
        prefixes: ['/collection', '/cosmetics', '/referral', '/profile'],
        items: [
            { href: '/collection', label: 'Collection' },
            { href: '/cosmetics',  label: 'Vestiaire' },
            { href: '/referral',   label: 'Parrainage' },
            { href: '/profile',    label: 'Mon profil' },
        ],
    },
    { href: '/play', label: 'Jouer' },
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

    const groupTriggerClass = (group: NavGroup) =>
        'px-3 py-2 rounded-md transition-all font-display text-sm uppercase tracking-wide inline-flex items-center gap-1 cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-shard-500 ' +
        (group.prefixes.some(p => url.startsWith(p))
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
                            href="/dashboard"
                            className="font-display font-bold text-lg uppercase tracking-wide"
                        >
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                    </div>

                    {/* Nav desktop — 6 entrées max via regroupement dropdown */}
                    <nav className="hidden md:flex items-center gap-1">
                        {NAV.map(entry => isGroup(entry) ? (
                            <Dropdown.Root key={entry.label}>
                                <Dropdown.Trigger className={groupTriggerClass(entry)}>
                                    {entry.label}
                                    <ChevronDown size={12} aria-hidden="true" />
                                </Dropdown.Trigger>
                                <Dropdown.Portal>
                                    <Dropdown.Content
                                        align="start"
                                        sideOffset={6}
                                        className="z-overlay min-w-[180px] rounded-md bg-bg-elev2 border border-border-default p-1 shadow-el2"
                                    >
                                        {entry.items.map(item => (
                                            <Dropdown.Item key={item.href} asChild>
                                                <Link
                                                    href={item.href}
                                                    className={
                                                        'block px-3 py-2 rounded font-display text-xs uppercase tracking-wide transition-colors duration-fast outline-none focus:bg-bg-elev3 ' +
                                                        (url.startsWith(item.href)
                                                            ? 'text-shard-400 bg-bg-elev3'
                                                            : 'text-text-medium hover:text-text-high hover:bg-bg-elev3')
                                                    }
                                                >
                                                    {item.label}
                                                </Link>
                                            </Dropdown.Item>
                                        ))}
                                    </Dropdown.Content>
                                </Dropdown.Portal>
                            </Dropdown.Root>
                        ) : (
                            <Link key={entry.href} href={entry.href} className={leafClass(entry.href)}>
                                {entry.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2 sm:gap-3 min-w-0">
                        <ThemeToggle />
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

            {/* Drawer mobile — sections par groupe */}
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

                        <nav className="flex flex-col gap-3">
                            {NAV.map(entry => isGroup(entry) ? (
                                <div key={entry.label}>
                                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low px-3 mb-1">
                                        {entry.label}
                                    </p>
                                    <div className="flex flex-col gap-1">
                                        {entry.items.map(item => (
                                            <Link key={item.href} href={item.href} className={leafClass(item.href)}>
                                                {item.label}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <Link key={entry.href} href={entry.href} className={leafClass(entry.href)}>
                                    {entry.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="mt-6 pt-4 border-t border-border-default flex flex-col gap-2">
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
