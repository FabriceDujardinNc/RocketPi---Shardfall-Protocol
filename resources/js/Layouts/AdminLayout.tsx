import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import ThemeToggle from '@ui/ThemeToggle';

// Site simplifié : admin = juste gestion utilisateurs + paramètres globaux
// (PayPal / wallet crypto pour la page Dons). Modération des Idées en phase 3.
const SECTIONS = [
    { href: '/admin',          label: 'Tableau de bord' },
    { href: '/admin/players',  label: 'Joueurs' },
    { href: '/admin/ideas',    label: 'Idées' },
    { href: '/admin/settings', label: 'Paramètres' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url } = usePage();
    const [open, setOpen] = useState(false);

    useEffect(() => { setOpen(false); }, [url]);
    useEffect(() => {
        if (!open) return;
        document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = ''; };
    }, [open]);

    const linkClass = (href: string) =>
        'px-3 py-2 rounded-md transition-all ' +
        (url === href || (href !== '/admin' && url.startsWith(href))
            ? 'text-shard-400 bg-bg-elev2'
            : 'text-text-medium hover:text-text-high hover:bg-bg-elev2');

    const SidebarContent = () => (
        <>
            <Link
                href="/admin"
                className="block font-display font-bold uppercase tracking-wide text-base mb-6"
            >
                ROCKETPI<span className="text-danger">.</span>ADMIN
            </Link>
            <nav className="flex flex-col gap-1 font-display text-sm uppercase tracking-wide">
                {SECTIONS.map(s => (
                    <Link key={s.href} href={s.href} className={linkClass(s.href)}>
                        {s.label}
                    </Link>
                ))}
            </nav>
            <div className="mt-8 pt-4 border-t border-border-default flex items-center justify-between">
                <Link
                    href="/play"
                    className="text-xs font-display uppercase tracking-wide text-text-low hover:text-text-medium"
                >
                    ← Retour au jeu
                </Link>
                <ThemeToggle />
            </div>
        </>
    );

    return (
        <div className="min-h-screen bg-bg-base text-text-high md:grid md:grid-cols-[16rem_1fr]">
            {/* Top bar mobile — visible <md uniquement */}
            <header className="md:hidden sticky top-0 z-overlay flex items-center justify-between border-b border-border-default bg-bg-elev1/90 backdrop-blur px-4 h-14">
                <Link href="/admin" className="font-display font-bold uppercase tracking-wide text-sm">
                    ROCKETPI<span className="text-danger">.</span>ADMIN
                </Link>
                <div className="flex items-center gap-1">
                    <ThemeToggle />
                    <button
                        type="button"
                        onClick={() => setOpen(true)}
                        className="inline-flex items-center justify-center size-10 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2"
                        aria-label="Ouvrir le menu admin"
                    >
                        <Menu size={20} />
                    </button>
                </div>
            </header>

            {/* Sidebar desktop */}
            <aside className="hidden md:block border-r border-border-default bg-bg-elev1 p-4">
                <SidebarContent />
            </aside>

            {/* Drawer mobile */}
            {open && (
                <div className="md:hidden fixed inset-0 z-modal" role="dialog" aria-modal="true">
                    <div
                        className="absolute inset-0 bg-bg-base/80 backdrop-blur-sm"
                        onClick={() => setOpen(false)}
                        aria-hidden="true"
                    />
                    <aside className="absolute top-0 left-0 h-full w-72 max-w-[85vw] bg-bg-elev1 border-r border-border-default shadow-el3 p-4 overflow-y-auto">
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="absolute top-3 right-3 inline-flex items-center justify-center size-9 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2"
                            aria-label="Fermer le menu admin"
                        >
                            <X size={18} />
                        </button>
                        <SidebarContent />
                    </aside>
                </div>
            )}

            <main className="p-4 sm:p-6 md:p-8 max-w-6xl w-full">{children}</main>
        </div>
    );
}
